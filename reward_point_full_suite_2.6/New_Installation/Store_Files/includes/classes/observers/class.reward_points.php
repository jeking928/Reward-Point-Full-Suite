<?php
/**
 * Observer class used to handle reward points in an order
 *
 */
class RewardPoints extends base 
{
 function RewardPoints()
 {
  global $zco_notifier;
  $zco_notifier->attach($this, array('NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_HEADER','NOTIFY_MODULE_CREATE_ACCOUNT_ADDED_CUSTOMER_RECORD','NOTIFY_LOGIN_SUCCESS'));
 }
 
 function update(&$class, $eventID, $paramsArray) 
 {
  if(isset($_SESSION['customer_id']))
   $customer_id=(int)$_SESSION['customer_id'];
  else 
   if(isset($paramsArray['customer_id']))
    $customer_id=(int)$paramsArray['customer_id'];
   else
    error_log("Customer ID not passed to Reward Point observer. EventID=".$eventID."\r\n");
	
  //error_log("Customer ID=".$customer_id.". EventID=".$eventID.". Reward Points=".REWARD_POINTS_NEW_ACCOUNT_REWARD."\r\n");
	
  switch($eventID)
  {
   //case NOTIFY_CHECKOUT_PROCESS_AFTER_SEND_ORDER_EMAIL:
   //case NOTIFY_CHECKOUT_PROCESS_BEGIN:
   //case NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_CREATE_ADD_PRODUCTS:
   //case NOTIFY_ORDER_AFTER_ORDER_CREATE_ADD_PRODUCTS:
   case NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_HEADER:
     $this->StoreRewardPoints($customer_id);
     break;
   
   case NOTIFY_MODULE_CREATE_ACCOUNT_ADDED_CUSTOMER_RECORD:
    if(REWARD_POINTS_NEW_ACCOUNT_REWARD!=0)
     if(REWARD_POINTS_NEW_ACCOUNT_REWARD>0 && !HasRewardPoints($customer_id))
      $this->AddRewardPoints($customer_id,REWARD_POINTS_NEW_ACCOUNT_REWARD);
    else
     if(REWARD_POINTS_NEW_ACCOUNT_REWARD<0 && !HasPendingPoints($customer_id))
      $this->AddPendingPoints($customer_id,abs(REWARD_POINTS_NEW_ACCOUNT_REWARD));
   
   case NOTIFY_LOGIN_SUCCESS:
    if(isset($customer_id) && REWARD_POINTS_SUNRISE_PERIOD>0)
     $this->UpdateRewardStatus($customer_id);
    break;
  }
 }
 
 function after_checkout($customer_id)
 {
	StoreRewardPoints($customer_id);
 }
 
 function AddRewardPoints($customer_id,$reward_points)
 {
	UpdateCustomerRewardPoints($customer_id,$reward_points,0);
 }

 function AddPendingPoints($customer_id,$pending_points)
 {
	UpdateCustomerRewardPoints($customer_id,0,$pending_points);
 }
 
 function StoreRewardPoints($customer_id)
 {
  global $db,$order;
  
  //$reward_points=GetRewardPoints($order->products);
  if(isset($_SESSION['REWARD_POINTS_EARNED']))
  {
	$reward_points=(int)$_SESSION['REWARD_POINTS_EARNED'];
    unset($_SESSION['REWARD_POINTS_EARNED']);
	
//  foreach($order->products as $product)
//   $reward_points+=GetRewardPoints($product['id'],$product['attributes'])*$product['qty'];
   
	if(isset($_SESSION['redeemed_value']))
	{
		$reward_ratio=GetGlobalRewardPointRatio();
		$reward_points=$reward_points-($reward_ratio*$_SESSION['redeemed_value']);
		unset($_SESSION['redeemed_value']);
	}

	$reward_points=zen_round($reward_points,0);

	if($reward_points>0)
	{
	  if(REWARD_POINTS_ALLOW_TOTAL=='0')
	   $status=($order->info['subtotal']>0?STATUS_PENDING:STATUS_PROCESSED);
	  else
	   $status=($order->info['total']>0?STATUS_PENDING:STATUS_PROCESSED);
	
	  if($status==STATUS_PENDING) // Place reward points into pending if awaiting payment/status change
	   $sql="INSERT INTO ".TABLE_REWARD_CUSTOMER_POINTS." SET customers_id='".$customer_id."', pending_points='".$reward_points."' ON DUPLICATE KEY UPDATE pending_points=pending_points+".$reward_points.";";
	  else // Reward points and or coupons have covered the price of the purchase- place into processed
	   $sql="INSERT INTO ".TABLE_REWARD_CUSTOMER_POINTS." SET customers_id='".$customer_id."', reward_points='".$reward_points."' ON DUPLICATE KEY UPDATE reward_points=reward_points+".$reward_points.";";
	  $db->Execute($sql);
	
	  $sql="REPLACE INTO ".TABLE_REWARD_STATUS_TRACK." SET customers_id='".$customer_id."', orders_id='".GetCustomersLastOrderID($customer_id)."', date=NOW(), reward_points='".$reward_points."', status=".$status.";";
	  $db->Execute($sql);
	}
  }
  else
   error_log("StoreRewardPoints called but SESSION['REWARD_POINTS_EARNED'] not found");
   
//  RefreshCustomerPointTable((int)$_SESSION['customer_id']);
 }
 
 function UpdateRewardStatus($customer_id)
 {
  global $db;
  
   if(($result=$db->Execute("SELECT SUM(reward_points) FROM ".TABLE_REWARD_STATUS_TRACK." WHERE customers_id=".$customer_id." AND status=".STATUS_PENDING." AND date<NOW()-INTERVAL ".REWARD_POINTS_SUNRISE_PERIOD." DAY;")))
   {
   $db->Execute("UPDATE ".TABLE_REWARD_STATUS_TRACK." SET status=".STATUS_PROCESSED." WHERE customers_id=".$customer_id." AND status=".STATUS_PENDING." AND date<NOW()-INTERVAL ".REWARD_POINTS_SUNRISE_PERIOD." DAY;");
   if($points=$result->fields['SUM(reward_points)'])
    UpdateCustomerRewardPoints($customer_id,$points,-$points);
   }
 }
}

function HasRewardPoints($customer_id)
{
	return (GetCustomersRewardPoints($customer_id)!=0);
}

function HasPendingPoints($customer_id)
{
	return (GetCustomersPendingPoints($customer_id)!=0);
}
?>