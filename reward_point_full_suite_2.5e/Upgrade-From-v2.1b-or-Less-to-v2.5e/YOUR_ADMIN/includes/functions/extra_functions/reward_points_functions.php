<?php
/**
 * File contains just the reward points functions (Admin side)
 *
 * @package classes
 * @copyright Andrew Moore
 */
if(!defined('IS_ADMIN_FLAG'))
 die('Illegal Access');

// Sync reward points status with orders everytime the function is loaded
global $db,$messageStack;;

// Update any reward point records where the corresponding order has been modified since the last reward history date
if($result=$db->Execute("SHOW TABLES LIKE '".TABLE_REWARD_STATUS_TRACK."';"))
 if($result->RecordCount()>0)
 {
    if($result=$db->Execute("SELECT ph.*, o.orders_status FROM ".TABLE_REWARD_STATUS_TRACK." ph, ".TABLE_ORDERS." o WHERE ph.orders_id=o.orders_id AND ph.date<o.last_modified;"))
     while(!$result->EOF) 
     {
        UpdateOrderRewardPointsStatus($result->fields['orders_id'],$result->fields['orders_status']);
        $result->MoveNext();
     }
    
    // Remove all reward point history records for deleted orders. Update customers pending points removing any deleted order with points still pending
    if($result=$db->Execute("SELECT * FROM ".TABLE_REWARD_STATUS_TRACK." rs WHERE rs.`orders_id` NOT IN (SELECT `orders_id` FROM ".TABLE_ORDERS.") LIMIT 1;"))
     if($result->RecordCount()>0)
      if($db->Execute("UPDATE ".TABLE_REWARD_CUSTOMER_POINTS." rc SET `pending_points`=`pending_points`-IFNULL((SELECT SUM(`reward_points`) FROM ".TABLE_REWARD_STATUS_TRACK." ph WHERE ph.`customers_id`=rc.`customers_id` AND ph.`status`=0 AND ph.`orders_id` NOT IN (SELECT `orders_id` FROM ".TABLE_ORDERS.")),0);"))
       $db->Execute("DELETE FROM ".TABLE_REWARD_STATUS_TRACK." WHERE `orders_id` NOT IN (SELECT `orders_id` FROM ".TABLE_ORDERS.");");
 }

function GetGlobalRewardPointRatio()
{
    return GetRewardPointField(FIELD_POINT_RATIO,SCOPE_GLOBAL);
}

function GetGlobalRewardBonusPoints()
{
    return GetRewardPointField(FIELD_BONUS_POINTS,SCOPE_GLOBAL);
}

function GetCategoryRewardPointRatio($category_id)
{
    return GetRewardPointField(FIELD_POINT_RATIO,SCOPE_CATEGORY,$category_id);
}

function GetCategoryRewardBonusPoints($category_id)
{
    return GetRewardPointField(FIELD_BONUS_POINTS,SCOPE_CATEGORY,$category_id);
}

function GetProductRewardPointRatio($product_id)
{
    return GetRewardPointField(FIELD_POINT_RATIO,SCOPE_PRODUCT,$product_id);
}

function GetProductRewardBonusPoints($product_id)
{
    return GetRewardPointField(FIELD_BONUS_POINTS,SCOPE_PRODUCT,$product_id);
}

function GetRewardPointField($field,$scope,$id=0)
{
    if(($result=GetRewardPointRecord($scope,$id))!=null)
     return $result->fields[$field];
    
    return null;
}

function GetRewardPointRecord($scope,$id=0)
{
    global $db;
    
    $sql="SELECT * FROM ".TABLE_REWARD_MASTER." WHERE scope='".$scope."' AND scope_id='".$id."';";
    $result=$db->Execute($sql);

    if($result->RecordCount()>0)
     return $result;
    else
     return null;
}

function UpdateRewardPointRecord($scope,$id,$point_ratio,$bonus_points=0)
{
    global $db;
    
    $sql="INSERT INTO ".TABLE_REWARD_MASTER." SET scope='".$scope."', scope_id='".$id."', point_ratio='".$point_ratio."' ON DUPLICATE KEY UPDATE point_ratio='".$point_ratio."';";
    $result=$db->Execute($sql);
}

function UpdateRedeemRatioRecord($scope,$id,$redeem_ratio)
{
    global $db;
    
    $sql="INSERT INTO ".TABLE_REWARD_MASTER." SET scope='".$scope."', scope_id='".$id."', redeem_ratio='".$redeem_ratio."' ON DUPLICATE KEY UPDATE redeem_ratio='".$redeem_ratio."';";
    $result=$db->Execute($sql);
}

