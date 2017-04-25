<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2008 Andrew Moore                                      |
// |                                                                      |
// | http://www.zen-cart.com/index.php                                    |
// |                                                                      |
// | Portions Copyright (c) 2003 The zen-cart developers                  |
// | Portions Copyright (c) 2003 osCommerce                               |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.zen-cart.com/license/2_0.txt.                             |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@zen-cart.com so we can mail you a copy immediately.          |
// +----------------------------------------------------------------------+
//
require('includes/application_top.php');
require(DIR_WS_CLASSES . 'currencies.php');
$currencies = new currencies();
CleanupRewardPointHistory();

if(!isset($_SESSION['customer_sort_order']))
 $_SESSION['customer_sort_order']=0;
 
$action=(isset($_GET['action'])?$_GET['action']:null);

if(zen_not_null($action))
{
	switch ($action)
	{
	  case 'update':
		if(isset($_GET['id']))
		{
			$id=$_GET['id'];
			if(isset($_REQUEST['customers_reward_group']))
			 $db->Execute("UPDATE ".TABLE_CUSTOMERS." SET customers_group_pricing=".$_REQUEST['customers_reward_group']." WHERE customers_id=".$id.";");
			 
			$pending_delta=0;
			$earned_delta=0;
			if(($current_reward_points=GetCustomerRewardPointsRecord($id))!=null && isset($_REQUEST['total_points_action']))
			 switch($_REQUEST['total_points_action'])
			 {
				case 'TransferAllToEarned':
					$earned_delta+=$current_reward_points->fields['pending_points'];
					$pending_delta-=$current_reward_points->fields['pending_points'];
					$db->Execute("UPDATE IGNORE ".TABLE_REWARD_STATUS_TRACK." SET status=".STATUS_PROCESSED." WHERE customers_id=".$id." AND status=".STATUS_PENDING.";");
					break;
					
				case 'TransferAllToPending':
					$earned_delta-=$current_reward_points->fields['reward_points'];
					$pending_delta+=$current_reward_points->fields['reward_points'];
					$db->Execute("UPDATE IGNORE ".TABLE_REWARD_STATUS_TRACK." SET status=".STATUS_PENDING." WHERE customers_id=".$id." AND status=".STATUS_PROCESSED.";");
					break;
					
				case 'ResetAllPending':
					$pending_delta-=$current_reward_points->fields['pending_points'];
					$db->Execute("DELETE IGNORE FROM ".TABLE_REWARD_STATUS_TRACK." WHERE customers_id=".$id." AND status=".STATUS_PENDING.";");
					break;
					
				case 'ResetAllEarned':
					$earned_delta-=$current_reward_points->fields['reward_points'];
					$db->Execute("DELETE IGNORE FROM ".TABLE_REWARD_STATUS_TRACK." WHERE customers_id=".$id." AND status=".STATUS_PROCESSED.";");
					break;
					
				case 'ResetAll':
					$pending_delta-=$current_reward_points->fields['pending_points'];
					$earned_delta-=$current_reward_points->fields['reward_points'];
					$db->Execute("DELETE IGNORE FROM ".TABLE_REWARD_STATUS_TRACK." WHERE customers_id=".$id." AND (status=".STATUS_PROCESSED." OR status=".STATUS_PENDING.");");
					break;
			 }
			 
			if(isset($_REQUEST['variable_points_action']) && isset($_REQUEST['points']))
			 switch($_REQUEST['variable_points_action'])
			 {
				case 'AddToEarned':
					$earned_delta+=$_REQUEST['points'];
					break;
					
				case 'AddToPending':
					$pending_delta+=$_REQUEST['points'];
					break;
					
				case 'SubtractFromEarned':
					$earned_delta-=$_REQUEST['points'];
					break;
					
				case 'SubtractFromPending':
					$pending_delta-=$_REQUEST['points'];
					break;
			 }
				
			if($earned_delta!=0 || $pending_delta!=0)
			 UpdateCustomerRewardPoints($id,$earned_delta,$pending_delta);
		}
		break;
		
	  case 'handle_point_list':
		if(isset($_REQUEST['reward_point_table_action']) && isset($_REQUEST['id']))
		{
			$id=$_GET['id'];
			
			$id_list=array();
			for($loop=0;$loop<REWARD_POINTS_MAX_TRANSACTIONS;$loop++)
			 if(isset($_REQUEST['rewards_id_'.$loop]))
			  $id_list[]="rewards_id=".$_REQUEST['rewards_id_'.$loop];
		   
			$list=implode(" OR ",$id_list);
			switch($_REQUEST['reward_point_table_action'])
			{
				case 'TransferToEarned':
					if(($result=$db->Execute("SELECT SUM(reward_points) FROM ".TABLE_REWARD_STATUS_TRACK." WHERE status=".STATUS_PENDING." AND (".$list.");")))
					{
						$db->Execute("UPDATE ".TABLE_REWARD_STATUS_TRACK." SET status=".STATUS_PROCESSED." WHERE ".$list.";");
						if($transfer_points=$result->fields['SUM(reward_points)'])
						 UpdateCustomerRewardPoints($id,$transfer_points,-$transfer_points);
					}
					break;
				
				case 'DeleteRecords':
					$db->Execute("DELETE IGNORE FROM ".TABLE_REWARD_STATUS_TRACK." WHERE ".$list.";");
					break;
			}
		}
		break;
		
	  case 'reset_index':
		unset($_SESSION['customer_sort_index']);
		unset($_SESSION['search']);
		break;
	}
}
else
{
	if(isset($_GET['customer_sort_order']))
	 $_SESSION['customer_sort_order']=zen_db_input(zen_db_prepare_input($_GET['customer_sort_order']));

	if(isset($_GET['customer_sort_index']))
	{
		$_SESSION['customer_sort_index']=zen_db_input(zen_db_prepare_input($_GET['customer_sort_index']));
		unset($_SESSION['search']);
	}
		
	if(isset($_GET['search']))
	{
		$_SESSION['search']=zen_db_input(zen_db_prepare_input($_GET['search']));
		unset($_SESSION['customer_sort_index']);
	}
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/reward_points.css">
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript">
  <!--
  function init()
  {
    cssjsmenu('navbar');
    if (document.getElementById)
    {
      var kill = document.getElementById('hoverJS');
	  if(kill)
       kill.disabled = true;
    }

  }

  function SetCheckboxes(formname,prefix,count,state)
  {
	for(var loop=0;loop<count;loop++) 
	{
		box=eval("document."+formname+"."+prefix+loop); 
		if(box.checked!=state)
		 box.checked=state;
    }
  }

  // -->
</script>
</head>
<body onload="init()">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<!-- body_text //-->
<table border="0" width="100%" cellspacing="0" cellpadding="2">
 <tr height="40px">
  <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
  <td class="smallText" align="right">
  <?php
    $customer_sort_order_array = array(array('id' => '0', 'text' => TEXT_SORT_CUSTOMER_LASTNAME),array('id' => '1', 'text' => TEXT_SORT_CUSTOMER_FIRSTNAME),array('id' => '2', 'text' => TEXT_SORT_CUSTOMER_ID),array('id' => '3', 'text' => TEXT_SORT_PRICING_GROUP),array('id' => '4', 'text' => TEXT_SORT_PENDING_POINTS),array('id' => '5', 'text' => TEXT_SORT_REWARD_POINTS));
    $customer_sort_order = $_SESSION['customer_sort_order'];
	echo TEXT_CUSTOMER_SORT_ORDER_INFO . zen_draw_form('set_customer_sort_order_form', FILENAME_ADMIN_CUSTOMER_REWARD_POINTS, '', 'get') . '&nbsp;&nbsp;' . zen_draw_pull_down_menu('customer_sort_order', $customer_sort_order_array, $customer_sort_order, 'onChange="this.form.submit();"') . zen_hide_session_id();
	echo '</form>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

	echo zen_draw_form('search', FILENAME_ADMIN_CUSTOMER_REWARD_POINTS,'','get');
	$parameters = explode('&', zen_get_all_get_params(array('search', 'action')));
	foreach($parameters as $parameter) {
	  if(!empty($parameter)) {
	    list($key, $value) = explode('=', $parameter);
	    echo zen_draw_hidden_field($key, $value);
	  }
	}
	
	echo HEADING_TITLE_SEARCH_DETAIL.'&nbsp;'.zen_draw_input_field('search',zen_db_input(zen_db_prepare_input($_SESSION['search']))).zen_hide_session_id();
	echo '</form>';
  ?>
 </td>
 </tr>
</table>

<table border="1" cellspacing="0" cellpadding="0" width="75%" bgcolor="#FFFFCC">
 <tr class="dataTableHeadingRow">
  <td class="dataTableHeadingContent">Last Name Index:</td>
<?php
 for($loop=0;$loop<26;$loop++)
  echo '<td class="dataTableRow" width="3%" align="center" onmouseover="rowOverEffect(this);" onmouseout="rowOutEffect(this);" onclick="document.location.href=\''.zen_href_link(FILENAME_ADMIN_CUSTOMER_REWARD_POINTS, zen_get_all_get_params(array('customer_sort_index', 'action', 'id', 'page')) . 'customer_sort_index='.chr(65+$loop)).'\'">'.chr(65+$loop).'</td>';
   echo '<td class="dataTableRow" width="6%" align="center" onmouseover="rowOverEffect(this);" onmouseout="rowOutEffect(this);" onclick="document.location.href=\''.zen_href_link(FILENAME_ADMIN_CUSTOMER_REWARD_POINTS,'action=reset_index').'\';">Reset</td>';
?>
 </tr>
</table>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
 <tr>
  <td width="75%" height="1px"></td>
  <td width="25%" height="1px"></td>
 </tr>
 <tr>
  <td valign="top">
   <table border="0" width="100%" cellspacing="1" cellpadding="0">
    <tr class="dataTableHeadingRow">
     <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CUSTOMER_ID; ?></td>
	 <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CUSTOMER_LAST_NAME; ?></td>
	 <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CUSTOMER_FIRST_NAME; ?></td>
	 <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CUSTOMER_PENDING_POINTS; ?></td>
	 <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CUSTOMER_EARNED_POINTS; ?></td>
	 <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CUSTOMER_PRICING_GROUP; ?></td>
	 <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_GROUP_REDEEM_RATIO; ?></td>
	 <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?></td>
    </tr>
<?php
 /*if(isset($_SESSION['search']))
  $where=" WHERE (c.customers_lastname LIKE '%".$_SESSION['search']."%' OR c.customers_firstname LIKE '%".$_SESSION['search']."%' OR c.customers_id LIKE '%".$_SESSION['search']."%' OR c.customers_email_address LIKE '%".$_SESSION['search']."%')";
 else
  if(isset($_SESSION['customer_sort_index']))
   if($_SESSION['customer_sort_order']==1)
    $where=" WHERE c.customers_firstname LIKE '".$_SESSION['customer_sort_index']."%'";
   else
    $where=" WHERE c.customers_lastname LIKE '".$_SESSION['customer_sort_index']."%'";
   else
    $where="";
  */
  
  $where = 'WHERE c.customers_id > 0';
  
  if(isset($_SESSION['search'])) {
    $where .= " AND (c.customers_lastname LIKE '%".$_SESSION['search']."%' OR c.customers_firstname LIKE '%".$_SESSION['search']."%' OR c.customers_id LIKE '%".$_SESSION['search']."%' OR c.customers_email_address LIKE '%".$_SESSION['search']."%')";
  }
  
  if(isset($_GET['customer_sort_index'])) {
    $where .= " AND c.customers_lastname LIKE '".$_GET['customer_sort_index']."%'";
  }
  
  switch($_SESSION['customer_sort_order']) 
  {
		case (0):
			$order_by=" ORDER BY c.customers_lastname";
			break;
		case (1):
			$order_by=" ORDER BY c.customers_firstname, c.customers_lastname";
			break;
		case (2):
			$order_by=" ORDER BY c.customers_id, c.customers_lastname";
			break;
		case (3):
			$order_by=" ORDER BY gp.group_name, c.customers_lastname";
			break;
		case (4):
			$order_by=" ORDER BY r.pending_points DESC, c.customers_lastname";
			break;
		case (5):
			$order_by=" ORDER BY r.reward_points DESC, c.customers_lastname";
			break;
		case (6):
			$order_by=" ORDER BY o.date_purchased DESC, c.customers_lastname";
			break;
  }
  $group_by="";
  //$limit=" LIMIT ".REWARD_POINTS_CUSTOMER_LIMIT;
  $customer_query_raw = "select distinct c.customers_id, c.customers_lastname, c.customers_firstname, c.customers_group_pricing, r.pending_points, r.reward_points, gp.group_name, rm.redeem_ratio from ".TABLE_CUSTOMERS." as c LEFT JOIN (".TABLE_REWARD_CUSTOMER_POINTS." as r) ON (r.customers_id=c.customers_id) LEFT JOIN(".TABLE_GROUP_PRICING." as gp) ON (gp.group_id=c.customers_group_pricing) LEFT JOIN(".TABLE_REWARD_MASTER." as rm) ON ((c.customers_group_pricing!=0 AND rm.scope=".SCOPE_GROUP." AND rm.scope_id=c.customers_group_pricing) OR (c.customers_group_pricing=0 AND rm.scope=".SCOPE_GLOBAL." AND rm.scope_id=0))".$index.$where.$group_by.$order_by.$limit;
  //echo $customer_query_raw;
  $customers_split = new splitPageResults($_GET['page'], REWARD_POINTS_CUSTOMER_LIMIT, $customer_query_raw, $customers_query_numrows);
  $customers = $db->Execute($customer_query_raw);
  while (!$customers->EOF)
  {
	//$redeem_points=GetRewardPointRecord(SCOPE_GROUP,$customers->fields['customers_group_pricing']);
	if(isset($_GET['id']) && $customers->fields['customers_id']==$_GET['id'])
	{
		echo '    <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\''.zen_href_link(FILENAME_ADMIN_CUSTOMER_REWARD_POINTS, zen_get_all_get_params(array('id', 'action')) . 'id='.$customers->fields['customers_id'].'&action=edit').'\'">'."\n";
		$current_customer=$customers->fields;
	}
    else
	 echo '    <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . zen_href_link(FILENAME_ADMIN_CUSTOMER_REWARD_POINTS, zen_get_all_get_params(array('id', 'action')) . 'id='.$customers->fields['customers_id'].'&action=edit').'\'">'."\n";
?>              
				<td class="dataTableContent" align="center"><?php echo '<a href="' . zen_href_link(FILENAME_ORDERS, 'cID=' . $customers->fields['customers_id'], 'NONSSL') . '" >' . $customers->fields['customers_id'] . '</a>'; ?></td>
                <td class="dataTableContent"><?php echo $customers->fields['customers_lastname']; ?></td>
                <td class="dataTableContent"><?php echo $customers->fields['customers_firstname']; ?></td>
                <td class="dataTableContent"><?php echo (int)$customers->fields['pending_points']; ?></td>
                <td class="dataTableContent"><?php echo (int)$customers->fields['reward_points']; ?></td>
				<td class="dataTableContent"><?php echo CheckText($customers->fields['group_name'],TEXT_NONE); ?></td>
                <td class="dataTableContent"><?php echo $customers->fields['redeem_ratio']; ?></td>
                <td class="dataTableContent" align="right">
                  <?php echo '<a href="' . zen_href_link(FILENAME_ADMIN_CUSTOMER_REWARD_POINTS, zen_get_all_get_params(array('id', 'action')) . 'id=' . $customers->fields['customers_id'] . '&action=edit') . '">' . zen_image(DIR_WS_IMAGES . 'icon_edit.gif', ICON_EDIT) . '</a>'; ?>
                </td>
              </tr>
<?php
    $customers->MoveNext();
  }
  ?>
    <tr>
      <td colspan="10">
        <div style="display: inline-block; padding:5px 0; text-align: left; width: 49%;"><?php echo $customers_split->display_count($customers_query_numrows, REWARD_POINTS_CUSTOMER_LIMIT, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_CUSTOMERS); ?></div>
        <div style="display: inline-block; padding:5px 0; text-align: right; width: 49%;"><?php echo $customers_split->display_links($customers_query_numrows, REWARD_POINTS_CUSTOMER_LIMIT, MAX_DISPLAY_PAGE_LINKS, $_GET['page'], zen_get_all_get_params(array('page', 'info', 'x', 'y', 'cID'))); ?></div>
      </td>
    </tr>
  </table>
  
  <?php 
  $heading = array();
  $contents = array();
  if(zen_not_null($current_customer)) {  //line 343 suggested by lhungil
  switch ($action) 
  {
    case 'edit':
	case 'update':
	case 'handle_point_list':
		$TotalPointsActionList=array(array('id'=>0, 'text'=>''),array('id'=>'TransferAllToEarned', 'text'=>'Transfer all Pending to Earned'),array('id'=>'TransferAllToPending', 'text'=>'Transfer all Earned to Pending'),array('id'=>'ResetAllPending', 'text'=>'Reset all Pending Points'),array('id'=>'ResetAllEarned', 'text'=>'Reset all Earned Points'),array('id'=>'ResetAll', 'text'=>'Reset all Earned and Pending Points'));
		$GetVariablePointsActionList=array(array('id'=>0, 'text'=>''),array('id'=>'AddToEarned', 'text'=>'Add extra points to Earned'),array('id'=>'AddToPending', 'text'=>'Add extra points to Pending'),array('id'=>'SubtractFromEarned', 'text'=>'Subtract points from Earned'),array('id'=>'SubtractFromPending', 'text'=>'Subtract points from Pending'));

		$heading[] = array('text' => '<b>Reward Point Admin</b>');

		$contents = array('form' => zen_draw_form('customer_reward_point_admin', FILENAME_ADMIN_CUSTOMER_REWARD_POINTS, zen_get_all_get_params(array('id', 'action')) . 'id='.$current_customer['customers_id'].'&action=update', 'post'));
	    $contents[]=array('align' => 'center', 'text' => '<div class="pageHeading">'.$current_customer['customers_firstname'].' '.$current_customer['customers_lastname'].'</div>');
        $contents[]=array('text' => '<div id="prompt">Reward Points Pending:&nbsp;</div><div id="field"><div id="numfield">'.(int)$current_customer['pending_points'].'</div></div>');
        $contents[]=array('text' => '<div id="prompt">Value of Pending:&nbsp;</div><div id="field"><div id="numfield">'.$currencies->format($current_customer['pending_points']*$current_customer['redeem_ratio']).'</div></div>');
        $contents[]=array('text' => '<div id="prompt">Reward Points Earned:&nbsp;</div><div id="field"><div id="numfield">'.(int)$current_customer['reward_points'].'</div></div>');
		if(defined(MODULE_ORDER_TOTAL_REWARD_POINTS_STATUS) && MODULE_ORDER_TOTAL_REWARD_POINTS_STATUS==true)
		 $points_value=$currencies->format($current_customer['reward_points']*$current_customer['redeem_ratio']);
		else
		 if(defined(MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_STATUS) && MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_STATUS==true)
		 {
			$row=GetRewardPointDiscountRow($current_customer['reward_points']);
			if($row!=null)
			 $points_value=$row['discount'].'%';
			else
			 $points_value="0%";
		 }
        $contents[]=array('text' => '<div id="prompt">Value of Earned:&nbsp;</div><div id="field"><div id="numfield">'.$points_value.'</div></div>');
        $contents[]=array('text' => '<hr />');
        $contents[]=array('text' => '<strong>Set new Pricing Group/Redeem Ratio</strong>');
        $contents[]=array('align' => 'center','text' => zen_draw_pull_down_menu('customers_reward_group', GetPricingGroupList(), $current_customer['customers_group_pricing']));
        $contents[]=array('text' => '<hr />');
        $contents[]=array('text' => '<strong>Points Transfer or Reset</strong>');
        $contents[]=array('align' => 'center','text' => zen_draw_pull_down_menu('total_points_action', $TotalPointsActionList));
        $contents[]=array('text' => '<hr />');
        $contents[]=array('text' => '<strong>Add or Subtract Points</strong>');
        $contents[]=array('align' => 'center','text' => zen_draw_input_field('points','','size="8"').'&nbsp;'.zen_draw_pull_down_menu('variable_points_action', $GetVariablePointsActionList));
        $contents[]=array('text' => '<hr />');
		$contents[]=array('align' => 'center', 'text' => zen_image_submit('button_update.gif', IMAGE_UPDATE));
		$contents[]=array('text' => '</form>');

		$reward_records = $db->Execute("SELECT rewards_id, orders_id, date, reward_points, status FROM ".TABLE_REWARD_STATUS_TRACK." WHERE customers_id=".$current_customer['customers_id']." AND status=".STATUS_PENDING." ORDER BY date DESC LIMIT ".REWARD_POINTS_MAX_TRANSACTIONS.";");
		if($reward_records->RecordCount()>0)
		{
			$record_heading = array();
			$record_contents = array();
		
			$record_heading[] = array('text' => '<b>Pending Reward Points</b>');
			$record_contents = array('form' => zen_draw_form('reward_transactions', FILENAME_ADMIN_CUSTOMER_REWARD_POINTS, 'id='.$current_customer['customers_id'].'&action=handle_point_list', 'post'));
			$reward_point_table='<table width="90%" cellpadding="0px" cellspacing="0px" bgcolor="#d7d6cc"><thead bgcolor="a8a8a8"><tr><th align="left">Date</th><th>Order ID</th><th>Points</th><th>&nbsp;</th></tr><thead><tbody>'."\n";
			$count=0;
			while(!$reward_records->EOF)
			{
				$reward_point_table.='<tr><td>'.zen_date_short($reward_records->fields['date']).'</td><td align="right">'.$reward_records->fields['orders_id'].'</td><td align="right">'.(int)$reward_records->fields['reward_points'].'</td><td align="right"><input type=checkbox name="rewards_id_'.$count++.'" value="'.$reward_records->fields['rewards_id'].'"></td></tr>'."\n";
				$reward_records->MoveNext();
			}
			$RewardRecordsActionList=array(array('id'=>0, 'text'=>''),array('id'=>'TransferToEarned', 'text'=>'Transfer to Earned'),array('id'=>'DeleteRecords', 'text'=>'Delete Reward Points'));
			$reward_point_table.='</tbody><tfoot bgcolor="#e7e6e0"><tr><th align="right" colspan=4>Select all records:&nbsp;<input type=checkbox name="all" onChange="SetCheckboxes(\'reward_transactions\',\'rewards_id_\','.REWARD_POINTS_MAX_TRANSACTIONS.',this.checked)"></th></tr><tr><th align="right" colspan=4>Selected records:&nbsp;'.zen_draw_pull_down_menu('reward_point_table_action', $RewardRecordsActionList).'</th></tr></tfoot></table>';
			$record_contents[]=array('align'=>'center','text'=>$reward_point_table);
			$record_contents[]=array('align' => 'center', 'text' => zen_image_submit('button_confirm.gif', IMAGE_CONFIRM));
			$record_contents[]=array('text' => '</form>');
		}
		break;
     }  // End of switch statement
	 } else {
		echo '<div class="alert">No Customer (or invalid customer) selected. No action performed!</div>'; //line 407 and 408 suggested by lhungil
    }

  if((zen_not_null($heading)) && (zen_not_null($contents)))
  {
    echo '            <td width="25%" valign="top">' . "\n";

    $box = new box;
    echo $box->infoBox($heading, $contents);

	$record_box = new box;
    echo $record_box->infoBox($record_heading, $record_contents);

    echo '            </td>' . "\n";
  }
?>
          </tr>
        </table>
<!-- body_text_eof //-->
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
