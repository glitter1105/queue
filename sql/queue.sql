/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50726 (5.7.26)
 Source Host           : localhost:3306
 Source Schema         : webman

 Target Server Type    : MySQL
 Target Server Version : 50726 (5.7.26)
 File Encoding         : 65001

 Date: 27/12/2024 10:45:08
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for queue_numbers
-- ----------------------------
DROP TABLE IF EXISTS `queue_numbers`;
CREATE TABLE `queue_numbers`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '号码',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '姓名',
  `mobile` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '手机号',
  `call_count` int(11) NULL DEFAULT 0 COMMENT '叫号次数',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '状态:0待叫号,1已取消,2已过号,3已完成',
  `window_id` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '窗口号',
  `qrcode_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '二维码链接',
  `created_at` datetime NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_number`(`number`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '叫号队列表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of queue_numbers
-- ----------------------------

-- ----------------------------
-- Table structure for windows
-- ----------------------------
DROP TABLE IF EXISTS `windows`;
CREATE TABLE `windows`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '窗口名称',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '窗口描述',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 1:启用 0:禁用',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '窗口表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of windows
-- ----------------------------
INSERT INTO `windows` VALUES (1, '1号窗口', '普通窗口', 1, '2024-12-23 14:50:45', '2024-12-23 16:15:42');
INSERT INTO `windows` VALUES (2, '2号窗口', '普通窗口', 1, '2024-12-23 14:50:52', '2024-12-23 16:15:47');
INSERT INTO `windows` VALUES (3, '3号窗口', '普通窗口', 1, '2024-12-23 14:51:09', '2024-12-23 16:15:51');
INSERT INTO `windows` VALUES (4, '4号窗口', '普通窗口', 1, '2024-12-23 14:51:19', '2024-12-23 14:51:19');
INSERT INTO `windows` VALUES (5, '5号窗口', 'VIP窗口', 1, '2024-12-23 15:10:51', '2024-12-23 15:10:51');
INSERT INTO `windows` VALUES (6, '6号窗口', 'VIP窗口', 1, '2024-12-23 15:12:34', '2024-12-23 17:17:06');

SET FOREIGN_KEY_CHECKS = 1;
