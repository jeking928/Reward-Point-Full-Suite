<?php
/**
 * Side Box Template for a shopping cart with reward points
 *
 * @package templateSystem
 * @copyright Copyright 2008 Andrew Moore
 * @copyright Portions Copyright 2003-2005 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */
  $content ="";
  
  $content .= '<div id="' . str_replace('_', '-', $box_id . 'Content') . '" class="sideBoxContent">';
  if ($_SESSION['cart']->count_contents() > 0) {
  $content .= '<div id="cartBoxListWrapper">' . "\n" . '<ul>' . "\n";
    $products = $_SESSION['cart']->get_products();
    $reward_points=GetRewardPoints($products);
    for ($i=0, $n=sizeof($products); $i<$n; $i++) {
      $content .= '<li>';
/*
//	  if($products[$i]['products_priced_by_attribute'])
	   $reward_points+=(GetRewardPoints($products[$i]['id'],$products[$i]['attributes'])*$products[$i]['quantity']);
//	  else
//	   $reward_points+=(GetRewardPoints($products[$i]['id'])*$products[$i]['quantity']);
*/	  
      if (($_SESSION['new_products_id_in_cart']) && ($_SESSION['new_products_id_in_cart'] == $products[$i]['id'])) {
        $content .= '<span class="cartNewItem">';
      } else {
        $content .= '<span class="cartOldItem">';
      }

      $content .= $products[$i]['quantity'] . BOX_SHOPPING_CART_DIVIDER . '</span><a href="' . zen_href_link(zen_get_info_page($products[$i]['id']), 'products_id=' . $products[$i]['id']) . '">';

      if (($_SESSION['new_products_id_in_cart']) && ($_SESSION['new_products_id_in_cart'] == $products[$i]['id'])) {
        $content .= '<span class="cartNewItem">';
      } else {
        $content .= '<span class="cartOldItem">';
      }

      $content .= $products[$i]['name'] . '</span></a></li>' . "\n";

      if (($_SESSION['new_products_id_in_cart']) && ($_SESSION['new_products_id_in_cart'] == $products[$i]['id'])) {
        $_SESSION['new_products_id_in_cart'] = '';
      }
    }
    $content .= '</ul>' . "\n" . '</div>';
  } else {
    $content .= '<div id="cartBoxEmpty">' . BOX_SHOPPING_CART_EMPTY . '</div>';
  }

  if ($_SESSION['cart']->count_contents() > 0) {
    $content .= '<hr />';
    $content .= '<div class="cartBoxTotal">' . $currencies->format($_SESSION['cart']->show_total()) . '</div>';
	$content .= '<div class="cartBoxRewardPoints">' . (int)$reward_points . '&nbsp;' . PRODUCT_REWARD_POINT_TAG . '</div>';
    $content .= '<br class="clearBoth" />';
  }

  if (isset($_SESSION['customer_id'])) {
    $gv_query = "select amount
                 from " . TABLE_COUPON_GV_CUSTOMER . "
                 where customer_id = '" . $_SESSION['customer_id'] . "'";
   $gv_result = $db->Execute($gv_query);

    if ($gv_result->RecordCount() && $gv_result->fields['amount'] > 0 ) {
      $content .= '<div id="cartBoxGVButton"><a href="' . zen_href_link(FILENAME_GV_SEND, '', 'SSL') . '">' . zen_image_button(BUTTON_IMAGE_SEND_A_GIFT_CERT , BUTTON_SEND_A_GIFT_CERT_ALT) . '</a></div>';
      $content .= '<div id="cartBoxVoucherBalance">' . VOUCHER_BALANCE . $currencies->format($gv_result->fields['amount']) . '</div>';
    }
  }
  $content .= '</div>';
?>