function DeleteRewardPointRecord($scope,$id=0)
{
    global $db;
    
    $sql="DELETE FROM ".TABLE_REWARD_MASTER." WHERE scope='".$scope."' AND scope_id='".$id."';";
    $result=$db->Execute($sql);
}

function UpdateOrderRewardPointsStatus($order_id,$zc_status)
{
    global $messageStack;
	
    if(REWARD_POINTS_STATUS_TRACK=='') // Simple mode
     if($zc_status!=1) // If status has changed from Pending
      TransferCustomerPointsFromPending($order_id);
     else
      TransferCustomerPointsToPending($order_id);
    else // Advanced mode
     if(($record=GetLastRewardPointHistoryRecord($order_id)))
      if(($state=GetState($zc_status))!=STATUS_IGNORE && $status_change=($record->fields['status']!=$state))
       if($state==STATUS_PROCESSED)
        TransferCustomerPointsFromPending($order_id);
       else
        TransferCustomerPointsToPending($order_id);
}

function TransferCustomerPointsFromPending($order_id)
{
    global $messageStack;
    
    if(($record=GetLastRewardPointHistoryRecord($order_id)) && $record->fields['status']==STATUS_PENDING)
    {
        $customer_id=$record->fields['customers_id'];
        $reward_points=$record->fields['reward_points'];
        $pending_points=-$reward_points;
        UpdateCustomerRewardPoints($customer_id,$reward_points,$pending_points);
        UpdateRewardPointHistoryRecord($customer_id,$order_id,$reward_points,STATUS_PROCESSED);
    }
    else
     if(!$record)
      $messageStack->add_session(WARNING_MISSING_RECORD.' '.$order_id, 'warning');
}

function TransferCustomerPointsToPending($order_id)
{
    global $messageStack;
    
    if(($record=GetLastRewardPointHistoryRecord($order_id)) && $record->fields['status']==STATUS_PROCESSED)
    {
        $customer_id=$record->fields['customers_id'];
        $pending_points=$record->fields['reward_points'];
        $reward_points=-$pending_points;
        UpdateCustomerRewardPoints($customer_id,$reward_points,$pending_points);
        UpdateRewardPointHistoryRecord($customer_id,$order_id,$pending_points,STATUS_PENDING);
    }
    else
     if(!$record)
      $messageStack->add_session(WARNING_MISSING_RECORD.' '.$order_id, 'warning');
}

function UpdateRewardPointHistoryRecord($customer_id,$order_id,$reward_points,$status)
{
    global $db;
    
    $sql="REPLACE INTO ".TABLE_REWARD_STATUS_TRACK." SET customers_id='".(int)$customer_id."', orders_id='".(int)$order_id."', date=NOW(), reward_points='".$reward_points."', status=".$status.";";
    $db->Execute($sql);
}

function GetLastRewardPointHistoryRecord($order_id)
{
    global $db;

    $sql="SELECT * FROM ".TABLE_REWARD_STATUS_TRACK." WHERE orders_id='".$order_id."' ORDER BY date DESC LIMIT 1;";
    $result=$db->Execute($sql);

    if($result->RecordCount()>0)
     return $result;
    else
     return null;
}

function GetCustomerRewardPointsRecord($customer_id)
{
    global $db;

    $sql="SELECT * FROM ".TABLE_REWARD_CUSTOMER_POINTS." WHERE customers_id='".$customer_id."';";
    $result=$db->Execute($sql);

    if($result->RecordCount()>0)
     return $result;
    else
     return null;
}

function UpdateCustomerRewardPoints($customer_id,$reward_points,$pending_points)
{
    global $db;

    $sql="INSERT INTO ".TABLE_REWARD_CUSTOMER_POINTS." SET customers_id='".$customer_id."', reward_points='".$reward_points."', pending_points='".$pending_points."' ON DUPLICATE KEY UPDATE reward_points=reward_points+".$reward_points.", pending_points=pending_points+".$pending_points.";";
    $db->Execute($sql);
}

function DeleteOrderRewardPoints($order_id)
{
    global $db,$messageStack;

    if(($record=GetLastRewardPointHistoryRecord($order_id)))
    {
        $customer_id=$record->fields['customers_id'];
        $pending_points=0;
        $reward_points=0;

        if($record->fields['status']==STATUS_PENDING)
         $pending_points=-$record->fields['reward_points'];
        else
         $reward_points=-$record->fields['reward_points'];
        
        UpdateCustomerRewardPoints($customer_id,$reward_points,$pending_points);

        $sql="DELETE IGNORE FROM ".TABLE_REWARD_STATUS_TRACK." WHERE orders_id='".(int)$order_id."';";
        $db->Execute($sql);
    }
    else
     $messageStack->add_session(WARNING_NO_REWARD_POINTS_FOUND_FOR_ORDER.' '.$order_id, 'warning');
}

