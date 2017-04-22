<?php
/**
 * Side Box Template for reward points
 *
 * @package templateSystem
 * @copyright Copyright 2008 Andrew Moore
 * @copyright Portions Copyright 2003-2005 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */
	$reward_points=GetRewardPoints($_SESSION['cart']->get_products());
  
	$warning='';
	$content='<div id="'.str_replace('_', '-', $box_id . 'Content').'" class="sideBoxContent">';
/*
	foreach($_SESSION['cart']->get_products() as $product)
//	 if($products[$i]['products_priced_by_attribute'])
	  $reward_points+=GetRewardPoints($product['id'],$product['attributes'])*$product['quantity'];
//	 else
//	  $reward_points+=GetRewardPoints($product['id'])*$product['quantity'];
*/  
	if($reward_points>0)
	 $content.='<div class="cartBoxRewardPoints">'.(int)$reward_points.'&nbsp;'.REWARD_POINTS_IN_CART_TAG.'</div>';
	else
	 $warning.=NO_REWARD_POINTS_IN_CART_TAG;
	
    if(isset($_SESSION['customer_id']))
	 $content.='<div class="cartBoxEarnedPoints">'.GetCustomersRewardPoints($_SESSION['customer_id']).'&nbsp;'.CUSTOMER_EARNED_POINT_TAG.'</div><div class="cartBoxPendingPoints">'.GetCustomersPendingPoints($_SESSION['customer_id']).'&nbsp;'.CUSTOMER_PENDING_POINT_TAG.'</div>';
	else
	 $warning.=($warning?'<br />':'').CUSTOMER_NOT_LOGGED_IN_TAG;
	 
	if($warning)
	 $content.='<div id="cartBoxEmpty">'.$warning.'</div>';
	 
    $content.='</div>'; 
?>