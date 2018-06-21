/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50505
Source Host           : localhost:3306
Source Database       : spider

Target Server Type    : MYSQL
Target Server Version : 50505
File Encoding         : 65001

Date: 2018-06-21 17:58:21
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for sp_area
-- ----------------------------
DROP TABLE IF EXISTS `sp_area`;
CREATE TABLE `sp_area` (
  `area_id` varchar(15) NOT NULL DEFAULT '' COMMENT '地区标识',
  `area_pid` varchar(15) NOT NULL DEFAULT '' COMMENT '父id',
  `level` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 省  2   3 4 5  以此类推',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 ',
  `area_name` varchar(30) NOT NULL DEFAULT '' COMMENT '名称',
  `imported` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已经导入 默认0',
  PRIMARY KEY (`area_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