function CleanupRewardPointHistory()
{
    global $db,$messageStack;
    
    $subset="IFNULL((SELECT SUM(rp.reward_points) FROM ".TABLE_REWARD_STATUS_TRACK." rp WHERE cp.customers_id=rp.customers_id AND rp.status='".STATUS_PENDING."' AND rp.date<NOW()-INTERVAL %s DAY),0)";
    $sunrise_subset=sprintf($subset,REWARD_POINTS_SUNRISE_PERIOD);
    $housekeeping_subset=sprintf($subset,REWARD_POINTS_HOUSEKEEPING);
    
    if(REWARD_POINTS_SUNRISE_PERIOD>0)
     if($db->Execute("UPDATE ".TABLE_REWARD_CUSTOMER_POINTS." cp, ".TABLE_REWARD_STATUS_TRACK." rp SET cp.reward_points=cp.reward_points+".$sunrise_subset.",cp.pending_points=cp.pending_points-".$sunrise_subset." WHERE cp.customers_id=rp.customers_id;"))
      $db->Execute("UPDATE ".TABLE_REWARD_STATUS_TRACK." SET status=".STATUS_PROCESSED." WHERE status=".STATUS_PENDING." AND date<NOW()-INTERVAL ".REWARD_POINTS_SUNRISE_PERIOD." DAY;");
     
    if(REWARD_POINTS_HOUSEKEEPING>0)
     if($db->Execute("UPDATE ".TABLE_REWARD_CUSTOMER_POINTS." cp, ".TABLE_REWARD_STATUS_TRACK." rp SET cp.pending_points=cp.pending_points-".$housekeeping_subset." WHERE cp.customers_id=rp.customers_id;"))
      $db->Execute("DELETE FROM ".TABLE_REWARD_STATUS_TRACK." WHERE date<NOW()-INTERVAL ".(int)REWARD_POINTS_HOUSEKEEPING." DAY;");
}

function UseRewardPointStateFunction($value)
{
    if($value=='')
    {
        return TEXT_SIMPLE_MODE;
    }
    else
    {
        $status_list=GetOrdersStatusList();
        $earn_list=array();
        $pend_list=array();
    
        $size=count($status_list);
        foreach($status_list as $status)
         switch($status['state'])
         {
            case STATUS_PENDING:
                $pend_list[]=$status['text'];
                break;
            
            case STATUS_PROCESSED:
                $earn_list[]=$status['text'];
                break;
         }
        return TEXT_SHORT_PENDING_STATE_NAME.': ['.implode(", ",$pend_list).'] '.TEXT_SHORT_EARNED_STATE_NAME.': ['.implode(", ",$earn_list).']';
    }
}

function SetRewardPointStateFunction($value,$key='')
{
    $status_list=GetOrdersStatusList();
    $state_names=array(TEXT_PENDING_STATE_NAME,TEXT_EARNED_STATE_NAME,TEXT_IGNORE_STATE_NAME);
    
    require('includes/javascript/reward_points.js');
    
    $content='<br />';
    
    $content.='<strong>'.TEXT_MODE_PROMPT.'</strong>&nbsp;'.zen_draw_pull_down_menu('mode_id',array(array('id'=>'0','text'=>TEXT_SIMPLE_MODE),array('id'=>'1','text'=>TEXT_ADVANCED_MODE)),($value==''?'0':'1'),'onchange="UpdateMode()"').'<br />';
    $content.='<div id="AdvancedModeTable" style="display: '.($value==''?'none':'block').'"><br /><center><table width="90%" border="0" cellspacing="0" bgcolor="#d0d0d0"><tbody><tr><th>&nbsp;</th>';
    $name_size=count($state_names);
    for($s=0;$s<$name_size;$s++)
     $content.='<th align="center">'.$state_names[$s].'</th>';
    $content.='</tr>';
    
    $size=count($status_list);
    for($i=0;$i<$size;$i++)
    {
        $content.='<tr '.($i%2==0?'BGCOLOR="#FFFFFF"':'').'><td><b>'.$status_list[$i]['text'].':</b></td>';
        for($s=0;$s<$name_size;$s++)
         $content.='<td align="center"><INPUT TYPE=RADIO NAME="'.$status_list[$i]['text'].'" ID="'.$status_list[$i]['id'].'"VALUE="'.$s.'"'.($status_list[$i]['state']==$s?' CHECKED':'').' onchange="UpdateStateList()"></td>';
        $content.='</tr>';
    }
    $content.='</tbody></table></center></div>';
    $content.=zen_draw_hidden_field('configuration_value',$value);
    return $content;
}

