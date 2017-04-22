<?php
/**
 * @package admin
 * @copyright Copyright 2008 Andrew Moore
 * @copyright Portions Copyright 2003-2006 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: reward_points.php $
 */

	require('includes/application_top.php');

	require(DIR_WS_MODULES . 'prod_cat_header_code.php');

	$action=(isset($_GET['action']) ? $_GET['action'] : '');

	if(!isset($_SESSION['categories_products_sort_order']))
	 $_SESSION['categories_products_sort_order'] = CATEGORIES_PRODUCTS_SORT_ORDER;
  
	if(!isset($_GET['reset_categories_products_sort_order']))
     $reset_categories_products_sort_order = $_SESSION['categories_products_sort_order'];

	if (zen_not_null($action)) 
	{
		switch ($action) 
		{
			case 'set_categories_products_sort_order':
				$_SESSION['categories_products_sort_order']=$_GET['reset_categories_products_sort_order'];
				$action='';
				zen_redirect(zen_href_link(FILENAME_ADMIN_REWARD_POINTS,  'cPath=' . $_GET['cPath'] . ((isset($_GET['pID']) and !empty($_GET['pID'])) ? '&pID=' . $_GET['pID'] : '') . ((isset($_GET['page']) and !empty($_GET['page'])) ? '&page=' . $_GET['page'] : '')));
				break;
				
			case 'set_reward_points':
				if(isset($_GET['pID']))
				 UpdateRewardPointRecord(SCOPE_PRODUCT,$_GET['pID'],$_POST['reward_point_ratio']);
				else
				 if(isset($_GET['cID']))
				  UpdateRewardPointRecord(SCOPE_CATEGORY,$_GET['cID'],$_POST['reward_point_ratio']);
				 else
				  UpdateRewardPointRecord(SCOPE_GLOBAL,0,$_POST['reward_point_ratio']);
				break;
				
			case 'clear_reward_points':
				if(isset($_GET['pID']))
				 DeleteRewardPointRecord(SCOPE_PRODUCT,$_GET['pID']);
				else
				 if(isset($_GET['cID']))
				  DeleteRewardPointRecord(SCOPE_CATEGORY,$_GET['cID']);
				 else
				  DeleteRewardPointRecord(SCOPE_GLOBAL);
				break;
		}
	}

	// check if the catalog image directory exists
	if(!is_dir(DIR_FS_CATALOG_IMAGES))
     $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST, 'error');
	else
	 if (!is_writeable(DIR_FS_CATALOG_IMAGES))
	  $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE, 'error');
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
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
    kill.disabled = true;
  }
  if (typeof _editor_url == "string") HTMLArea.replaceAll();
}
// -->
</script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onLoad="init()">
<div id="spiffycalendar" class="text"></div>
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->

<table border="0" width="100%" cellspacing="0" cellpadding="0">
 <tr height="40px">
  <td class="pageHeading"><?php echo HEADING_TITLE.'&nbsp;-&nbsp;'.zen_output_generated_category_path($current_category_id); ?></td>
  <td class="smallText" align="right">
