<?php
/**
 * Module Template
 *
 * @package templateSystem
 * @copyright Copyright 2008 Andrew Moore
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */
	if(isset($_REQUEST['products_id']) || isset($_GET['products_id']))
	{
		if(isset($_REQUEST['products_id']))
		 $products_id=$_REQUEST['products_id'];
		else
		 $products_id=$_GET['products_id'];

		$reward_points=(int)GetProductRewardPoints($products_id);

		if(REWARD_POINTS_ALWAYS_DISPLAY=='1' || $reward_points>0)
		 echo $reward_points.'&nbsp;'.PRODUCT_REWARD_POINT_TAG;
	}
	else
	 echo 'Product ID not found!!!';
?>