function GetState($status_id)
{
    $status_list=GetOrdersStatusList();
    foreach($status_list as $status)
     if($status['id']==$status_id)
      return $status['state'];
}

function GetOrdersStatusList()
{
    global $db;
    $states=GetRewardPointStateList();
    $list=array();

    if(isset($_SESSION['languages_id']))
     $sql="SELECT * FROM ".TABLE_ORDERS_STATUS." WHERE language_id='".$_SESSION['languages_id']."' ORDER BY orders_status_id;";
	else
     $sql="SELECT * FROM ".TABLE_ORDERS_STATUS." WHERE 1 ORDER BY orders_status_id;";
	 
    $result=$db->Execute($sql);
    $i=0;
    while(!$result->EOF) 
    {
        $list[]=array('id'=>$result->fields['orders_status_id'],'text'=>$result->fields['orders_status_name'],'state'=>(isset($states[$result->fields['orders_status_id']])?$states[$result->fields['orders_status_id']]:STATUS_IGNORE));
        $i++;
        $result->MoveNext();
    }
    return $list;
}

function GetRewardPointStateList()
{
    if(REWARD_POINTS_STATUS_TRACK=='')
     return NULL;
    else
    {
        $states=array();
        $list=explode("/",REWARD_POINTS_STATUS_TRACK);

        $pend_list=explode(",",$list[0]);
        foreach($pend_list as $pend_item)
         $states[$pend_item]=STATUS_PENDING;
         
        $earn_list=explode(",",$list[1]);
        foreach($earn_list as $earn_item)
         $states[$earn_item]=STATUS_PROCESSED;
        
        return $states;
    }
}

function CheckText($string1,$string2='NULL')
{
    return (is_null($string1)?$string2:$string1);
}

function GetPricingGroupList()
{
    global $db;

    $group_array_query = $db->execute("SELECT rm.scope_id, rm.redeem_ratio, gp.group_name FROM ".TABLE_REWARD_MASTER." rm, ".TABLE_GROUP_PRICING." gp WHERE rm.scope_id=gp.group_id AND rm.scope='".SCOPE_GROUP."';");
    $group_array[]=array('id'=>0, 'text'=>TEXT_NONE);
    while(!$group_array_query->EOF)
    {
        $group_array[]=array('id'=>$group_array_query->fields['scope_id'], 'text'=>$group_array_query->fields['group_name'].':&nbsp;'.$group_array_query->fields['redeem_ratio']);
        $group_array_query->MoveNext();
    }
    return $group_array;
}

function UseRewardPointNewAccountAwardFunction($value)
{
    if($value==0)
     return TEXT_NO_NEW_ACCOUNT_AWARD;
    else
     if($value>0)
      return abs((int)$value).TEXT_NEW_ACCOUNT_EARNED_AWARD;
     else
      return abs((int)$value).TEXT_NEW_ACCOUNT_PENDING_AWARD;
}

function SetRewardPointNewAccountAwardFunction($value,$key='')
{
    require('includes/javascript/reward_points.js');
    
    $content='<br />';
    
    $content.='<strong>'.TEXT_NEW_ACCOUNT_AWARD_PROMPT.'</strong>&nbsp;'.zen_draw_checkbox_field('allow_award','',$value!=0,0,'onchange="UpdateAward()"');
    $content.=zen_draw_input_field('award_points',($value==0?'':abs($value)),'onchange="UpdateAward()"').'&nbsp;'.zen_draw_pull_down_menu('award_id',array(array('id'=>'0','text'=>TEXT_NEW_ACCOUNT_PENDING_AWARD),array('id'=>'1','text'=>TEXT_NEW_ACCOUNT_EARNED_AWARD)),($value<0?'0':$value>0?'1':''),'onchange="UpdateAward()"').'<br />';
    $content.=zen_draw_hidden_field('configuration_value',$value);

    return $content;
}

function UseRewardPointDiscountTableFunction($value)
{
    $discount_list=GetRewardPointDiscountTable();
    
    $content='<div id="RewardPointDiscountTable" style="display: block"><center><table width="200px" border="0" cellspacing="0" bgcolor="#d0d0d0"><tbody align="right">';
    $content.='<tr><th width="80px">Discount</th><th width="120px">Points Required</th></tr>';
    
    $size=count($discount_list);
    for($i=0;$i<$size;$i++)
     $content.='<tr'.($i%2==0?' BGCOLOR="#FFFFFF"':'').'><td>'.$discount_list[$i]['discount'].'</td><td>'.$discount_list[$i]['required'].'</td></tr>';
     
    //$content.=zen_draw_hidden_field('configuration_value',$value);
    $content.='</tbody></table></center></div><br />';
    
    return $content;
}

