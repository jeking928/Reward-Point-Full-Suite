CREATE TABLE reward_master (
rewards_products_id INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
scope INT( 1 ) NOT NULL DEFAULT '0',
scope_id INT( 11 ) NOT NULL DEFAULT '0',
point_ratio DOUBLE( 15, 4 ) NOT NULL DEFAULT '1',
bonus_points DOUBLE( 15, 4 ) NULL,
redeem_ratio DOUBLE( 15, 4 ) NULL,
redeem_points DOUBLE( 15, 4 ) NULL,
UNIQUE unique_id ( scope , scope_id ));

INSERT INTO reward_master 
(rewards_products_id ,scope ,scope_id ,point_ratio ,bonus_points, redeem_ratio, redeem_points)
VALUES (NULL , '0', '0', '1.0000', NULL, 0.01, NULL);

CREATE TABLE reward_customer_points (
customers_id INT( 11 ) NOT NULL PRIMARY KEY,
reward_points DOUBLE( 15, 4 ) NOT NULL DEFAULT '0',
pending_points DOUBLE( 15, 4 ) NOT NULL DEFAULT '0');

CREATE TABLE reward_status_track (
rewards_id INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
customers_id INT( 11 ) NOT NULL ,
orders_id INT( 11 ) NOT NULL ,
date DATETIME NOT NULL ,
reward_points DOUBLE( 15, 4 ) NOT NULL ,
status TINYINT( 1 ) NOT NULL,
UNIQUE (orders_id));


SELECT @groupid:=configuration_group_id FROM configuration_group
WHERE configuration_group_title= 'Reward Points';
DELETE FROM configuration WHERE configuration_group_id = @groupid  AND configuration_group_id != 0;
DELETE FROM configuration_group WHERE configuration_group_id = @groupid AND configuration_group_id != 0;


