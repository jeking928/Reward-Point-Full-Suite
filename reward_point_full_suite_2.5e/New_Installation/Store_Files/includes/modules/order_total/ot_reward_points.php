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

class ot_reward_points 
{
    var $title, $output;

    function ot_reward_points() 
	{
		$this->code = 'ot_reward_points';
		$this->title = MODULE_ORDER_TOTAL_REWARD_POINTS_TITLE;
		$this->description = MODULE_ORDER_TOTAL_REWARD_POINTS_DESCRIPTION;
		$this->sort_order = MODULE_ORDER_TOTAL_REWARD_POINTS_SORT_ORDER;
		$this->credit_class=true;
		
		$this->output = array();
    }

    function process() 
	{
		global $currencies,$order;

		if(isset($_SESSION['redeem_value']))
		{
			$_SESSION['redeem_points']=$this->get_points_redeeming();
			$_SESSION['redeem_value']=$_SESSION['redeem_points']*GetRedeemRatio($_SESSION['customer_id']);
			
			$order->info['total']=zen_round($order->info['total']-$_SESSION['redeem_value'], 2);
			
			$this->output[] = array('title' => MODULE_ORDER_TOTAL_REWARD_POINTS_VALUE,
									'text' => '-'.$currencies->format($_SESSION['redeem_value']),
									'value' => $_SESSION['redeem_value']);
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
			$check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_ORDER_TOTAL_REWARD_POINTS_STATUS'");
			$this->_check = $check_query->RecordCount();
		}

		return $this->_check;
    }

	function collect_posts() 
	{
//		if($_REQUEST['redeem_checkout_flag'])
		 if((isset($_REQUEST['redeem_flag']) && $_REQUEST['redeem_flag']) || (isset($_REQUEST['redeem_points']) && $_REQUEST['redeem_points']>0))
		 {
			$_SESSION['redeem_points']=$this->get_points_redeeming();
			$_SESSION['redeem_value']=$_SESSION['redeem_points']*GetRedeemRatio($_SESSION['customer_id']);
		 }
		 else
		  $this->clear_posts();
	}

	function use_credit_amount() 
	{
		return '';
	}