<?php
	// Set edit dialog box and add Global Reward Point settings
	
	$heading = array();
	$contents = array();
	
    $heading[]=array('text' => '<b>'.BOX_REWARD_POINTS.'</b>');

	if(isset($_GET['pID']))
	{
		$reward_point_record=GetRewardPointRecord(SCOPE_PRODUCT,$_GET['pID']);
		$header=TEXT_PRODUCT_REWARD_POINTS_HEADER;
		$ident=zen_get_products_name($_GET['pID'],$_SESSION['languages_id']);
		$prompt=TEXT_PRODUCT_REWARD_POINT_PROMPT;
		$passvar="&pID=".$_GET['pID'];
	}
	else
	 if(isset($_GET['cID']))
	 {
		$reward_point_record=GetRewardPointRecord(SCOPE_CATEGORY,$_GET['cID']);
		$header=TEXT_CATEGORY_REWARD_POINTS_HEADER;
		$ident=zen_get_category_name($_GET['cID'],$_SESSION['languages_id']);
		$prompt=TEXT_CATEGORY_REWARD_POINT_PROMPT;
		$passvar="&cID=".$_GET['cID'];
	 }
	 else
	 {
		$reward_point_record=GetRewardPointRecord(SCOPE_GLOBAL);
		$header=TEXT_GLOBAL_REWARD_POINTS_HEADER;
		$ident="";
		$prompt=TEXT_GLOBAL_REWARD_POINT_PROMPT;
		$passvar="";
	 }
	 
		
	$contents=array('form' => zen_draw_form('set_reward_points', FILENAME_ADMIN_REWARD_POINTS, 'cPath='.$cPath.$passvar.'&action=set_reward_points','post', 'enctype="multipart/form-data"'));
	$contents[]=array('text' => '<b>'.$header.'<b><br />');
	$contents[]=array('align' => 'center', 'text' => '<div class="pageHeading">'.$ident.'</div><br />');
	$contents[]=array('text' => $prompt.'&nbsp;'.zen_draw_input_field('reward_point_ratio',$reward_point_record->fields[FIELD_POINT_RATIO],zen_set_field_length(TABLE_REWARD_MASTER,FIELD_POINT_RATIO)).'<br /><br />');
	$contents[]=array('align' => 'center', 'text' => ($reward_point_record->fields?zen_image_submit('button_update.gif', IMAGE_UPDATE):zen_image_submit('button_save.gif', IMAGE_SAVE)));
	$contents[]=array('text' => '</form>');

	// check for which buttons to show for categories and products
	$check_categories = zen_has_category_subcategories($current_category_id);
	$check_products = zen_products_in_category_count($current_category_id, false, false, 1);

	$zc_skip_products = false;
	$zc_skip_categories = false;

	if($check_products == 0) 
	{
        $zc_skip_products = false;
        $zc_skip_categories = false;
    }
    if ($check_categories == true) 
	{
        $zc_skip_products = true;
        $zc_skip_categories = false;
    }
    if ($check_products > 0) 
	{
        $zc_skip_products = false;
        $zc_skip_categories = true;
    }

    if ($zc_skip_products == true) 
     $categories_products_sort_order_array = array(array('id' => '0', 'text' => TEXT_SORT_CATEGORIES_SORT_ORDER_PRODUCTS_NAME),array('id' => '1', 'text' => TEXT_SORT_CATEGORIES_NAME));
    else // toggle switch for display sort order
	 $categories_products_sort_order_array=array(array('id' => '0', 'text' => TEXT_SORT_PRODUCTS_SORT_ORDER_PRODUCTS_NAME),array('id' => '1', 'text' => TEXT_SORT_PRODUCTS_NAME),array('id' => '2', 'text' => TEXT_SORT_PRODUCTS_MODEL),);

	echo TEXT_CATEGORIES_PRODUCTS_SORT_ORDER_INFO . zen_draw_form('set_categories_products_sort_order_form', FILENAME_ADMIN_REWARD_POINTS, '', 'get') . '&nbsp;&nbsp;' . zen_draw_pull_down_menu('reset_categories_products_sort_order', $categories_products_sort_order_array, $reset_categories_products_sort_order, 'onChange="this.form.submit();"') . zen_hide_session_id() .
            zen_draw_hidden_field('cID', $cPath) .
            zen_draw_hidden_field('cPath', $cPath) .
            zen_draw_hidden_field('pID', $_GET['pID']) .
            zen_draw_hidden_field('page', $_GET['page']) .
            zen_draw_hidden_field('action', 'set_categories_products_sort_order');
	echo '</form>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

	echo zen_draw_form('search', FILENAME_ADMIN_REWARD_POINTS,'','get');
	echo HEADING_TITLE_SEARCH_DETAIL.'&nbsp;'.zen_draw_input_field('search',zen_db_input(zen_db_prepare_input($_GET['search']))).zen_hide_session_id();
	echo '</form>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

	echo zen_draw_form('goto',FILENAME_ADMIN_REWARD_POINTS,'','get').zen_hide_session_id();
	echo HEADING_TITLE_GOTO.'&nbsp;'.zen_draw_pull_down_menu('cPath',zen_get_category_tree(),$current_category_id,'onChange="this.form.submit();"');
	echo '</form>';
?>
  </td>
 </tr>
 <tr height="24px" valign="middle">
  <td>
<?php
    $cPath_back = '';
    if(sizeof($cPath_array) > 0)
	 for($i=0, $n=sizeof($cPath_array)-1; $i<$n; $i++)
	  if (empty($cPath_back))
	   $cPath_back.= $cPath_array[$i];
	  else
	   $cPath_back.='_'.$cPath_array[$i];

	$cPath_back = (zen_not_null($cPath_back)) ? 'cPath=' . $cPath_back . '&' : '';
	echo (sizeof($cPath_array)>0?'<a href="'.zen_href_link(FILENAME_ADMIN_REWARD_POINTS,$cPath_back.'cID='.$current_category_id).'">'.zen_image_button('button_back.gif', IMAGE_BACK).'</a>':"&nbsp;"); 
?>
  </td>
  <td class="smallText" align="right" valign="middle">
<?php
    $global_reward_point_record=GetRewardPointRecord(0);
	echo zen_draw_form('set_global_reward_points',FILENAME_ADMIN_REWARD_POINTS,'cPath='.$cPath.'&action=set_reward_points','post').zen_hide_session_id();
	echo TEXT_GLOBAL_REWARD_POINT_PROMPT.'&nbsp;'.zen_draw_input_field('reward_point_ratio',$global_reward_point_record->fields[FIELD_POINT_RATIO],zen_set_field_length(TABLE_REWARD_MASTER,FIELD_POINT_RATIO));
	echo '</form>';
