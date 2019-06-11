
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for surveys
-- ----------------------------
DROP TABLE IF EXISTS `surveys`;
CREATE TABLE `surveys`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `link` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `published_on` timestamp(0) NULL DEFAULT NULL,
  `expires_on` timestamp(0) NULL DEFAULT NULL,
  `expired` tinyint(1) UNSIGNED NULL DEFAULT 0,
  `added` timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP(0),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `user_id`(`user_id`) USING BTREE,
  CONSTRAINT `surveys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for surveys_lang
-- ----------------------------
DROP TABLE IF EXISTS `surveys_lang`;
CREATE TABLE `surveys_lang`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `object_id` int(11) UNSIGNED NOT NULL,
  `lang_id` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `link` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `object_id`(`object_id`) USING BTREE,
  INDEX `lang_id`(`lang_id`) USING BTREE,
  CONSTRAINT `surveys_lang_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `surveys_lang_ibfk_2` FOREIGN KEY (`lang_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for surveys_questions
-- ----------------------------
DROP TABLE IF EXISTS `surveys_questions`;
CREATE TABLE `surveys_questions`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `object_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `object_id`(`object_id`) USING BTREE,
  CONSTRAINT `surveys_questions_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for surveys_questions_lang
-- ----------------------------
DROP TABLE IF EXISTS `surveys_questions_lang`;
CREATE TABLE `surveys_questions_lang`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `object_id` int(11) UNSIGNED NOT NULL,
  `lang_id` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `lang_id`(`lang_id`) USING BTREE,
  INDEX `surveys_questions_lang_ibfk_3`(`object_id`) USING BTREE,
  CONSTRAINT `surveys_questions_lang_ibfk_2` FOREIGN KEY (`lang_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `surveys_questions_lang_ibfk_3` FOREIGN KEY (`object_id`) REFERENCES `surveys_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for surveys_votes
-- ----------------------------
DROP TABLE IF EXISTS `surveys_votes`;
CREATE TABLE `surveys_votes`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `survery_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `added` timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP(0),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `survery_id`(`survery_id`) USING BTREE,
  INDEX `question_id`(`question_id`) USING BTREE,
  CONSTRAINT `surveys_votes_ibfk_1` FOREIGN KEY (`survery_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `surveys_votes_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `surveys_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