function GetRewardPointDiscountRow($reward_points)
{
    $discount_list=GetRewardPointDiscountTable();
    $size=count($discount_list);
    
    for($i=0;$i<$size;$i++)
     if($reward_points<$discount_list[$i]['required'])
      if($i>0)
       return $discount_list[$i-1];
      else
       return NULL;
       
    return $discount_list[$size-1];
}

function GetRewardPointDiscountTable()
{
    if(MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_TABLE=='')
     return NULL;
    else
    {
        $discounts=array();
        $list=explode(",",MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_TABLE);

        foreach($list as $record)
        {
            $fields=explode(":",$record);
            array_push($discounts,array('discount'=>$fields[0],'required'=>$fields[1]));
        }
        
        usort($discounts,"SortDiscountTable");
        return $discounts;
    }
}

function SortDiscountTable($a,$b)
{
    $diff=(int)$a['discount']-(int)$b['discount'];
    return ($diff==0?0:$diff>0?1:-1);
}

function SetRewardPointDiscountTableFunction($value,$key='')
{
    require('includes/javascript/reward_points.js');
    
    $discount_list=GetRewardPointDiscountTable();
    
    $content='<div id="RewardPointDiscountTableDiv" style="display: block"><center><table id="RewardPointDiscountTable" width="220px" border="0" cellspacing="0" bgcolor="#d0d0d0"><tbody align="right">';
    $content.='<tr><th width="80px">Discount</th><th width="120px">Points Required</th><th width="20px">&nbsp</th></tr>';
    
    $size=count($discount_list);
    for($i=0;$i<$size;$i++)
     $content.='<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="SetDiscountTableFields(this)"><td>'.$discount_list[$i]['discount'].'</td><td>'.$discount_list[$i]['required'].'</td><td>'.zen_image(DIR_WS_IMAGES . 'icon_rp_delete.gif', ICON_DELETE,'','','onclick="DeleteDiscountRecord(this)"').'</tr>';
     
    $content.='<tr><td>'.zen_draw_input_field('discountField','','size="8" maxlength="8" id="discountField"').'</td><td>'.zen_draw_input_field('requiredField','','size="8" maxlength="8" id="requiredField"').'</td><td>'.zen_image(DIR_WS_IMAGES . 'icon_rp_add.gif', ICON_ADD_CONFIRM,'','','onclick="AddOrUpdateDiscountRecord()"').'</td></tr>';
    $content.='</tbody></table></center></div><br />';
    $content.=zen_draw_hidden_field('configuration[MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_TABLE]',$value);
    
    return $content;
}

function UseRewardPointDiscountTypeFunction($value)
{
	if($value=='0')
	 $content=MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_TYPE_0;
	else
	 $content=MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_TYPE_1;
    
    return $content;
}

//--- Advanced calculate functions

function UseRewardPointAdvancedCalculateTableFunction($value)
{
	if($value=='')
	 $content=ADVANCED_RULES_DISABLED;
	else
	 $content=ADVANCED_RULES_ENABLED;
	 
	return $content;
/*
    $module_list=GetRewardPointAdvancedCalculateTable();
    
    $content='<div id="RewardPointAdvancedCalculateTable" style="display: block"><center><table width="200px" border="0" cellspacing="0" bgcolor="#d0d0d0"><tbody align="left">';
    $content.='<tr><th width="140px">Module</th><th width="60px">Action</th></tr>';
    
    $size=count($module_list);
    for($i=0;$i<$size;$i++)
     $content.='<tr'.($i%2==0?' BGCOLOR="#FFFFFF"':'').'><td>'.$module_list[$i]['module'].'</td><td>'.$module_list[$i]['action'].'</td></tr>';
     
    //$content.=zen_draw_hidden_field('configuration_value',$value);
    $content.='</tbody></table></center></div><br />';
    
    return $content;
*/
}

function GetRewardPointAdvancedCalculateTable()
{
    if(REWARD_POINTS_ADVANCED_CALCULATE_TABLE=='')
     return NULL;
    else
    {
		$modules=array();
        $list=explode(",",REWARD_POINTS_ADVANCED_CALCULATE_TABLE);
		foreach($list as $record)
         array_push($modules,array('module'=>substr($record,1),'action'=>(substr($record,0,1)=="-"?"Subtract":"Add")));

        //usort($modules,"SortModulesTable");
        return $modules;
    }
}

