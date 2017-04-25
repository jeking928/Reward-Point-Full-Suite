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

class ot_reward_points_discount 
{
    var $title, $output;

    function ot_reward_points_discount() 
	{
		$this->code = 'ot_reward_points_discount';
		$this->title = MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_TITLE;
		$this->description = MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_DESCRIPTION;
		$this->sort_order = MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_SORT_ORDER;
		$this->credit_class=true;
		
		if($_REQUEST['redeem_checkout_flag'])
		 if($_REQUEST['redeem_flag'])
		 {
			$_SESSION['redeem_value']=0;
			$_SESSION['redeem_points']=0;
		}
		 else
		 {
		  unset($_SESSION['redeem_value']);
		  unset($_SESSION['redeem_points']);
		 }
		  
		$this->output = array();
    }

    function process() 
	{
		global $currencies,$order;

		if(isset($_SESSION['redeem_value']))
		{
			$points_redeeming=GetCustomersRewardPoints($_SESSION['customer_id']);
			$redeem_discount=$this->GetRedeemDiscount($_SESSION['customer_id'],$points_redeeming);
			$points_redeeming=$this->GetRedeemDiscountPointsRequired($_SESSION['customer_id'],$points_redeeming);
			
			$order_total=$this->get_order_total();
			if(MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_TYPE=='0')
			 $order_discount=$order_total*$redeem_discount/100;
			else
			 $order_discount=$redeem_discount;
			
			$order->info['total']=zen_round($order->info['total']-$order_discount, 2);
			
			$this->output[] = array('title' => MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_VALUE.' ('.zen_round($redeem_discount*100, 2).'%)',
									'text' => '-'.$currencies->format($order_discount),
									'value' => $order_discount);
									
			$_SESSION['redeem_value']=$order_discount;
			$_SESSION['redeem_points']=$points_redeeming;

		}
		//else
		 //$this->clear_posts();
    }

	function clear_posts() 
	{
		unset($_SESSION['redeem_value']);
		unset($_SESSION['redeem_points']);
	}

    function check() 
	{
		global $db;
		if (!isset($this->_check)) 
		{
			$check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_STATUS'");
			$this->_check = $check_query->RecordCount();
		}

		return $this->_check;
    }

	function collect_posts() 
	{
	}

	function use_credit_amount() 
	{
		return '';
	}

	function credit_selection() 
	{
		global $currencies,$order;

		$points_earned=GetCustomersRewardPoints($_SESSION['customer_id']);
		$redeem_discount=$this->GetRedeemDiscount($_SESSION['customer_id'],$points_earned);

		if($redeem_discount>0)
		{
			$order_total=$this->get_order_total()+GetRewardPointAdvancedCalculateValue();
			if(MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_TYPE=='0')
			 $order_discount=$order_total*$redeem_discount/100;
			else
			 $order_discount=$redeem_discount;

			$prompt=zen_draw_checkbox_field('redeem_flag',true,isset($_SESSION['redeem_value'])).'&nbsp;'.zen_draw_hidden_field('redeem_checkout_flag',true);
			 
			$selection = array('id' => $this->code,
							   'module' => $this->title,
							   'redeem_instructions' => MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_REDEEM_INSTRUCTIONS.'<br /><br />'.
														(MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_TYPE=='0'?LABEL.MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_EARNED.UNLABEL.FIELD.$redeem_discount.'%'.UNFIELD.'<br class="clearBoth" />':'').
														LABEL.MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_VALUE.UNLABEL.FIELD.$currencies->format($order_discount).UNFIELD.'<br class="clearBoth" />',
							   'checkbox' => $this->use_credit_amount(),
							   'fields' => array(array('title' => MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_USE_PROMPT,'field' => FIELD.$prompt.UNFIELD,'tag' => "redeem_flag")));
							   
			return $selection;
		}
		return null;
	}

	function update_credit_account($i)
	{
	}
	
	function get_order_total() 
	{
		global $order;
		if(REWARD_POINTS_ALLOW_TOTAL=='0')
		 return $order->info['subtotal'];
		else
		 return $order->info['total'];
	}
	
	function GetRedeemDiscount($customer_id,$reward_points)
	{
		$discount=GetRewardPointDiscountRow($reward_points);
		if($discount!=NULL)
		 return $discount['discount'];
		 
		return 0;
	}

	function GetRedeemDiscountPointsRequired($customer_id,$reward_points)
	{
		$discount=GetRewardPointDiscountRow($reward_points);
		if($discount!=NULL)
		 return $discount['required'];
		 
		return 0;
	}

	function pre_confirmation_check($order_total) 
	{
		if($_SESSION['redeem_value'])
		 if($_SESSION['redeem_value']>$this->get_order_total())
		  return $this->get_order_total();
		 else
		  return $_SESSION['redeem_value'];
		  
		return 0;
	}
	
	function apply_credit() 
	{
		global $messageStack;
		
		if($_SESSION['redeem_value'])
		{
			//error_log("Reward Point Discount apply credit called. customer_id=".$_SESSION['customer_id'].", redeem_points=".$_SESSION['redeem_points']."\r\n");
			
			UpdateCustomerRewardPoints($_SESSION['customer_id'],-$_SESSION['redeem_points'],0);
			$messageStack->add_session('checkout_success', "Customer ID:".$_SESSION['customer_id']." Points Redeemed:".$_SESSION['redeem_points'], 'success');
			$_SESSION['redeemed_value']=$_SESSION['redeem_value'];
			
			unset($_SESSION['redeem_value']);
			unset($_SESSION['redeem_points']);
		}
	}
	
    function keys() 
	{
		return array('MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_STATUS', 'MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_SORT_ORDER','MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_TYPE','MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_TABLE');
    }

    function install() 
	{
		global $db;
		
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('This module is installed', 'MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_STATUS', 'true', '', '6', '1','zen_cfg_select_option(array(\'true\'), ', now())");
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_SORT_ORDER', '900', 'Sort order of display.', '6', '2', now())");
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Discount Type', 'MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_TYPE', '0', 'Select the type of discount:', '6', '3', 'UseRewardPointDiscountTypeFunction', 'zen_cfg_select_drop_down(array(array(''id''=>''0'', ''text''=>''Percentage discount''), array(''id''=>''1'', ''text''=>''Cash discount'')), ', now())");
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Discount Table', 'MODULE_ORDER_TOTAL_REWARD_POINTS_DISCOUNT_TABLE', '5:5000,10:10000,15:15000,20:20000,30:30000,40:40000', 'Table of discounts and required Reward Points.', '6', '4', 'UseRewardPointDiscountTableFunction', 'SetRewardPointDiscountTableFunction(', now())");

		$ot_module=DIR_FS_CATALOG_MODULES.'order_total/'."ot_reward_points.php";
        if(file_exists($ot_module))
		{
			include($ot_module);
			$module=new ot_reward_points;
			$module->remove();
		}
    }

    function remove() 
	{
		global $db;
		$db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }
}
?>