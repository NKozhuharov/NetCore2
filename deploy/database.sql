SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for images
-- ----------------------------
DROP TABLE IF EXISTS `images`;
CREATE TABLE `images`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `hash` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `org` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `src` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `watermark` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `added` timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP(0),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for languages
-- ----------------------------
DROP TABLE IF EXISTS `languages`;
CREATE TABLE `languages`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `native_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `short` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `active` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 185 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of languages
-- ----------------------------
INSERT INTO `languages` VALUES (1, 'Abkhazian', 'аҧсуа бызшәа, аҧсшәа', 'ab', 0);
INSERT INTO `languages` VALUES (2, 'Afar', 'Afaraf', 'aa', 0);
INSERT INTO `languages` VALUES (3, 'Afrikaans', 'Afrikaans', 'af', 0);
INSERT INTO `languages` VALUES (4, 'Akan', 'Akan', 'ak', 0);
INSERT INTO `languages` VALUES (5, 'Albanian', 'Shqip', 'sq', 0);
INSERT INTO `languages` VALUES (6, 'Amharic', 'አማርኛ', 'am', 0);
INSERT INTO `languages` VALUES (7, 'Arabic', 'العربية', 'ar', 0);
INSERT INTO `languages` VALUES (8, 'Aragonese', 'aragonés', 'an', 0);
INSERT INTO `languages` VALUES (9, 'Armenian', 'Հայերեն', 'hy', 0);
INSERT INTO `languages` VALUES (10, 'Assamese', 'অসমীয়া', 'as', 0);
INSERT INTO `languages` VALUES (11, 'Avaric', 'Авар мацӀ, магӀарул мацӀ', 'av', 0);
INSERT INTO `languages` VALUES (12, 'Avestan', 'Avesta', 'ae', 0);
INSERT INTO `languages` VALUES (13, 'Aymara', 'Aymar aru', 'ay', 0);
INSERT INTO `languages` VALUES (14, 'Azerbaijani', 'Azərbaycan dili', 'az', 0);
INSERT INTO `languages` VALUES (15, 'Bambara', 'Bbamanankan', 'bm', 0);
INSERT INTO `languages` VALUES (16, 'Bashkir', 'Башҡорт теле', 'ba', 0);
INSERT INTO `languages` VALUES (17, 'Basque', 'Euskara, Euskera', 'eu', 0);
INSERT INTO `languages` VALUES (18, 'Belarusian', 'Беларуская мова', 'be', 0);
INSERT INTO `languages` VALUES (19, 'Bengali', 'বাংলা', 'bn', 0);
INSERT INTO `languages` VALUES (20, 'Bihari languages', 'भोजपुरी', 'bh', 0);
INSERT INTO `languages` VALUES (21, 'Bislama', 'Bislama', 'bi', 0);
INSERT INTO `languages` VALUES (22, 'Bosnian', 'Bosanski jezik', 'bs', 0);
INSERT INTO `languages` VALUES (23, 'Breton', 'Brezhoneg', 'br', 0);
INSERT INTO `languages` VALUES (24, 'Bulgarian', 'Български', 'bg', 1);
INSERT INTO `languages` VALUES (25, 'Burmese', 'Burmese', 'my', 0);
INSERT INTO `languages` VALUES (26, 'Catalan, Valencian', 'català, valencià', 'ca', 0);
INSERT INTO `languages` VALUES (27, 'Chamorro', 'Chamoru', 'ch', 0);
INSERT INTO `languages` VALUES (28, 'Chechen', 'Нохчийн мотт', 'ce', 0);
INSERT INTO `languages` VALUES (29, 'Chichewa, Chewa, Nyanja', 'ChiCheŵa, Chinyanja', 'ny', 0);
INSERT INTO `languages` VALUES (30, 'Chinese', '中文 (Zhōngwén), 汉语, 漢語', 'zh', 0);
INSERT INTO `languages` VALUES (31, 'Chuvash', 'Чӑваш чӗлхи', 'cv', 0);
INSERT INTO `languages` VALUES (32, 'Cornish', 'Kernewek', 'kw', 0);
INSERT INTO `languages` VALUES (33, 'Corsican', 'Corsu, Lingua corsa', 'co', 0);
INSERT INTO `languages` VALUES (34, 'Cree', 'ᓀᐦᐃᔭᐍᐏᐣ', 'cr', 0);
INSERT INTO `languages` VALUES (35, 'Croatian', 'Hrvatski jezik', 'hr', 0);
INSERT INTO `languages` VALUES (36, 'Czech', 'čeština, český jazyk', 'cs', 0);
INSERT INTO `languages` VALUES (37, 'Danish', 'Dansk', 'da', 0);
INSERT INTO `languages` VALUES (38, 'Divehi, Dhivehi, Maldivian', 'ދިވެހި', 'dv', 0);
INSERT INTO `languages` VALUES (39, 'Dutch, Flemish', 'Nederlands, Vlaams', 'nl', 0);
INSERT INTO `languages` VALUES (40, 'Dzongkha', 'རྫོང་ཁ', 'dz', 0);
INSERT INTO `languages` VALUES (41, 'English', 'English', 'en', 1);
INSERT INTO `languages` VALUES (42, 'Esperanto', 'Esperanto', 'eo', 0);
INSERT INTO `languages` VALUES (43, 'Estonian', 'Eesti, Eesti keel', 'et', 0);
INSERT INTO `languages` VALUES (44, 'Ewe', 'Eʋegbe', 'ee', 0);
INSERT INTO `languages` VALUES (45, 'Faroese', 'Føroyskt', 'fo', 0);
INSERT INTO `languages` VALUES (46, 'Fijian', 'Vosa Vakaviti', 'fj', 0);
INSERT INTO `languages` VALUES (47, 'Finnish', 'Suomi, Suomen kieli', 'fi', 0);
INSERT INTO `languages` VALUES (48, 'French', 'Français, Langue française', 'fr', 0);
INSERT INTO `languages` VALUES (49, 'Fulah', 'Fulfulde, Pulaar, Pular', 'ff', 0);
INSERT INTO `languages` VALUES (50, 'Galician', 'Galego', 'gl', 0);
INSERT INTO `languages` VALUES (51, 'Georgian', 'ქართული', 'ka', 0);
INSERT INTO `languages` VALUES (52, 'German', 'Deutsch', 'de', 0);
INSERT INTO `languages` VALUES (53, 'Greek (modern)', 'ελληνικά', 'el', 0);
INSERT INTO `languages` VALUES (54, 'Guaraní', 'Avañe\'ẽ', 'gn', 0);
INSERT INTO `languages` VALUES (55, 'Gujarati', 'ગુજરાતી', 'gu', 0);
INSERT INTO `languages` VALUES (56, 'Haitian, Haitian Creole', 'Kreyòl ayisyen', 'ht', 0);
INSERT INTO `languages` VALUES (57, 'Hausa', '(Hausa) هَوُسَ', 'ha', 0);
INSERT INTO `languages` VALUES (58, 'Hebrew (modern)', 'עברית', 'he', 0);
INSERT INTO `languages` VALUES (59, 'Herero', 'Otjiherero', 'hz', 0);
INSERT INTO `languages` VALUES (60, 'Hindi', 'हिन्दी, हिंदी', 'hi', 0);
INSERT INTO `languages` VALUES (61, 'Hiri Motu', 'Hiri Motu', 'ho', 0);
INSERT INTO `languages` VALUES (62, 'Hungarian', 'Magyar', 'hu', 0);
INSERT INTO `languages` VALUES (63, 'Interlingua', 'Interlingua', 'ia', 0);
INSERT INTO `languages` VALUES (64, 'Indonesian', 'Bahasa Indonesia', 'id', 0);
INSERT INTO `languages` VALUES (65, 'Interlingue', 'Originally called Occidental; then Interlingue after WWII', 'ie', 0);
INSERT INTO `languages` VALUES (66, 'Irish', 'Gaeilge', 'ga', 0);
INSERT INTO `languages` VALUES (67, 'Igbo', 'Asụsụ Igbo', 'ig', 0);
INSERT INTO `languages` VALUES (68, 'Inupiaq', 'Iñupiaq, Iñupiatun', 'ik', 0);
INSERT INTO `languages` VALUES (69, 'Ido', 'Ido', 'io', 0);
INSERT INTO `languages` VALUES (70, 'Icelandic', 'Íslenska', 'is', 0);
INSERT INTO `languages` VALUES (71, 'Italian', 'Italiano', 'it', 0);
INSERT INTO `languages` VALUES (72, 'Inuktitut', 'ᐃᓄᒃᑎᑐᑦ', 'iu', 0);
INSERT INTO `languages` VALUES (73, 'Japanese', '日本語 (にほんご)', 'ja', 0);
INSERT INTO `languages` VALUES (74, 'Javanese', 'Basa Jawa', 'jv', 0);
INSERT INTO `languages` VALUES (75, 'Kalaallisut, Greenlandic', 'Kalaallisut, Kalaallit oqaasii', 'kl', 0);
INSERT INTO `languages` VALUES (76, 'Kannada', 'ಕನ್ನಡ', 'kn', 0);
INSERT INTO `languages` VALUES (77, 'Kanuri', 'Kanuri', 'kr', 0);
INSERT INTO `languages` VALUES (78, 'Kashmiri', 'कश्मीरी, كشميري‎', 'ks', 0);
INSERT INTO `languages` VALUES (79, 'Kazakh', 'қазақ тілі', 'kk', 0);
INSERT INTO `languages` VALUES (80, 'Central Khmer', 'ខ្មែរ, ខេមរភាសា, ភាសាខ្មែរ', 'km', 0);
INSERT INTO `languages` VALUES (81, 'Kikuyu, Gikuyu', 'Gĩkũyũ', 'ki', 0);
INSERT INTO `languages` VALUES (82, 'Kinyarwanda', 'Ikinyarwanda', 'rw', 0);
INSERT INTO `languages` VALUES (83, 'Kirghiz, Kyrgyz', 'Кыргызча, Кыргыз тили', 'ky', 0);
INSERT INTO `languages` VALUES (84, 'Komi', 'коми кыв', 'kv', 0);
INSERT INTO `languages` VALUES (85, 'Kongo', 'Kikongo', 'kg', 0);
INSERT INTO `languages` VALUES (86, 'Korean', '한국어', 'ko', 0);
INSERT INTO `languages` VALUES (87, 'Kurdish', 'Kurdî, كوردی‎', 'ku', 0);
INSERT INTO `languages` VALUES (88, 'Kuanyama, Kwanyama', 'Kuanyama', 'kj', 0);
INSERT INTO `languages` VALUES (89, 'Latin', 'latine, lingua latina', 'la', 0);
INSERT INTO `languages` VALUES (90, 'Luxembourgish, Letzeburgesch', 'Lëtzebuergesch', 'lb', 0);
INSERT INTO `languages` VALUES (91, 'Ganda', 'Luganda', 'lg', 0);
INSERT INTO `languages` VALUES (92, 'Limburgan, Limburger, Limburgish', 'Limburgs', 'li', 0);
INSERT INTO `languages` VALUES (93, 'Lingala', 'Lingála', 'ln', 0);
INSERT INTO `languages` VALUES (94, 'Lao', 'ພາສາລາວ', 'lo', 0);
INSERT INTO `languages` VALUES (95, 'Lithuanian', 'lietuvių kalba', 'lt', 0);
INSERT INTO `languages` VALUES (96, 'Luba-Katanga', 'Kiluba', 'lu', 0);
INSERT INTO `languages` VALUES (97, 'Latvian', 'Latviešu Valoda', 'lv', 0);
INSERT INTO `languages` VALUES (98, 'Manx', 'Gaelg, Gailck', 'gv', 0);
INSERT INTO `languages` VALUES (99, 'Macedonian', 'македонски јазик', 'mk', 0);
INSERT INTO `languages` VALUES (100, 'Malagasy', 'fiteny malagasy', 'mg', 0);
INSERT INTO `languages` VALUES (101, 'Malay', 'Bahasa Melayu, بهاس ملايو‎', 'ms', 0);
INSERT INTO `languages` VALUES (102, 'Malayalam', 'മലയാളം', 'ml', 0);
INSERT INTO `languages` VALUES (103, 'Maltese', 'Malti', 'mt', 0);
INSERT INTO `languages` VALUES (104, 'Maori', 'te reo Māori', 'mi', 0);
INSERT INTO `languages` VALUES (105, 'Marathi', 'मराठी', 'mr', 0);
INSERT INTO `languages` VALUES (106, 'Marshallese', 'Kajin M̧ajeļ', 'mh', 0);
INSERT INTO `languages` VALUES (107, 'Mongolian', 'Монгол хэл', 'mn', 0);
INSERT INTO `languages` VALUES (108, 'Nauru', 'Dorerin Naoero', 'na', 0);
INSERT INTO `languages` VALUES (109, 'Navajo, Navaho', 'Diné bizaad', 'nv', 0);
INSERT INTO `languages` VALUES (110, 'North Ndebele', 'isiNdebele', 'nd', 0);
INSERT INTO `languages` VALUES (111, 'Nepali', 'नेपाली', 'ne', 0);
INSERT INTO `languages` VALUES (112, 'Ndonga', 'Owambo', 'ng', 0);
INSERT INTO `languages` VALUES (113, 'Norwegian Bokmål', 'Norsk Bokmål', 'nb', 0);
INSERT INTO `languages` VALUES (114, 'Norwegian Nynorsk', 'Norsk Nynorsk', 'nn', 0);
INSERT INTO `languages` VALUES (115, 'Norwegian', 'Norsk', 'no', 0);
INSERT INTO `languages` VALUES (116, 'Sichuan Yi, Nuosu', 'ꆈꌠ꒿ Nuosuhxop', 'ii', 0);
INSERT INTO `languages` VALUES (117, 'South Ndebele', 'isiNdebele', 'nr', 0);
INSERT INTO `languages` VALUES (118, 'Occitan', 'occitan, lenga d\'òc', 'oc', 0);
INSERT INTO `languages` VALUES (119, 'Ojibwa', 'ᐊᓂᔑᓈᐯᒧᐎᓐ', 'oj', 0);
INSERT INTO `languages` VALUES (120, 'Church Slavic, Church Slavonic, Old Church Slavonic, Old Slavonic, Old Bulgarian', 'ѩзыкъ словѣньскъ', 'cu', 0);
INSERT INTO `languages` VALUES (121, 'Oromo', 'Afaan Oromoo', 'om', 0);
INSERT INTO `languages` VALUES (122, 'Oriya', 'ଓଡ଼ିଆ', 'or', 0);
INSERT INTO `languages` VALUES (123, 'Ossetian, Ossetic', 'ирон æвзаг', 'os', 0);
INSERT INTO `languages` VALUES (124, 'Panjabi, Punjabi', 'ਪੰਜਾਬੀ', 'pa', 0);
INSERT INTO `languages` VALUES (125, 'Pali', 'पाऴि', 'pi', 0);
INSERT INTO `languages` VALUES (126, 'Persian', 'فارسی', 'fa', 0);
INSERT INTO `languages` VALUES (127, 'Polish', 'Język Polski, Polszczyzna', 'pl', 0);
INSERT INTO `languages` VALUES (128, 'Pashto, Pushto', 'پښتو', 'ps', 0);
INSERT INTO `languages` VALUES (129, 'Portuguese', 'Português', 'pt', 0);
INSERT INTO `languages` VALUES (130, 'Quechua', 'Runa Simi, Kichwa', 'qu', 0);
INSERT INTO `languages` VALUES (131, 'Romansh', 'Rumantsch Grischun', 'rm', 0);
INSERT INTO `languages` VALUES (132, 'Rundi', 'Ikirundi', 'rn', 0);
INSERT INTO `languages` VALUES (133, 'Romanian, Moldavian, Moldovan', 'Română', 'ro', 0);
INSERT INTO `languages` VALUES (134, 'Russian', 'Русский', 'ru', 0);
INSERT INTO `languages` VALUES (135, 'Sanskrit', 'संस्कृतम्', 'sa', 0);
INSERT INTO `languages` VALUES (136, 'Sardinian', 'sardu', 'sc', 0);
INSERT INTO `languages` VALUES (137, 'Sindhi', 'सिन्धी, سنڌي، سندھی‎', 'sd', 0);
INSERT INTO `languages` VALUES (138, 'Northern Sami', 'Davvisámegiella', 'se', 0);
INSERT INTO `languages` VALUES (139, 'Samoan', 'gagana fa\'a Samoa', 'sm', 0);
INSERT INTO `languages` VALUES (140, 'Sango', 'yângâ tî sängö', 'sg', 0);
INSERT INTO `languages` VALUES (141, 'Serbian', 'српски језик', 'sr', 0);
INSERT INTO `languages` VALUES (142, 'Gaelic, Scottish Gaelic', 'Gàidhlig', 'gd', 0);
INSERT INTO `languages` VALUES (143, 'Shona', 'chiShona', 'sn', 0);
INSERT INTO `languages` VALUES (144, 'Sinhala, Sinhalese', 'සිංහල', 'si', 0);
INSERT INTO `languages` VALUES (145, 'Slovak', 'Slovenčina, Slovenský Jazyk', 'sk', 0);
INSERT INTO `languages` VALUES (146, 'Slovenian', 'Slovenski Jezik, Slovenščina', 'sl', 0);
INSERT INTO `languages` VALUES (147, 'Somali', 'Soomaaliga, af Soomaali', 'so', 0);
INSERT INTO `languages` VALUES (148, 'Southern Sotho', 'Sesotho', 'st', 0);
INSERT INTO `languages` VALUES (149, 'Spanish, Castilian', 'Español', 'es', 0);
INSERT INTO `languages` VALUES (150, 'Sundanese', 'Basa Sunda', 'su', 0);
INSERT INTO `languages` VALUES (151, 'Swahili', 'Kiswahili', 'sw', 0);
INSERT INTO `languages` VALUES (152, 'Swati', 'SiSwati', 'ss', 0);
INSERT INTO `languages` VALUES (153, 'Swedish', 'Svenska', 'sv', 0);
INSERT INTO `languages` VALUES (154, 'Tamil', 'தமிழ்', 'ta', 0);
INSERT INTO `languages` VALUES (155, 'Telugu', 'తెలుగు', 'te', 0);
INSERT INTO `languages` VALUES (156, 'Tajik', 'тоҷикӣ, toçikī, تاجیکی‎', 'tg', 0);
INSERT INTO `languages` VALUES (157, 'Thai', 'ไทย', 'th', 0);
INSERT INTO `languages` VALUES (158, 'Tigrinya', 'ትግርኛ', 'ti', 0);
INSERT INTO `languages` VALUES (159, 'Tibetan', 'བོད་ཡིག', 'bo', 0);
INSERT INTO `languages` VALUES (160, 'Turkmen', 'Türkmen, Түркмен', 'tk', 0);
INSERT INTO `languages` VALUES (161, 'Tagalog', 'Wikang Tagalog', 'tl', 0);
INSERT INTO `languages` VALUES (162, 'Tswana', 'Setswana', 'tn', 0);
INSERT INTO `languages` VALUES (163, 'Tonga (Tonga Islands)', 'Faka Tonga', 'to', 0);
INSERT INTO `languages` VALUES (164, 'Turkish', 'Türkçe', 'tr', 0);
INSERT INTO `languages` VALUES (165, 'Tsonga', 'Xitsonga', 'ts', 0);
INSERT INTO `languages` VALUES (166, 'Tatar', 'татар теле, tatar tele', 'tt', 0);
INSERT INTO `languages` VALUES (167, 'Twi', 'Twi', 'tw', 0);
INSERT INTO `languages` VALUES (168, 'Tahitian', 'Reo Tahiti', 'ty', 0);
INSERT INTO `languages` VALUES (169, 'Uighur, Uyghur', 'ئۇيغۇرچە‎, Uyghurche', 'ug', 0);
INSERT INTO `languages` VALUES (170, 'Ukrainian', 'Українська', 'uk', 0);
INSERT INTO `languages` VALUES (171, 'Urdu', 'اردو', 'ur', 0);
INSERT INTO `languages` VALUES (172, 'Uzbek', 'Oʻzbek, Ўзбек, أۇزبېك‎', 'uz', 0);
INSERT INTO `languages` VALUES (173, 'Venda', 'Tshivenḓa', 've', 0);
INSERT INTO `languages` VALUES (174, 'Vietnamese', 'Tiếng Việt', 'vi', 0);
INSERT INTO `languages` VALUES (175, 'Volapük', 'Volapük', 'vo', 0);
INSERT INTO `languages` VALUES (176, 'Walloon', 'Walon', 'wa', 0);
INSERT INTO `languages` VALUES (177, 'Welsh', 'Cymraeg', 'cy', 0);
INSERT INTO `languages` VALUES (178, 'Wolof', 'Wollof', 'wo', 0);
INSERT INTO `languages` VALUES (179, 'Western Frisian', 'Frysk', 'fy', 0);
INSERT INTO `languages` VALUES (180, 'Xhosa', 'isiXhosa', 'xh', 0);
INSERT INTO `languages` VALUES (181, 'Yiddish', 'ייִדיש', 'yi', 0);
INSERT INTO `languages` VALUES (182, 'Yoruba', 'Yorùbá', 'yo', 0);
INSERT INTO `languages` VALUES (183, 'Zhuang, Chuang', 'Saɯ cueŋƅ, Saw cuengh', 'za', 0);
INSERT INTO `languages` VALUES (184, 'Zulu', 'isiZulu', 'zu', 0);

-- ----------------------------
-- Table structure for languages_lang
-- ----------------------------
DROP TABLE IF EXISTS `languages_lang`;
CREATE TABLE `languages_lang`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `object_id` int(10) UNSIGNED NOT NULL,
  `lang_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `object_id`(`object_id`) USING BTREE,
  INDEX `lang_id`(`lang_id`) USING BTREE,
  CONSTRAINT `languages_lang_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `languages_lang_ibfk_2` FOREIGN KEY (`lang_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for multilanguage_links