?>
  </td>
 </tr>
</table>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
 <tr>
  <td width="75%" height="1px"></td>
  <td width="25%" height="1px"></td>
 </tr>
 <tr>
  <td valign="top">
   <table border="0" width="100%" cellspacing="0" cellpadding="2">
    <tr class="dataTableHeadingRow">
     <td class="dataTableHeadingContent" width="20px" align="right"><?php echo TABLE_HEADING_ID; ?></td>
     <td class="dataTableHeadingContent" width="50%"><?php echo TABLE_HEADING_CATEGORIES_PRODUCTS; ?></td>
     <td class="dataTableHeadingContent" width="10%"><?php echo TABLE_HEADING_MODEL; ?></td>
     <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_POINT_RATIO; ?></td>
     <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_POINT_BONUS; ?></td>
     <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
    </tr>
    <tr>
<?php
	if(isset($_GET['search']))
	 $safe_search_string="'%".zen_db_input(zen_db_prepare_input($_GET['search']))."%'";
	 
    switch ($_SESSION['categories_products_sort_order']) 
	{
		case (0):
			$order_by="ORDER BY c.sort_order, cd.categories_name";
			break;
		case (1):
			$order_by="ORDER BY cd.categories_name";
			break;
	}

    $categories_count = 0;
    $rows = 0;
	
    if (isset($_GET['search']))
	 $search="AND cd.categories_name LIKE ".$safe_search_string;
	else
	 $search="AND c.parent_id=".(int)$current_category_id;
	 
	$categories=$db->Execute("SELECT c.categories_id, cd.categories_name, cd.categories_description, c.categories_image,
                                         c.parent_id, c.sort_order, c.date_added, c.last_modified, c.categories_status
                                  from ".TABLE_CATEGORIES." c, ".TABLE_CATEGORIES_DESCRIPTION." cd
                                  where c.categories_id = cd.categories_id
                                  and cd.language_id = '".(int)$_SESSION['languages_id']."' ".
								  $search." ".
                                  $order_by);
    while (!$categories->EOF) 
	{
		$categories_count++;
		$rows++;

// Get parent_id for subcategories if search
		if(isset($_GET['search'])) $cPath = $categories->fields['parent_id'];
		 if((!isset($_GET['cID']) && !isset($_GET['pID']) || (isset($_GET['cID']) && ($_GET['cID'] == $categories->fields['categories_id']))) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) 
		  $cInfo = new objectInfo($categories->fields);

//		if (isset($cInfo) && is_object($cInfo) && ($categories->fields['categories_id']==$cInfo->categories_id))
//		 echo '<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . zen_href_link(FILENAME_ADMIN_REWARD_POINTS, zen_get_path($categories->fields['categories_id'])) . '\'">' . "\n";
//       else
         echo '<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . zen_href_link(FILENAME_ADMIN_REWARD_POINTS, zen_get_path($categories->fields['categories_id'])) . '\'">' . "\n";
		 
//		if ($action == '')
//		{
			$result=GetRewardPointRecord(SCOPE_CATEGORY,$categories->fields['categories_id']);
            echo '<td class="dataTableContent" width="20px" align="right">'.$categories->fields['categories_id'].'</td>';
            echo '<td class="dataTableContent">'.zen_image(DIR_WS_ICONS.'folder.gif', ICON_FOLDER,'','','ALIGN=').'&nbsp;<b>'.$categories->fields['categories_name'].'</b></td>';
            echo '<td class="dataTableContent" align="center">&nbsp;</td>';
            echo '<td class="dataTableContent" align="right">'.$result->fields[FIELD_POINT_RATIO].'</td>';
            echo '<td class="dataTableContent" align="right">'.$result->fields[FIELD_REWARD_BONUS_POINTS].'</td>';
            echo '<td class="dataTableContent" align="right">';
            echo '<a href="'.zen_href_link(FILENAME_ADMIN_REWARD_POINTS, 'cID='.$categories->fields['categories_id'].'&action=edit_reward_points').'">'.zen_image(DIR_WS_IMAGES.'icon_edit.gif',ICON_EDIT_REWARD_POINTS).'</a>';
            echo '<a href="'.zen_href_link(FILENAME_ADMIN_REWARD_POINTS, 'cID='.$categories->fields['categories_id'].'&action=clear_reward_points').'">'.zen_image(DIR_WS_IMAGES.'icon_delete.gif',ICON_DELETE_REWARD_POINTS).'</a>';
			echo zen_image(DIR_WS_IMAGES.'icon_arrow_right.gif');
			echo '</td>';
//		} // action == ''
		echo '</tr>';
        $categories->MoveNext();
    }

    switch ($_SESSION['categories_products_sort_order']) 
	{
		case (0):
			$order_by="order by p.products_sort_order, pd.products_name";
			break;
		case (1):
			$order_by="order by pd.products_name";
			break;
		case (2);
			$order_by="order by p.products_model";
			break;
		case (3);
			$order_by="order by p.products_quantity, pd.products_name";
			break;
		case (4);
			$order_by="order by p.products_quantity DESC, pd.products_name";
			break;
		case (5);
			$order_by="order by p.products_price_sorter, pd.products_name";
			break;
		case (6);
			$order_by="order by p.products_price_sorter DESC, pd.products_name";
			break;
	}

    $products_count = 0;
    if(isset($_GET['search']))
	 $search="AND p.master_categories_id = p2c.categories_id AND (pd.products_name like ".$safe_search_string." OR pd.products_description LIKE ".$safe_search_string." OR p.products_model LIKE " .$safe_search_string.")";
	else
	 $search="AND p2c.categories_id=".(int)$current_category_id;

