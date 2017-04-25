DROP TABLE IF EXISTS reward_master;
DROP TABLE IF EXISTS reward_customer_points;
DROP TABLE IF EXISTS reward_status_track;

DELETE FROM admin_pages WHERE page_key='configRewardPoints';
DELETE FROM admin_pages WHERE page_key='GroupRewardRedeem';
DELETE FROM admin_pages WHERE page_key='RewardPoints';
DELETE FROM admin_pages WHERE page_key='CustomerRewardPoints';