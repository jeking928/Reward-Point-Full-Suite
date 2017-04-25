<?php
	define('FILENAME_ADMIN_REWARD_POINTS','reward_points');
	define('FILENAME_ADMIN_GROUP_REWARD_POINTS_REDEEM','group_reward_points');
	define('FILENAME_ADMIN_CUSTOMER_REWARD_POINTS','customers_reward_points');
    define('BOX_CONFIGURATION_REWARD_POINTS', 'Reward Point Configuration');//moved from _dhtml.php for 1.5
	
	define('TABLE_REWARD_CUSTOMER_POINTS', DB_PREFIX . 'reward_customer_points');
	define('TABLE_REWARD_MASTER', DB_PREFIX . 'reward_master');
	define('TABLE_REWARD_STATUS_TRACK', DB_PREFIX . 'reward_status_track');

	define('FIELD_POINT_RATIO','point_ratio');
	define('FIELD_BONUS_POINTS','bonus_points');
	define('FIELD_REDEEM_RATIO','redeem_ratio');

	define('SCOPE_GLOBAL',0);
	define('SCOPE_CATEGORY',1);
	define('SCOPE_PRODUCT',2);
	define('SCOPE_GROUP',3);
	define('SCOPE_CUSTOMER',4);

	define('STATUS_PENDING',0);
	define('STATUS_PROCESSED',1);
	define('STATUS_IGNORE',2);
	define('STATUS_CANCELLED',3);
	define('STATUS_REDEEMED',4);

?>