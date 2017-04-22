<?php
/**
 * File contains just the reward points functions (Catalog side)
 *
 * @package classes
 * @copyright Andrew Moore
 */
if(!defined('IS_ADMIN_FLAG'))
 die('Illegal Access');

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

function GetGlobalRewardPointRatio()
{
	global $db;
	
	$sql = "SELECT prp.point_ratio FROM ".TABLE_REWARD_MASTER." prp WHERE prp.scope='".SCOPE_GLOBAL."' LIMIT 1;";
	
	$result=$db->Execute($sql);
	if($result)
	 return $result->fields['point_ratio'];
	else
	 return 0;
}

function GetRewardPointRatio($products_id)
{
	global $db;
	
	$sql = "SELECT prp.point_ratio 
			FROM ".TABLE_REWARD_MASTER." prp, ".TABLE_PRODUCTS." p, ".TABLE_PRODUCTS_TO_CATEGORIES." p2c 
			WHERE p.products_id='" . $products_id . "'
			AND p2c.products_id='" . $products_id . "'
			AND ((prp.scope_id=p.products_id AND prp.scope='".SCOPE_PRODUCT."') 
			OR (p.products_id=p2c.products_id AND prp.scope_id=p2c.categories_id AND prp.scope='".SCOPE_CATEGORY."')
			OR (prp.scope='".SCOPE_GLOBAL."'))
			ORDER BY prp.scope DESC LIMIT 1;";
	
	$result=$db->Execute($sql);
	if($result)
	 return $result->fields['point_ratio'];
	else
	 return 0;
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

function GetCustomerRewardPointsRecord($customers_id)
{
	global $db;
	
	$sql="SELECT * FROM ".TABLE_REWARD_CUSTOMER_POINTS." WHERE customers_id='".(int)$customers_id."';";

	$result=$db->Execute($sql);

	return $result;
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

function UpdateCustomerRewardPoints($customer_id,$reward_points,$pending_points)
{
	global $db;

	$sql="INSERT INTO ".TABLE_REWARD_CUSTOMER_POINTS." VALUES ('".$customer_id."', '".$reward_points."', '".$pending_points."') ON DUPLICATE KEY UPDATE reward_points=reward_points+".$reward_points.", pending_points=pending_points+".$pending_points.";";
	$db->Execute($sql);
}

function ExtractNumber($str)
{
	if(preg_match("/^[0-9]*[\.]{1}[0-9-]+$/",$str,$match))
	 return floatval($match[0]);
	else
     return floatval($str);	
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
?>