-- ----------------------------
DROP TABLE IF EXISTS `multilanguage_links`;
CREATE TABLE `multilanguage_links`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `controller_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `path` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `language_id`(`language_id`, `controller_name`) USING BTREE,
  UNIQUE INDEX `link`(`path`) USING BTREE,
  CONSTRAINT `multilanguage_links_ibfk_1` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for phrases
-- ----------------------------
DROP TABLE IF EXISTS `phrases`;
CREATE TABLE `phrases`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phrase` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `en` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `bg` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `tr` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `phrase`(`phrase`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for phrases_platform
-- ----------------------------
DROP TABLE IF EXISTS `phrases_platform`;
CREATE TABLE `phrases_platform`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phrase` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `en` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `bg` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `tr` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `phrase`(`phrase`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 56 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of phrases_platform
-- ----------------------------
INSERT INTO `phrases_platform` VALUES (1, 'copy_link', 'Copy link', 'Копирай линка', NULL);
INSERT INTO `phrases_platform` VALUES (2, 'download', 'Download', 'Свали', NULL);
INSERT INTO `phrases_platform` VALUES (3, 'delete', 'Delete', 'Изтрий', NULL);
INSERT INTO `phrases_platform` VALUES (4, 'unlock', 'Unlock', 'Отключи', NULL);
INSERT INTO `phrases_platform` VALUES (5, 'lock', 'Lock', 'Заключи', NULL);
INSERT INTO `phrases_platform` VALUES (6, 'copied', 'Copied', 'Копирано', NULL);
INSERT INTO `phrases_platform` VALUES (7, 'the_following_fields_must_not_be_empty', 'The following fields must not be empty', 'Следните полета не трябва да са празни', NULL);
INSERT INTO `phrases_platform` VALUES (8, 'is_required', 'Is required', 'Е задължително', NULL);
INSERT INTO `phrases_platform` VALUES (9, 'the_following_fields_does_not_exist', 'The following fields does not exist', 'Следните полета не съществуват', NULL);
INSERT INTO `phrases_platform` VALUES (10, 'saved', 'Saved', 'Запаметено', NULL);
INSERT INTO `phrases_platform` VALUES (11, 'deleted', 'Deleted', 'Изтрито', NULL);
INSERT INTO `phrases_platform` VALUES (12, 'add_category', 'Add category', 'Добавете категория', NULL);
INSERT INTO `phrases_platform` VALUES (13, 'add_news', 'Add news', 'Добавете новина', NULL);
INSERT INTO `phrases_platform` VALUES (14, 'add_administrator', 'Add administrator', 'Добавете администратор', NULL);
INSERT INTO `phrases_platform` VALUES (15, 'you_cannot_delete_from', 'You cannot delete from', 'Не може да триете от', NULL);
INSERT INTO `phrases_platform` VALUES (17, 'if_there_are', 'if there are', 'ако има свързани', NULL);
INSERT INTO `phrases_platform` VALUES (18, 'attached_to_it', 'attached to it', 'свързани с него', NULL);
INSERT INTO `phrases_platform` VALUES (19, 'level', 'Level', 'Ниво', NULL);
INSERT INTO `phrases_platform` VALUES (20, 'names', 'Names', 'Имена', NULL);
INSERT INTO `phrases_platform` VALUES (21, 'names_latin', 'Names in Latin', 'Имена на Латиница', NULL);
INSERT INTO `phrases_platform` VALUES (22, 'search', 'Search', 'Търси', NULL);
INSERT INTO `phrases_platform` VALUES (23, 'searching', 'Searching', 'Търсене', NULL);
INSERT INTO `phrases_platform` VALUES (24, 'user_added', 'User added', 'Потребителят е добавен', NULL);
INSERT INTO `phrases_platform` VALUES (25, 'adminuser', 'Administrators', 'Администратори', NULL);
INSERT INTO `phrases_platform` VALUES (26, 'access_levels', 'Access levels', 'Нива на достъп', NULL);
INSERT INTO `phrases_platform` VALUES (27, 'access_level', 'Access level', 'Ниво на достъп', NULL);
INSERT INTO `phrases_platform` VALUES (28, 'add_access_level', 'Add access level', 'Добавете ниво на достъп', NULL);
INSERT INTO `phrases_platform` VALUES (29, 'role', 'Role', 'Роля', NULL);
INSERT INTO `phrases_platform` VALUES (30, 'access_levels_note', 'The lower the level, the higher the access. DO NOT use `0`.', 'Колкото е по-ниско нивото, толкова по-висок е достъпът. НE използвайте `0`.', NULL);
INSERT INTO `phrases_platform` VALUES (31, 'note', 'Note', 'Забележка', NULL);
INSERT INTO `phrases_platform` VALUES (32, 'must_be_a_number', 'Мust be a number', 'Трябва да е число', NULL);
INSERT INTO `phrases_platform` VALUES (35, 'should_be_between', 'Should be between', 'Трябва да е между', NULL);
INSERT INTO `phrases_platform` VALUES (37, 'and', 'and', 'и', NULL);
INSERT INTO `phrases_platform` VALUES (38, 'must_not_be_longer_than', 'Must not be longer than', 'Не трябва да е по дълго от', NULL);
INSERT INTO `phrases_platform` VALUES (39, 'sybmols', 'sybmols', 'символа', NULL);
INSERT INTO `phrases_platform` VALUES (40, 'tags', 'Tags', 'Тагове', NULL);
INSERT INTO `phrases_platform` VALUES (41, 'tag', 'Tag', 'Таг', NULL);
INSERT INTO `phrases_platform` VALUES (42, 'add_tag', 'Add tag', 'Добавете таг', NULL);
INSERT INTO `phrases_platform` VALUES (43, 'is_requried_for_language', 'Is required for language', 'Е задължително за език', NULL);
INSERT INTO `phrases_platform` VALUES (44, 'turkish', 'Turkish', 'Турски', NULL);
INSERT INTO `phrases_platform` VALUES (45, 'the_following_fields_are_required', 'The following fields are required', 'Следните полета са задължителни', NULL);
INSERT INTO `phrases_platform` VALUES (46, 'the_following', 'The following', ' ', NULL);
INSERT INTO `phrases_platform` VALUES (47, 'alredy_exists', 'already exists', 'вече съществува', NULL);
INSERT INTO `phrases_platform` VALUES (48, 'update', 'Update', 'Обнови', NULL);
INSERT INTO `phrases_platform` VALUES (49, 'actions', 'Actions', 'Действия', NULL);
INSERT INTO `phrases_platform` VALUES (50, 'profile', 'Profile', 'Профил', NULL);
INSERT INTO `phrases_platform` VALUES (51, 'rotate_right', 'Rotate right', 'Завърти на дясно', NULL);
INSERT INTO `phrases_platform` VALUES (52, 'rotate_left', 'Rotate left', 'Завърти на ляво', NULL);
INSERT INTO `phrases_platform` VALUES (53, 'browse', 'Browse', 'Избери', NULL);
INSERT INTO `phrases_platform` VALUES (54, 'messages', 'Messages', 'Съобщения', NULL);
INSERT INTO `phrases_platform` VALUES (55, 'requests', 'Requests', 'Заявки', NULL);

-- ----------------------------
-- Table structure for phrases_text
-- ----------------------------
DROP TABLE IF EXISTS `phrases_text`;
CREATE TABLE `phrases_text`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phrase` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `en` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
  `bg` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
  `tr` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `phrase`(`phrase`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
