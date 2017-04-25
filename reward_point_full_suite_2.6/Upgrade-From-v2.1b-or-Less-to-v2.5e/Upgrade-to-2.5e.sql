/*
zencart 1.5 mods
*/
SELECT @cgi:=configuration_group_id FROM configuration_group WHERE configuration_group_title = 'Reward Points';

INSERT INTO configuration
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Show Reward Points on Product Info Display Page', 'SHOW_REWARD_POINTS_PRODUCT', '0', 'Display Reward Points on product info display page?<br />0= No<br />1= Yes', @cgi, '1', NULL, now(), NULL , 'zen_cfg_select_option(array(''0'', ''1''), ');

/*
zencart 1.5 mods
*/
INSERT INTO admin_pages (page_key,language_key,main_page,page_params,menu_key,display_on_menu,sort_order) VALUES ('configRewardPoints','BOX_CONFIGURATION_REWARD_POINTS','FILENAME_CONFIGURATION',CONCAT('gID=',@cgi), 'configuration', 'Y', @cgi);  
INSERT INTO admin_pages (page_key, language_key, main_page, page_params, menu_key, display_on_menu, sort_order) VALUES ('GroupRewardRedeem', 'BOX_GROUP_REWARD_POINTS_REDEEM', 'FILENAME_ADMIN_GROUP_REWARD_POINTS_REDEEM', '', 'customers', 'Y', 35); 
INSERT INTO admin_pages (page_key, language_key, main_page, page_params, menu_key, display_on_menu, sort_order) VALUES ('RewardPoints', 'BOX_REWARD_POINTS', 'FILENAME_ADMIN_REWARD_POINTS', '', 'catalog', 'Y', 36); 
INSERT INTO admin_pages (page_key, language_key, main_page, page_params, menu_key, display_on_menu, sort_order) VALUES ('CustomerRewardPoints', 'BOX_CUSTOMER_REWARD_POINTS', 'FILENAME_ADMIN_CUSTOMER_REWARD_POINTS', '', 'customers', 'Y', 37); 
