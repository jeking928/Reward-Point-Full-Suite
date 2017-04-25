#NEXT_X_ROWS_AS_ONE_COMMAND:2
DELETE FROM `configuration_group` WHERE `configuration_group_id` NOT IN (
SELECT `configuration_group_id` FROM `configuration` WHERE 1);