function SortModulesTable($a,$b)
{
    $diff=(int)$a['discount']-(int)$b['discount'];
    return ($diff==0?0:$diff>0?1:-1);
}

function SetRewardPointAdvancedCalculateTableFunction($value,$key='')
{
    require('includes/javascript/reward_points.js');
	$ignore_list=array("ot_subtotal","ot_reward_points","ot_reward_points_debug","ot_reward_points_display","ot_reward_points_discount","ot_reward_points_redeem","ot_total");
    $action_list=array(array('id'=>"Subtract",'text'=>"Subtract"),array('id'=>"Add",'text'=>"Add"));
    $module_list=GetRewardPointAdvancedCalculateTable();
	$installed_module_list=array();
	$module_directory=GetInstalledModules(DIR_FS_CATALOG_MODULES.'order_total/');
	
	foreach($module_directory as $value)
	 if(($module=substr($value,0,strrpos($value, '.')))!=NULL)
	  if(!in_array($module,$ignore_list))
	   array_push($installed_module_list,array('id'=>$module,'text'=>$module));
    sort($installed_module_list);
	
    $content='<div id="RewardPointAdvancedCalculateTableDiv" style="display: block"><center><table id="RewardPointAdvancedCalculateTable" width="220px" border="0" cellspacing="0" bgcolor="#d0d0d0"><tbody align="left">';
    $content.='<tr><th width="140px">Module</th><th width="60px">Action</th><th width="20px">&nbsp</th></tr>';
    
    $size=count($module_list);
    for($i=0;$i<$size;$i++)
     $content.='<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)"><td>'.$module_list[$i]['module'].'</td><td>'.$module_list[$i]['action'].'</td><td>'.zen_image(DIR_WS_IMAGES . 'icon_rp_delete.gif', ICON_DELETE,'','','onclick="DeleteAdvancedCalculateRecord(this)"').'</tr>';
     
    $content.='<tr><td>'.zen_draw_pull_down_menu('moduleField',$installed_module_list,'size="16" maxlength="16" id="moduleField"').'</td><td>'.zen_draw_pull_down_menu('actionField',$action_list,'size="8" maxlength="8" id="actionField"').'</td><td>'.zen_image(DIR_WS_IMAGES . 'icon_rp_add.gif', ICON_ADD_CONFIRM,'','','onclick="AddAdvancedCalculateRecord()"').'</td></tr>';
    $content.='</tbody></table></center></div><br />';
    $content.=zen_draw_hidden_field('configuration_value',$value);
    
    return $content;
}

function GetInstalledModules($module_directory)
{
	$directory_array=array();
	
	if($dir=@dir($module_directory))
	 while($file=$dir->read())
      if(!is_dir($module_directory.$file))
	   if(substr($file,strrpos($file,'.'))==".php")
		$directory_array[]=$file;
		
    sort($directory_array);
    $dir->close();
	
	return $directory_array;
}
function GetRewardPoints($products)
{
    $reward_points=0;
    if(REWARD_POINT_MODE=='0')
    {
        foreach($products as $product)
         if(isset($product['qty']))
          $reward_points+=GetProductRewardPoints($product['id'],$product['attributes'])*$product['qty'];
         else
          if(isset($product['quantity']))
           $reward_points+=GetProductRewardPoints($product['id'],$product['attributes'])*$product['quantity'];
          else
           if(isset($product['quantityField']))
            $reward_points+=GetProductRewardPoints($product['id'],$product['attributes'])*$product['quantityField'];
          else
           $reward_points="RP Error";
    }
    else
    {
        global $order;
        
        $GlobalRewardPointRatio=GetGlobalRewardPointRatio();
        if(isset($_SESSION['cart']))
         $reward_points=zen_round($_SESSION['cart']->show_total()*$GlobalRewardPointRatio-REWARD_POINTS_ROUNDING,0);
         
        if(isset($order) && isset($order->info))
         if(REWARD_POINTS_ALLOW_TOTAL=='0' && isset($order->info['subtotal']))
          $reward_points=zen_round($order->info['subtotal']*$GlobalRewardPointRatio-REWARD_POINTS_ROUNDING,0);
         else
          if(isset($order->info['total']))
           $reward_points=zen_round($order->info['total']*$GlobalRewardPointRatio-REWARD_POINTS_ROUNDING,0);
    }
    return $reward_points;
}