// fix duplicates and force search to use master_categories_id
	$products=$db->Execute("SELECT p.products_type, p.products_id, pd.products_name, p.products_quantity, p.products_image, p.products_price, p.products_date_added,
									p.products_last_modified, p.products_date_available, p.products_status, p2c.categories_id, p.products_model,
									p.products_quantity_order_min, p.products_quantity_order_units, p.products_priced_by_attribute, p.product_is_free, 
									p.product_is_call, p.products_quantity_mixed, p.product_is_always_free_shipping, p.products_quantity_order_max, 
									p.products_sort_order, p.master_categories_id
									FROM ".TABLE_PRODUCTS." p, ".TABLE_PRODUCTS_DESCRIPTION." pd, ".TABLE_PRODUCTS_TO_CATEGORIES." p2c
									WHERE p.products_id = pd.products_id AND pd.language_id = '".(int)$_SESSION['languages_id']."'
									AND p.products_id = p2c.products_id ".
									$search." ".
									$order_by);
	
    while(!$products->EOF) 
	{
		$products_count++;
		$rows++;

// Get categories_id for product if search
		if (isset($_GET['search'])) 
		 $cPath = $products->fields['categories_id'];

//		if(isset($pInfo) && is_object($pInfo) && ($products->fields['products_id'] == $pInfo->products_id))
//		 echo '<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">'."\n";
//		else
         echo '<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . zen_href_link(FILENAME_ADMIN_REWARD_POINTS, 'cPath='.$cPath.'&pID='.$products->fields['products_id'].'&action=edit_reward_points') . '\'">' . "\n";

		$result=GetRewardPointRecord(SCOPE_PRODUCT,$products->fields['products_id']);
		echo '<td class="dataTableContent" width="20px" align="right">'.$products->fields['products_id'].'</td>';
		echo '<td class="dataTableContent">'.zen_image(DIR_WS_ICONS . 'preview.gif', ICON_PREVIEW).'&nbsp;'.$products->fields['products_name'].'</td>';
        echo '<td class="dataTableContent">'.$products->fields['products_model'].'</td>';
		echo '<td class="dataTableContent" align="right">'.$result->fields[FIELD_POINT_RATIO].'</td>';
		echo '<td class="dataTableContent" align="right">'.$result->fields[FIELD_REWARD_BONUS_POINTS].'</td>';
        echo '<td class="dataTableContent" align="right">';
        echo '<a href="'.zen_href_link(FILENAME_ADMIN_REWARD_POINTS, 'cPath='.$cPath.'&pID='.$products->fields['products_id'].'&action=edit_reward_points').'">'.zen_image(DIR_WS_IMAGES.'icon_edit.gif',ICON_EDIT_REWARD_POINTS).'</a>';
        echo '<a href="'.zen_href_link(FILENAME_ADMIN_REWARD_POINTS, 'cPath='.$cPath.'&pID='.$products->fields['products_id'].'&action=clear_reward_points').'">'.zen_image(DIR_WS_IMAGES.'icon_delete.gif',ICON_DELETE_REWARD_POINTS).'</a>';
		echo '</td>';

		echo '</tr>';
		$products->MoveNext();
	}
?>
     </tr> 
    </table>
   </td>
   <td valign="top">
<?php
    $box = new box;
    echo $box->infoBox($heading, $contents);
?>
   </td>
  </tr>
 </table>
<div class="smallText"><?php echo TEXT_CATEGORIES . '&nbsp;' . $categories_count . '/' . TEXT_PRODUCTS . '&nbsp;' . $products_count; ?></div>
<!-- body_eof //-->
<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br />
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