INSERT INTO configuration_group VALUES (NULL , 'Reward Points', 'Reward Point Module Configuration', '50' , '1');
UPDATE configuration_group SET sort_order = last_insert_id() WHERE configuration_group_id = last_insert_id();
SELECT @cgi:=configuration_group_id FROM configuration_group WHERE configuration_group_title = 'Reward Points';

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Reward Point Mode', 'REWARD_POINT_MODE', '0', 'Select the Reward Point Mode<br />0= Reward Points are fixed to the product prices and are calculated individually.<br />1= Reward Points are calculated on the Order Total or Subtotal (depending on the setting of the <strong>Allow Redeem of Reward Points on Order Total or Subtotal</strong> configuration).', @cgi, '0', NULL, now(), NULL , 'zen_cfg_select_option(array(''0'', ''1''), ');

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Reward Point Sidebox Display', 'SHOW_REWARD_POINTS_BOX_OPTION', '0', 'Display Reward Points Sidebox<br />0= Always<br />1= Only when logged in<br />2= Only when logged in and has points', @cgi, '1', NULL, now(), NULL , 'zen_cfg_select_option(array(''0'', ''1'', ''2''), ');

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Reward Point Status Track', 'REWARD_POINTS_STATUS_TRACK', '', '<b>Simple mode:</b> All new reward points are set to Pending and are changed to Earned when the Order Status changes. If the Order Status is then changed back to Pending then the reward points are transfered back from Earned.<br /><br /><b>Advanced mode:</b> Allows you to select the order status that will trigger a transfer of reward points from pending to earned. Points are transferred when the status changes between status items set to "Pending" and status items set to "Earned". Status items set to "Ignore" will have no effect on Order Status changes to it.', @cgi, '2', NULL, now(), 'UseRewardPointStateFunction' , 'SetRewardPointStateFunction(');

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Reward Point Sunrise Period', 'REWARD_POINTS_SUNRISE_PERIOD', '0', 'The number of days after which points pending become points earned. Set to 0 for no sunrise period.', @cgi, '3', NULL, now(), NULL , NULL);

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Reward Point Redeem Minimum', 'REWARD_POINTS_REDEEM_MINIMUM', '0', 'This is the minimum amount of earned points needed before they can be redeemed.', @cgi, '3', NULL, now(), NULL , NULL);

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Reward Point Redeem Maximum', 'REWARD_POINTS_REDEEM_MAXIMUM', '0', 'This is the maximum amount of earned points that can be redeemed against a single order.<br /><i>Note: this can be a absolute value (eg 1000) or a percentage (20%).</i>', @cgi, '4', NULL, now(), NULL , NULL);

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Reward Point Rounding', 'REWARD_POINTS_ROUNDING', '0.0', 'Rounding value- This is an adjustment to each product price before it is rounded to 0 decimal places to calculate product Reward Points (default 0.0)', @cgi, '5', NULL, now(), NULL , NULL);

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Max Transactions to Display in Customer Admin', 'REWARD_POINTS_MAX_TRANSACTIONS', '12', 'Select the maximum number of records to show on the Pending Reward Points table in Customer Reward Point administration.', @cgi, '6', NULL, now(), NULL , NULL);

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Delete Old Reward Transactions Period', 'REWARD_POINTS_HOUSEKEEPING', '90', 'Set the age (in days) to keep the reward point transactions. Outstanding reward points pending after this period will be lost. Set to 0 to keep all transactions.', @cgi, '7', NULL, now(), NULL , NULL);

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Adjust Reward Points for Sales/Specials', 'REWARD_POINTS_SPECIAL_ADJUST', '0', 'How to calculate Reward Points<br />0= Always use the base price.<br />1= Use price less Discounts and Specials.', @cgi, '7', NULL, now(), NULL , 'zen_cfg_select_option(array(''0'', ''1''), ');

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Allow Reward Points on Free Products', 'REWARD_POINTS_ALLOW_ON_FREE', '0', 'Set how points are rewarded for free products.<br />0= Free products give 0 Reward Points.<br />1= Allow Reward Points on free products.', @cgi, '8', NULL, now(), NULL , 'zen_cfg_select_option(array(''0'', ''1''), ');

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Allow Redeem of Reward Points on Order Total or Subtotal', 'REWARD_POINTS_ALLOW_TOTAL', '0', 'Allow points to be redeemed against the full order (including shipping) or only against the subtotal.<br />0= Against the subtotal.<br />1= Against the full order.', @cgi, '9', NULL, now(), NULL , 'zen_cfg_select_option(array(''0'', ''1''), ');

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Set Minimum Order Value to Redeem Points Against', 'REWARD_POINTS_MINIMUM_VALUE', '0', 'Set the minimum value that the order should be in order for it to qualify for reward point redeem.', @cgi, '10', NULL, now(), NULL , NULL);

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Limit Maximum Customers on Listings', 'REWARD_POINTS_CUSTOMER_LIMIT', '50', 'Set the maximum number of records to appear on each page under Customer Reward Point administration page.', @cgi, '11', NULL, now(), NULL , NULL);

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Display Products Reward Points When Zero', 'REWARD_POINTS_ALWAYS_DISPLAY', '1', 'Set whether a products reward points are displayed when zero.<br />0= Don\'t display 0 Reward Points.<br />1= Always display reward points even when zero.', @cgi, '12', NULL, now(), NULL , 'zen_cfg_select_option(array(''0'', ''1''), ');

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
VALUES (NULL , 'Set New Account Reward Points', 'REWARD_POINTS_NEW_ACCOUNT_REWARD', '0', 'Set the amount of points awarded to a customer when an account is created. The points can either be added to \'Earned\' which will allow the customer to redeem the points straight away; Or the points can be added to \'Pending\' in which case the customer will receive the points after their first successful order.', @cgi, '13', NULL, now(), 'UseRewardPointNewAccountAwardFunction' , 'SetRewardPointNewAccountAwardFunction(');

INSERT INTO configuration 
(configuration_id ,configuration_title ,configuration_key ,configuration_value ,configuration_description ,configuration_group_id ,sort_order ,last_modified ,date_added ,use_function ,set_function)
values (NULL, 'Advanced Reward Point Calculation Rules', 'REWARD_POINTS_ADVANCED_CALCULATE_TABLE', '-ot_gv,-ot_coupon', 'Advanced configuration of Reward Point calculations. This will be the figure that total Reward Points are adjusted by. This also affects the maximum value that any points can be redeemed against.', @cgi, '14', NULL, now(), 'UseRewardPointAdvancedCalculateTableFunction', 'SetRewardPointAdvancedCalculateTableFunction(');

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
