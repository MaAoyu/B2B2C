INSERT INTO `33hao_setting` VALUES ('mobile_host_type', '');
INSERT INTO `33hao_setting` VALUES ('mobile_host', '');
INSERT INTO `33hao_setting` VALUES ('mobile_username', '');
INSERT INTO `33hao_setting` VALUES ('mobile_pwd', '');
INSERT INTO `33hao_setting` VALUES ('mobile_signature', '');
INSERT INTO `33hao_setting` VALUES ('mobile_key', '');
INSERT INTO `33hao_setting` VALUES ('mobile_memo', '');
ALTER TABLE `33hao_store` ADD `store_free_time` varchar(10) DEFAULT NULL DEFAULT '2' COMMENT '商家配送时间';