function GetProductRewardPoints($products_id,$attributes=null)
{
    global $db;
    $reward_price=0;
    
    if(zen_get_products_price_is_free($products_id)==false || REWARD_POINTS_ALLOW_ON_FREE=='1') // Allow RP on free items (Admin settable)
    {
        $sql = "SELECT prp.point_ratio*p.products_price AS reward_points, prp.point_ratio, p.products_price, p.products_priced_by_attribute 
                FROM ".TABLE_REWARD_MASTER." prp, ".TABLE_PRODUCTS." p, ".TABLE_PRODUCTS_TO_CATEGORIES." p2c 
                WHERE p.products_id='" . $products_id . "'
                AND p2c.products_id='" . $products_id . "'
                AND ((prp.scope_id=p.products_id AND prp.scope='".SCOPE_PRODUCT."') 
                OR (p.products_id=p2c.products_id AND prp.scope_id=p2c.categories_id AND prp.scope='".SCOPE_CATEGORY."')
                OR (prp.scope='".SCOPE_GLOBAL."'))
                ORDER BY prp.scope DESC LIMIT 1;";
    
        $result=$db->Execute($sql);
    
        if($result)
        {
            if(zen_has_product_attributes($products_id,'false') && !$attributes)
             $reward_price=zen_get_products_base_price($products_id);
            else
             $reward_price=$result->fields['products_price'];
             
            //echo '['.$reward_price.'=';
            //print_r($attributes);
            //echo ']';
            
            $special_price=zen_get_products_special_price($products_id);
            
            if(REWARD_POINTS_SPECIAL_ADJUST=='1' && $special_price && !$attributes)
             $reward_price=$special_price;
        
            // Calculate attribute pricing
            //if($result->fields['products_priced_by_attribute']=='1' && $attributes!=null)
            if($attributes!=null)
             if(isset($attributes[0]['option_id']))
              foreach($attributes as $attribute)
               $reward_price+=CalculateRewardPointsOnAttribute($products_id,$attribute['option_id'],$attribute['value_id']);
             else
              foreach($attributes as $option_id => $value_id)
               $reward_price+=CalculateRewardPointsOnAttribute($products_id,$option_id,$value_id);
        }
    }

    //echo '::'.$reward_price.', '.$result->fields['point_ratio'].', '.REWARD_POINTS_ROUNDING.'::';
    $reward_points=($reward_price*$result->fields['point_ratio'])-REWARD_POINTS_ROUNDING;
    if($reward_points<0)
     $reward_points=0;
     
    return zen_round($reward_points,0);
}

function CalculateRewardPointsOnAttribute($products_id,$option_id,$value_id)
{
    global $db;
    
    if($attribute=$db->Execute("SELECT products_attributes_id, attributes_discounted, options_values_price, price_prefix FROM ".TABLE_PRODUCTS_ATTRIBUTES." WHERE products_id='".$products_id."' AND options_id='".$option_id."' AND options_values_id='".$value_id."';"))
     if(REWARD_POINTS_SPECIAL_ADJUST=='1' && $attribute->fields['attributes_discounted']=='1')
      $new_attributes_price=zen_get_discount_calc($products_id,$attribute->fields['products_attributes_id'],$attribute->fields['options_values_price'],1);
     else 
      $new_attributes_price=$attribute->fields['options_values_price'];
      
    return ($attribute->fields['price_prefix']=='-'?-$new_attributes_price:$new_attributes_price);
}

function GetRedeemRatio($customers_id)
{
    global $db;
    
    $sql = "SELECT redeem_ratio 
            FROM ".TABLE_REWARD_MASTER." prp, ".TABLE_CUSTOMERS." as c
            LEFT JOIN(".TABLE_GROUP_PRICING." as gp) ON (gp.group_id=c.customers_group_pricing)
            WHERE c.customers_id='".(int)$customers_id."'
            AND ((prp.scope_id='".$customers_id."' AND prp.scope='".SCOPE_CUSTOMER."')
            OR (gp.group_id=c.customers_group_pricing AND prp.scope_id=gp.group_id AND scope='".SCOPE_GROUP."')
            OR (prp.scope='".SCOPE_GLOBAL."'))
            ORDER BY prp.scope DESC LIMIT 1;"; 

    $result=$db->Execute($sql);

    if($result)
     return $result->fields['redeem_ratio'];
    else
     return 0;
}

function GetRewardPointsRedeemMaximum($order_total)
{
    $redeem_ratio=GetRedeemRatio($_SESSION['customer_id']);
    $order_total_points=zen_round($order_total/$redeem_ratio,0);

    if((double)REWARD_POINTS_REDEEM_MAXIMUM>0)
     if(strpos(REWARD_POINTS_REDEEM_MAXIMUM,"%")!==false)
      return zen_round($order_total_points*((double)REWARD_POINTS_REDEEM_MAXIMUM/100),0);
     else
      if($order_total_points>REWARD_POINTS_REDEEM_MAXIMUM)
       return zen_round(REWARD_POINTS_REDEEM_MAXIMUM,0);

    return zen_round($order_total_points,0);
}

