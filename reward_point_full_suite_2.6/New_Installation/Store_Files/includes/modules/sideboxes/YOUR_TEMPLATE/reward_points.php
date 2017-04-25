<?php
/**
 * reward points sidebox - displays current reward points and previously earned reward points (if logged in).
 *
 * @package templateSystem
 * @copyright Copyright 2008 Andrew Moore
 * @copyright Portions Copyright 2003-2005 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: shopping_cart.php 3505 2006-04-24 04:00:05Z drbyte $
 */
	if(SHOW_REWARD_POINTS_BOX_OPTION==0 ||
	  (SHOW_REWARD_POINTS_BOX_OPTION==1 && isset($_SESSION['customer_id'])) ||
	  (SHOW_REWARD_POINTS_BOX_OPTION==2 && isset($_SESSION['customer_id']) && (GetCustomersRewardPoints($_SESSION['customer_id'])>0 || GetCustomersPendingPoints($_SESSION['customer_id'])>0)))
	{
		require($template->get_template_dir('tpl_reward_points.php',DIR_WS_TEMPLATE, $current_page_base,'sideboxes'). '/tpl_reward_points.php');
		$title =  BOX_REWARD_POINTS;
		$title_link = false;

		require($template->get_template_dir($column_box_default, DIR_WS_TEMPLATE, $current_page_base,'common') . '/' . $column_box_default);
	}
?>