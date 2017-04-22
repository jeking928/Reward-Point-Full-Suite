<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2008 Andrew Moore                                      |
// |                                                                      |   
// | http://www.zen-cart.com/index.php                                    |   
// |                                                                      |   
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

class ot_reward_points_display 
{
    var $title, $output;

    function ot_reward_points_display() 
	{
		$this->code = 'ot_reward_points_display';
		$this->title = MODULE_ORDER_TOTAL_REWARD_POINTS_DISPLAY_TITLE;
		$this->description = MODULE_ORDER_TOTAL_REWARD_POINTS_DISPLAY_DESCRIPTION;
		$this->sort_order = MODULE_ORDER_TOTAL_REWARD_POINTS_DISPLAY_SORT_ORDER;
		
		$this->output = array();
    }

    function process() 
	{
		global $order;

		$reward_points=GetRewardPoints($order->products);
		
		$GlobalRewardPointRatio=GetGlobalRewardPointRatio();
		$AdjustValue=GetRewardPointAdvancedCalculateValue();
		$reward_points+=$AdjustValue*$GlobalRewardPointRatio;
		
		if(isset($_SESSION['redeem_value']))
		{
			/*
			$redeem_ratio=GetRedeemRatio($_SESSION['customer_id']);
			$reward_points=$reward_points/$redeem_ratio;
			$reward_points=$reward_points-$_SESSION['redeem_value'];
			$reward_points=$reward_points*$redeem_ratio;
			*/
			$reward_points=$reward_points-($GlobalRewardPointRatio*$_SESSION['redeem_value']);
		}

		if($reward_points<0)
		 $reward_points=0;
		
		$reward_points=zen_round($reward_points,0);
		$_SESSION['REWARD_POINTS_EARNED']=$reward_points;
		 
		$this->output[] = array('title' => $this->title . ':',
                              'text' => $reward_points,
                              'value' => 0);
    }

    function check() 
	{
		global $db;
		if (!isset($this->_check)) 
		{
			$check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_ORDER_TOTAL_REWARD_POINTS_DISPLAY_STATUS'");
			$this->_check = $check_query->RecordCount();
		}

		return $this->_check;
    }

    function keys() 
	{
		return array('MODULE_ORDER_TOTAL_REWARD_POINTS_DISPLAY_STATUS', 'MODULE_ORDER_TOTAL_REWARD_POINTS_DISPLAY_SORT_ORDER');
    }

    function install() 
	{
		global $db;
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('This module is installed', 'MODULE_ORDER_TOTAL_REWARD_POINTS_DISPLAY_STATUS', 'true', '', '6', '1','zen_cfg_select_option(array(\'true\',\'false\'), ', now())");
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ORDER_TOTAL_REWARD_POINTS_DISPLAY_SORT_ORDER', '1000', 'Sort order of display.', '6', '2', now())");
    }

    function remove() 
	{
		global $db;
		$db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }
}
?>