function GetCustomersRewardPoints($customers_id)
{
    $result=GetCustomerRewardPointsRecord($customers_id);
    if($result)
     return (int)$result->fields['reward_points'];
    else
     return 0;
}

function GetCustomersPendingPoints($customers_id)
{
    $result=GetCustomerRewardPointsRecord($customers_id);
    if($result)
     return (int)$result->fields['pending_points'];
    else
     return 0;
}

function GetCustomersLastOrderID($customers_id)
{
    global $db;
    
    $orders_lookup_query="SELECT orders_id FROM ".TABLE_ORDERS." WHERE customers_id = '".(int)$customers_id."' ORDER BY orders_id DESC LIMIT 1";
    $orders_lookup = $db->Execute($orders_lookup_query);
    if(isset($orders_lookup->fields))
     return $orders_lookup->fields['orders_id'];
    else
     return 0;
}

function ExtractNumber($str)
{
    if(preg_match("/^[0-9]*[\.]{1}[0-9-]+$/",$str,$match))
     return floatval($match[0]);
    else
     return floatval($str);    
}

function GetOrderTotalsArray($called_by)
{
    global $order_total_modules;
    
    $order_total_array = array();
    $modules=$order_total_modules->modules;
    if(is_array($modules))
    {
        reset($modules);
        while (list(,$value)=each($modules)) 
        {
            $class=substr($value, 0, strrpos($value, '.'));
            if($class!=$called_by && isset($GLOBALS[$class]))
            {
                $output_backup=$GLOBALS[$class]->output;
                if(sizeof($GLOBALS[$class]->output)==0)
                 $GLOBALS[$class]->process();
                for ($i=0, $n=sizeof($GLOBALS[$class]->output); $i<$n; $i++)
                 if(zen_not_null($GLOBALS[$class]->output[$i]['title']) && zen_not_null($GLOBALS[$class]->output[$i]['text']))
                  $order_total_array[]=array('code' => $GLOBALS[$class]->code,'title' => $GLOBALS[$class]->output[$i]['title'],'text' => $GLOBALS[$class]->output[$i]['text'],'value' => $GLOBALS[$class]->output[$i]['value'],'sort_order' => $GLOBALS[$class]->sort_order);
                  
                $GLOBALS[$class]->output=$output_backup;
            }
        }
    }
    return $order_total_array;
}

function GetRewardPointAdvancedCalculateValue()
{
    $value=0;
    
    $module_list=GetRewardPointAdvancedCalculateTable();
    
    foreach($module_list as $module)
     if($module['action']=="Subtract")
      $value-=GetOrderTotalValue($module['module']);
     else
      $value+=GetOrderTotalValue($module['module']);
      
    return $value;
}

function GetOrderTotalValue($module)
{
    global $order;
    $value=0;
    
    if(isset($GLOBALS[$module]) && isset($order->info))
    {
        //print_r($GLOBALS[$module]->output);
        //$output_backup=$GLOBALS[$module]->output;
        //$order_info_backup=$order->info;
        //if(sizeof($GLOBALS[$module]->output)==0)
         //$GLOBALS[$module]->process();
        for($loop=0;$loop<sizeof($GLOBALS[$module]->output);$loop++)
         if(zen_not_null($GLOBALS[$module]->output[$loop]['value']))
          $value+=$GLOBALS[$module]->output[$loop]['value'];
                  
        //$GLOBALS[$module]->output=$output_backup;
        //$order->info=$order_info_backup;
    }
    return $value;
}
/*
function SetRewardPointDiscountTypeFunction($value,$key='')
{
    require('includes/javascript/reward_points.js');
    
    $content='<br />';
    
    $content.='<strong>'.TEXT_NEW_ACCOUNT_AWARD_PROMPT.'</strong>&nbsp;'.zen_draw_checkbox_field('allow_award','',$value!=0,0,'onchange="UpdateAward()"');
    $content.=zen_draw_input_field('award_points',($value==0?'':abs($value)),'onchange="UpdateAward()"').'&nbsp;'.zen_draw_pull_down_menu('award_id',array(array('id'=>'0','text'=>TEXT_NEW_ACCOUNT_PENDING_AWARD),array('id'=>'1','text'=>TEXT_NEW_ACCOUNT_EARNED_AWARD)),($value<0?'0':$value>0?'1':''),'onchange="UpdateAward()"').'<br />';
    $content.=zen_draw_hidden_field('configuration_value',$value);

    return $content;
}
*/
?>