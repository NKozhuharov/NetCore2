SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for dynamic_info_pages
-- ----------------------------
DROP TABLE IF EXISTS `dynamic_info_pages`;
CREATE TABLE `dynamic_info_pages`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `link` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `body` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `hidden` tinyint(1) UNSIGNED NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of dynamic_info_pages
-- ----------------------------
INSERT INTO `dynamic_info_pages` VALUES (1, 'about_us', 'about_us', 'About Us', 'About Us Info', 0);
INSERT INTO `dynamic_info_pages` VALUES (2, 'terms_and_conditions', 'terms_and_conditions', 'Terms and Conditions', 'Terms and Conditions Info', 0);
INSERT INTO `dynamic_info_pages` VALUES (3, 'cookie_policy', 'cookie_policy', 'Cookie Policy', 'Cookie Policy Info', 0);
INSERT INTO `dynamic_info_pages` VALUES (4, 'privacy_policy', 'privacy_policy', 'Privacy Policy', 'Privacy Policy Info', 0);

-- ----------------------------
-- Table structure for dynamic_info_pages_lang
-- ----------------------------
DROP TABLE IF EXISTS `dynamic_info_pages_lang`;
CREATE TABLE `dynamic_info_pages_lang`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `object_id` int(10) UNSIGNED NOT NULL,
  `lang_id` int(10) UNSIGNED NOT NULL,
  `link` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `body` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `object_id`(`object_id`, `lang_id`) USING BTREE,
  INDEX `lang_id`(`lang_id`) USING BTREE,
  CONSTRAINT `dynamic_info_pages_lang_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `dynamic_info_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `dynamic_info_pages_lang_ibfk_2` FOREIGN KEY (`lang_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