	function credit_selection() 
	{
		global $currencies,$order;

		$points_earned=GetCustomersRewardPoints($_SESSION['customer_id']);
		$redeem_ratio=GetRedeemRatio($_SESSION['customer_id']);
		if($points_earned>0)
		{
			$order_total=$this->get_order_total();
			$redeem_maximum=GetRewardPointsRedeemMaximum($order_total);
			$points_redeemable=($points_earned>$redeem_maximum?$redeem_maximum:$points_earned);
			$points_worth=$currencies->format($points_redeemable*$redeem_ratio);
			$points_earned_worth=$currencies->format($points_earned*$redeem_ratio);

			if($points_earned<REWARD_POINTS_REDEEM_MINIMUM)
			 $prompt=MODULE_ORDER_TOTAL_REWARD_POINTS_NOT_ENOUGH_POINTS;
			else
			 if($order_total<REWARD_POINTS_MINIMUM_VALUE)
			  $prompt=MODULE_ORDER_TOTAL_REWARD_POINTS_BELOW_MINIMUM_VALUE.'&nbsp'.$currencies->format(REWARD_POINTS_MINIMUM_VALUE);
			 else
			  if(MODULE_ORDER_TOTAL_REWARD_POINTS_TYPE=="Automatic")
			   $prompt=zen_draw_checkbox_field('redeem_flag',true,isset($_SESSION['redeem_value']),'').'&nbsp;'.zen_draw_hidden_field('redeem_checkout_flag',true);
			  else
			   $prompt=zen_draw_input_field('redeem_points',$_SESSION['redeem_points']).'&nbsp;'.zen_draw_hidden_field('redeem_checkout_flag',true);
			 
			$selection = array('id' => $this->code,
							   'module' => $this->title,
							   'redeem_instructions' => MODULE_ORDER_TOTAL_REWARD_POINTS_REDEEM_INSTRUCTIONS.'<br /><br />'.
														LABEL.MODULE_ORDER_TOTAL_REWARD_POINTS_EARNED.UNLABEL.FIELD.$points_earned.'&nbsp;'.CUSTOMER_EARNED_POINT_TAG.'&nbsp;('.$points_earned_worth.')'.UNFIELD.'<br class="clearBoth" />'.
														(REWARD_POINTS_REDEEM_MINIMUM>0 && $points_earned<REWARD_POINTS_REDEEM_MINIMUM?LABEL.MODULE_ORDER_TOTAL_REWARD_POINTS_MINIMUM.UNLABEL.FIELD.REWARD_POINTS_REDEEM_MINIMUM.'&nbsp;'.MINIMUM_POINT_LEVEL_TAG.UNFIELD.'<br class="clearBoth" />':'').
														(REWARD_POINTS_REDEEM_MAXIMUM>0 && $points_earned>REWARD_POINTS_REDEEM_MAXIMUM?LABEL.MODULE_ORDER_TOTAL_REWARD_POINTS_MAXIMUM.UNLABEL.FIELD.$redeem_maximum.'&nbsp;'.MAXIMUM_POINT_LEVEL_TAG.UNFIELD.'<br class="clearBoth" />':'').
														LABEL.MODULE_ORDER_TOTAL_REWARD_POINTS_VALUE.UNLABEL.FIELD.$points_worth.'&nbsp;('.$points_redeemable.')'.UNFIELD.'<br class="clearBoth" />',
							   'checkbox' => $this->use_credit_amount(),
							   'fields' => array(array('title' => MODULE_ORDER_TOTAL_REWARD_POINTS_USE_PROMPT,'field' => FIELD.$prompt.UNFIELD,'tag' => "redeem_flag")));
							   
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
		 return $order->info['subtotal']+GetRewardPointAdvancedCalculateValue();
		else
		 return $order->info['total']+GetRewardPointAdvancedCalculateValue();
	}
	
	function pre_confirmation_check($order_total) 
	{
		$order_total=$this->get_order_total();
		if($_SESSION['redeem_value'])
		 if($_SESSION['redeem_value']>$order_total)
		  return $order_total;
		 else
		  return $_SESSION['redeem_value'];
		  
		return 0;
	}

	function get_points_redeeming()
	{
		if(MODULE_ORDER_TOTAL_REWARD_POINTS_TYPE=="Automatic")
		 $points_redeeming=GetCustomersRewardPoints($_SESSION['customer_id']);
		else
		 if(isset($_POST['redeem_points']))
		  $points_redeeming=$_POST['redeem_points'];
		 else
		  $points_redeeming=$_SESSION['redeem_points'];
		 
		$order_total=$this->get_order_total();
		$points_earned=GetCustomersRewardPoints($_SESSION['customer_id']);
		if($points_redeeming>$points_earned)
		 $points_redeeming=$points_earned;
		$redeem_maximum=GetRewardPointsRedeemMaximum($order_total);
		if($points_redeeming>$redeem_maximum)
		 $points_redeeming=$redeem_maximum;
		 
		return $points_redeeming;
	}
	
	function apply_credit() 
	{
		global $messageStack;
		
		if($_SESSION['redeem_value'])
		{
			UpdateCustomerRewardPoints($_SESSION['customer_id'],-$_SESSION['redeem_points'],0);
			$messageStack->add_session('checkout_success', "Customer ID:".$_SESSION['customer_id']." Points Redeemed:".$_SESSION['redeem_points'], 'success');
			$_SESSION['redeemed_value']=$_SESSION['redeem_value'];
			unset($_SESSION['redeem_value']);
			unset($_SESSION['redeem_points']);
		}
	}
	
    function keys() 
	{
		return array('MODULE_ORDER_TOTAL_REWARD_POINTS_STATUS','MODULE_ORDER_TOTAL_REWARD_POINTS_TYPE', 'MODULE_ORDER_TOTAL_REWARD_POINTS_SORT_ORDER');
    }

    function install() 
	{
		global $db;
		
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('This module is installed', 'MODULE_ORDER_TOTAL_REWARD_POINTS_STATUS', 'true', '', '6', '1','zen_cfg_select_option(array(\'true\'), ', now())");
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ORDER_TOTAL_REWARD_POINTS_SORT_ORDER', '900', 'Sort order of display.', '6', '2', now())");
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Point Redeem Method', 'MODULE_ORDER_TOTAL_REWARD_POINTS_TYPE', 'Automatic', 'Select how the customer redeems their points:<br /><strong>Automatic</strong> will select all Reward Points earned by the customer.<br /><strong>Manual</strong> will allow the customer to enter a number of points to redeem.<br />In both cases the total number allowed to redeem is capped by the order total and the \'Reward Point Redeem Maximum\' configuration setting.', '6', '3','zen_cfg_select_option(array(\'Automatic\',\'Manual\'), ', now())");
		// $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Redeem Ratio', 'MODULE_ORDER_TOTAL_REWARD_POINTS_REDEEM_RATIO', '0.01', 'Amount given per Reward Point.', '6', '3', now())");
		
		$ot_module=DIR_FS_CATALOG_MODULES.'order_total/'."ot_reward_points_discount.php";
        if(file_exists($ot_module))
		{
			include($ot_module);
			$module=new ot_reward_points_discount;
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