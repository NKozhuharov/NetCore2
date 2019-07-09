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
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `phrase`(`phrase`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 56 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of phrases_platform
-- ----------------------------
INSERT INTO `phrases_platform` VALUES (1, 'is_not_a_valid_link', 'is not a valid link', 'не е валиден линк');
INSERT INTO `phrases_platform` VALUES (2, 'is_not_valid_image', 'is not valid image', 'не е валидно изображение');
INSERT INTO `phrases_platform` VALUES (3, 'image_is_empty', 'Image is empty', 'Изображението е празно');
INSERT INTO `phrases_platform` VALUES (4, 'the_file', 'The file', 'Файлът');
INSERT INTO `phrases_platform` VALUES (5, 'image_must_not_be_bigger_than', 'Image must not be bigger than', 'Изображението не трябва да е по-голямо от');
INSERT INTO `phrases_platform` VALUES (6, 'the_following_fields_are_not_valid', 'The following fields are not valid', 'Следните полета са невалидни');
INSERT INTO `phrases_platform` VALUES (7, 'enter_email', 'Enter email', 'Въведете имейл');
INSERT INTO `phrases_platform` VALUES (8, 'enter_username', 'Enter username', 'Въведете потребителско име');
INSERT INTO `phrases_platform` VALUES (9, 'enter_password', 'Enter password', 'Въведете парола');
INSERT INTO `phrases_platform` VALUES (10, 'email_addres_or_password_is_invalid', 'Email addres or password is invalid', 'Имейлът или паролата са невалидни');
INSERT INTO `phrases_platform` VALUES (11, 'username_or_password_is_invalid', 'Username or password is invalid', 'Потребителското име или паролата са невалидни');
INSERT INTO `phrases_platform` VALUES (12, 'the_following_fields_are_required', 'The following fields are required', 'Следните полета са задължителни');
INSERT INTO `phrases_platform` VALUES (13, 'the_following_fields_must_not_be_empty', 'The following fields must not be empty', 'Следните полета не трябва да са празни');
INSERT INTO `phrases_platform` VALUES (14, 'you_cannot_delete_from', 'You cannot delete from', 'Не можете да изтривате от');
INSERT INTO `phrases_platform` VALUES (15, 'if_there_are', 'if there are', 'ако има свързани');
INSERT INTO `phrases_platform` VALUES (16, 'attached_to_it', 'attached to it', 'свързани с него');
INSERT INTO `phrases_platform` VALUES (17, 'the_following', 'The following', 'Следните');
INSERT INTO `phrases_platform` VALUES (18, 'already_exists', 'already exists', 'вече съществува');
INSERT INTO `phrases_platform` VALUES (19, 'this', 'This', 'Това');
INSERT INTO `phrases_platform` VALUES (20, 'does_not_exist', 'does not exist', 'не съществува');
INSERT INTO `phrases_platform` VALUES (21, 'is_not_allowed', 'is not allowed', 'не е позволено');
INSERT INTO `phrases_platform` VALUES (22, 'the_following_fields_does_not_exist', 'The following fields does not exist', 'Следните полета не съществуват');
INSERT INTO `phrases_platform` VALUES (23, 'this_email_is_already_taken', 'This email is already taken', 'Този имейл вече е зает');
INSERT INTO `phrases_platform` VALUES (24, 'provide_a_token', 'Provide a token', 'Въведете токен');
INSERT INTO `phrases_platform` VALUES (25, 'invalid_token', 'Invalid token', 'Невалиден токен');
INSERT INTO `phrases_platform` VALUES (26, 'this_email_does_not_exist', 'This email does not exist', 'Този имейл не съществува');
INSERT INTO `phrases_platform` VALUES (27, 'sorry,_there_has_been_some_kind_of_an_error_with_your_request._please_try_again_later.', 'Sorry, there has been some kind of an error with your request. Please try again later.', 'Съжаляваме, възникна някаква грешка с вашата заявка. Моля опитайте пак по-късно.');
INSERT INTO `phrases_platform` VALUES (28, 'phone_is_not_valid', 'Phone is not valid', 'Телефонът не е валиден');
INSERT INTO `phrases_platform` VALUES (29, 'name_is_not_valid', 'Name is not valid', 'Името не е валидно');
INSERT INTO `phrases_platform` VALUES (30, 'is_not_valid', 'is not valid', 'не е валидно');
INSERT INTO `phrases_platform` VALUES (31, 'image', 'image', 'изображение');
INSERT INTO `phrases_platform` VALUES (32, 'file_not_found', 'File not found', 'Файлът не е намерен');
INSERT INTO `phrases_platform` VALUES (33, 'nothing_to_update', 'Nothing to update', 'Няма какво да се актуализира');
INSERT INTO `phrases_platform` VALUES (34, 'invalid_hash', 'Invalid hash', 'Невалиден хаш');
INSERT INTO `phrases_platform` VALUES (35, 'invalid_file_source', 'Invalid file source', 'Невалиден файлов източник');
INSERT INTO `phrases_platform` VALUES (36, 'provide_a_valid_file', 'Provide a valid file', 'Въведете валиден фаил');
INSERT INTO `phrases_platform` VALUES (37, 'provide_email', 'Provide email', 'Въведете имейл');
INSERT INTO `phrases_platform` VALUES (38, 'email_is_not_valid', 'Email is not valid', 'Имейлът не е валиден');
INSERT INTO `phrases_platform` VALUES (39, 'password_must_contain_a_symbol', 'Password must contain a symbol', 'Паролата трябва да съдържа символ');
INSERT INTO `phrases_platform` VALUES (40, 'password_must_contain_a_capital_letter', 'Password must contain a capital letter', 'Паролата трябва да съдържа главна буква');
INSERT INTO `phrases_platform` VALUES (41, 'password_must_contain_a_number', 'Password must contain a number', 'Паролата трябва да съдържа число');
INSERT INTO `phrases_platform` VALUES (42, 'password_must_be_maximum', 'Password must be maximum', 'Паролата трябва да е максимум');
INSERT INTO `phrases_platform` VALUES (43, 'symbols', 'symbols', 'символи');
INSERT INTO `phrases_platform` VALUES (44, 'password_must_be_minimum', 'Password must be minimum', 'Паролата трябва да е минимум');
INSERT INTO `phrases_platform` VALUES (45, 'no_spaces_are_allowed_in_the_password', 'No spaces are allowed in the password', 'Не са разрешени интервали в паролата');
INSERT INTO `phrases_platform` VALUES (46, 'provide_password', 'Provide password', 'Въведете парола');
INSERT INTO `phrases_platform` VALUES (47, 'this_username_is_already_taken', 'This username is already taken', 'Това потребителско име вече е заето');
INSERT INTO `phrases_platform` VALUES (48, 'username_must_be_maximum', 'Username must be maximum', 'Потребителското име трябва да е максимум');
INSERT INTO `phrases_platform` VALUES (49, 'username_must_be_minimum', 'Username must be minimum', 'Потребителското име трябва да е минимум');
INSERT INTO `phrases_platform` VALUES (50, 'no_spaces_are_allowed_in_the_username', 'No spaces are allowed in the username', 'Не са разрешени интервали в потребителското име');
INSERT INTO `phrases_platform` VALUES (51, 'provide_username', 'Provide username', 'Въведете потребителско име');
INSERT INTO `phrases_platform` VALUES (52, 'this_user_does_not_exist', 'This user does not exist', 'Този потребител не съществува');
INSERT INTO `phrases_platform` VALUES (53, 'provide_a_list_with_ids', 'Provide a list with ids', 'Въведете списък с ID-та');
INSERT INTO `phrases_platform` VALUES (54, 'cannot_sort_by', 'Cannot sort by', 'Не може да сортирате по');
INSERT INTO `phrases_platform` VALUES (55, 'cannot_search_by', 'Cannot search by', 'Не може да търсите по');
INSERT INTO `phrases_platform` VALUES (56, 'invalid_page', 'Invalid page', 'Невалидна страница');
INSERT INTO `phrases_platform` VALUES (57, 'invalid_limits', 'Invalid limits', 'Невалидни лимити');
INSERT INTO `phrases_platform` VALUES (58, 'maximum', 'Maximum', 'Максимум');
INSERT INTO `phrases_platform` VALUES (59, 'items_per_page', 'items per page', 'елементи на страница');
INSERT INTO `phrases_platform` VALUES (60, 'you_cannot_vote_again_yet', 'You cannot vote again yet', 'Още не можете да гласувате отново');
INSERT INTO `phrases_platform` VALUES (61, 'invalid_ip_address', 'Invalid IP address', 'Невалиден IP адрес');
INSERT INTO `phrases_platform` VALUES (62, 'the_user_has_no_ip_address', 'The user has no IP address', 'Потребителят няма IP адрес');
INSERT INTO `phrases_platform` VALUES (63, 'all_questions_must_belong_to_a_single_survey', 'All questions must belong to a single survey', 'Всички въпроси трябва да принадлежат към една анкета');
INSERT INTO `phrases_platform` VALUES (64, 'one_question_can_have_only_one_answer', 'One question can have only one answer', 'Един въпрос може да има само един отговор');
INSERT INTO `phrases_platform` VALUES (65, 'one_or_more_answers_does_not_exist', 'One or more answers does not exist', 'Един или повече отговори не съществуват');
INSERT INTO `phrases_platform` VALUES (66, 'provide_at_least_one_answer', 'Provide at least one answer', 'Въведете поне един отговор');
INSERT INTO `phrases_platform` VALUES (67, 'this_survey_does_not_exist', 'This survey does not exist', 'Тази анкета не съществува');
INSERT INTO `phrases_platform` VALUES (68, 'cannot_delete_published_surveys', 'Cannot delete published surveys', 'Не може да изтриете публикувана анкета');
INSERT INTO `phrases_platform` VALUES (69, 'cannot_update_published_surveys', 'Cannot update published surveys', 'Не може да актуализирате публикувани анкети');
INSERT INTO `phrases_platform` VALUES (70, 'surveys_question', 'surveys question', 'въпроси на анкета');
INSERT INTO `phrases_platform` VALUES (71, 'surveys_answer', 'surveys answer', 'отговор на анкети');
INSERT INTO `phrases_platform` VALUES (72, 'each_answer_should_be_an_array', 'Each answer should be an array', 'Всеки отговор трябва да бъде масив');
INSERT INTO `phrases_platform` VALUES (73, 'provide_answers_for_the_questions', 'Provide answers for the questions', 'Въведете отговори на въпросите');
INSERT INTO `phrases_platform` VALUES (74, 'each_question_should_be_an_array', 'Each question should be an array', 'Всеки въпрос трябва да бъде масив');
INSERT INTO `phrases_platform` VALUES (75, 'provide_questions_for_the_survey', 'Provide questions for the survey', 'Въведете въпроси за анкетата');
INSERT INTO `phrases_platform` VALUES (76, 'invalid_action', 'Invalid action', 'Невалидно действие');
INSERT INTO `phrases_platform` VALUES (77, 'define_action', 'Define action', 'Дефинирайте действие');
INSERT INTO `phrases_platform` VALUES (78, 'no_spaces_are_allowed_in_the_action_parameter', 'No spaces are allowed in the action parameter', 'Не са разрешени интервали в параметъра за действие');
INSERT INTO `phrases_platform` VALUES (79, 'action_is_empty', 'Action is empty', 'Действието е празно');
INSERT INTO `phrases_platform` VALUES (80, 'current_page_must_be_bigger_than_0', 'Current page must be bigger than 0', 'Текущатат страница трябва да е по-голяма от 0');
INSERT INTO `phrases_platform` VALUES (81, 'the_items_per_page_count_must_be_bigger_than_0', 'The items per page count must be bigger than 0', 'Броят на елементите на страница трябва да е по-голям от 0');
INSERT INTO `phrases_platform` VALUES (82, 'you_cannot_add_an_empty_filter', 'You cannot add an empty filter', 'Не можете да добавите празен филтър');
INSERT INTO `phrases_platform` VALUES (83, 'you_cannot_remove_an_empty_filter', 'You cannot remove an empty filter', 'Не можете да премахнете празен филтър');
INSERT INTO `phrases_platform` VALUES (84, 'filter_name_cannot_be_empty', 'Filter name cannot be empty', 'Името на филтъра не може да бъде празно');
INSERT INTO `phrases_platform` VALUES (85, 'is_not_set', 'is not set', 'не съществува');
INSERT INTO `phrases_platform` VALUES (86, 'filter', 'Filter', 'Филтър');
INSERT INTO `phrases_platform` VALUES (87, 'you_have_to_be_logged_in_to_do_that', 'You have to be logged in to do that', 'Трябва да сте влезли, за да направите това');
INSERT INTO `phrases_platform` VALUES (88, 'you_are_exceding_the_api_query_limit', 'You are exceding the API query limit', 'Превишавате API лимита за заявки');
INSERT INTO `phrases_platform` VALUES (89, 'cannot_translate_to_the_default_language', 'Cannot translate to the default language', 'Не може превеждате към езика по подразбиране');
INSERT INTO `phrases_platform` VALUES (90, 'language', 'Language', 'Език');
INSERT INTO `phrases_platform` VALUES (91, 'provide_an_object_id', 'Provide an object id', 'Въведете ID на обекта');
INSERT INTO `phrases_platform` VALUES (92, 'cannot_insert_or_update_an_empty_object', 'Cannot insert or update an empty object', 'Не може да добавите или актуализирате празен обект');
INSERT INTO `phrases_platform` VALUES (10000, 'copy_link', 'Copy link', 'Копирай линка');
INSERT INTO `phrases_platform` VALUES (10001, 'download', 'Download', 'Свали');
INSERT INTO `phrases_platform` VALUES (10002, 'delete', 'Delete', 'Изтрий');
INSERT INTO `phrases_platform` VALUES (10003, 'unlock', 'Unlock', 'Отключи');
INSERT INTO `phrases_platform` VALUES (10004, 'lock', 'Lock', 'Заключи');
INSERT INTO `phrases_platform` VALUES (10005, 'copied', 'Copied', 'Копирано');
INSERT INTO `phrases_platform` VALUES (10007, 'is_required', 'Is required', 'Е задължително');
INSERT INTO `phrases_platform` VALUES (10009, 'saved', 'Saved', 'Запаметено');
INSERT INTO `phrases_platform` VALUES (10010, 'deleted', 'Deleted', 'Изтрито');
INSERT INTO `phrases_platform` VALUES (10011, 'add_category', 'Add category', 'Добавете категория');
INSERT INTO `phrases_platform` VALUES (10012, 'add_news', 'Add news', 'Добавете новина');
INSERT INTO `phrases_platform` VALUES (10013, 'add_administrator', 'Add administrator', 'Добавете администратор');
INSERT INTO `phrases_platform` VALUES (10017, 'level', 'Level', 'Ниво');
INSERT INTO `phrases_platform` VALUES (10018, 'names', 'Names', 'Имена');
INSERT INTO `phrases_platform` VALUES (10019, 'names_latin', 'Names in Latin', 'Имена на Латиница');
INSERT INTO `phrases_platform` VALUES (10020, 'search', 'Search', 'Търси');
INSERT INTO `phrases_platform` VALUES (10021, 'searching', 'Searching', 'Търсене');
INSERT INTO `phrases_platform` VALUES (10022, 'user_added', 'User added', 'Потребителят е добавен');
INSERT INTO `phrases_platform` VALUES (10023, 'adminuser', 'Administrators', 'Администратори');
INSERT INTO `phrases_platform` VALUES (10024, 'access_levels', 'Access levels', 'Нива на достъп');
INSERT INTO `phrases_platform` VALUES (10025, 'access_level', 'Access level', 'Ниво на достъп');
INSERT INTO `phrases_platform` VALUES (10026, 'add_access_level', 'Add access level', 'Добавете ниво на достъп');
INSERT INTO `phrases_platform` VALUES (10027, 'role', 'Role', 'Роля');
INSERT INTO `phrases_platform` VALUES (10028, 'access_levels_note', 'The lower the level, the higher the access. DO NOT use `0`.', 'Колкото е по-ниско нивото, толкова по-висок е достъпът. НE използвайте `0`.');
INSERT INTO `phrases_platform` VALUES (10029, 'note', 'Note', 'Забележка');
INSERT INTO `phrases_platform` VALUES (10030, 'must_be_a_number', 'Мust be a number', 'Трябва да е число');
INSERT INTO `phrases_platform` VALUES (10031, 'should_be_between', 'Should be between', 'Трябва да е между');
INSERT INTO `phrases_platform` VALUES (10032, 'and', 'and', 'и');
INSERT INTO `phrases_platform` VALUES (10033, 'add', 'Аdd', 'Добави');
INSERT INTO `phrases_platform` VALUES (10034, 'list', 'List', 'Списък');
INSERT INTO `phrases_platform` VALUES (10035, 'administrator', 'Administrator', 'Администратор');
INSERT INTO `phrases_platform` VALUES (10036, 'administrators', 'Administrators', 'Администратори');
INSERT INTO `phrases_platform` VALUES (10037, 'log', 'Log', 'Лог');
INSERT INTO `phrases_platform` VALUES (10038, 'logs', 'Logs', 'Логове');
INSERT INTO `phrases_platform` VALUES (10039, 'admin_panel', 'Admin Panel', 'Администраторски панел');
INSERT INTO `phrases_platform` VALUES (10040, 'logout', 'Logout', 'Изход');
INSERT INTO `phrases_platform` VALUES (10041, 'username', 'Username', 'Потребителско име');
INSERT INTO `phrases_platform` VALUES (10042, 'phone', 'Phone', 'Телефон');
INSERT INTO `phrases_platform` VALUES (10043, 'birth_date', 'Birth date', 'Рожденна дата');
INSERT INTO `phrases_platform` VALUES (10044, 'address', 'Address', 'Адрес');
INSERT INTO `phrases_platform` VALUES (10045, 'email', 'Email', 'Имейл');
INSERT INTO `phrases_platform` VALUES (10046, 'password', 'Password', 'Парола');
INSERT INTO `phrases_platform` VALUES (10047, 'repeat_password', 'Repeat password', 'Повтори паролата');
INSERT INTO `phrases_platform` VALUES (10048, 'save', 'Save', 'Запис');
INSERT INTO `phrases_platform` VALUES (10049, 'upload', 'Upload', 'Качване');
INSERT INTO `phrases_platform` VALUES (10050, 'documents', 'Documents', 'Документи');
INSERT INTO `phrases_platform` VALUES (10051, 'document', 'Document', 'Документ');
INSERT INTO `phrases_platform` VALUES (10052, 'or', 'or', 'или');
INSERT INTO `phrases_platform` VALUES (10053, 'are_you_sure', 'Are you sure?', 'Сигурни ли сте?');
INSERT INTO `phrases_platform` VALUES (10054, 'yes', 'Yes', 'Да');
INSERT INTO `phrases_platform` VALUES (10055, 'no', 'No', 'Не');
INSERT INTO `phrases_platform` VALUES (10056, 'no_results', 'No results', 'Няма резултати');
INSERT INTO `phrases_platform` VALUES (10057, 'user_inserted', 'User inserted successfully!', 'Потребителя е създаден успешно!');
INSERT INTO `phrases_platform` VALUES (10061, 'ok', 'OK', 'ОК');
INSERT INTO `phrases_platform` VALUES (10062, 'login', 'Login', 'Вход');
INSERT INTO `phrases_platform` VALUES (10063, 'page_not_found', 'Page not found', 'Страницата не е намерена');
INSERT INTO `phrases_platform` VALUES (10065, 'description', 'Description', 'Описание');
INSERT INTO `phrases_platform` VALUES (10066, 'name', 'Name', 'Име');
INSERT INTO `phrases_platform` VALUES (10067, 'surname', 'Surname', 'Фамилия');
INSERT INTO `phrases_platform` VALUES (10068, 'country', 'Country', 'Държава');
INSERT INTO `phrases_platform` VALUES (10069, 'countries', 'Countries', 'Държави');
INSERT INTO `phrases_platform` VALUES (10070, 'area', 'Area', 'Област');
INSERT INTO `phrases_platform` VALUES (10071, 'areas', 'Areas', 'Области');
INSERT INTO `phrases_platform` VALUES (10072, 'town', 'Town', 'Град');
INSERT INTO `phrases_platform` VALUES (10073, 'towns', 'Towns', 'Градове');
INSERT INTO `phrases_platform` VALUES (10074, 'city', 'City', 'Град');
INSERT INTO `phrases_platform` VALUES (10075, 'cities', 'Cities', 'Градове');
INSERT INTO `phrases_platform` VALUES (10079, 'error', 'Error', 'Грешка');
INSERT INTO `phrases_platform` VALUES (10081, 'vat_number', 'VAT number', 'ДДС номер');
INSERT INTO `phrases_platform` VALUES (10082, 'country_id', 'Country', 'Държава');
INSERT INTO `phrases_platform` VALUES (10083, 'area_id', 'Area', 'Област');
INSERT INTO `phrases_platform` VALUES (10084, 'town_id', 'Town', 'Град');
INSERT INTO `phrases_platform` VALUES (10085, 'iban_code', 'IBAN', 'IBAN');
INSERT INTO `phrases_platform` VALUES (10086, 'bic_code', 'BIC', 'BIC');
INSERT INTO `phrases_platform` VALUES (10087, 'swift_code', 'SWIFT', 'SWIFT');
INSERT INTO `phrases_platform` VALUES (10088, 'translate', 'Translate', 'Преведи');
INSERT INTO `phrases_platform` VALUES (10089, 'translation', 'Translation', 'Превод');
INSERT INTO `phrases_platform` VALUES (10090, 'added', 'Added', 'Дата на създаване');
INSERT INTO `phrases_platform` VALUES (10091, 'back', 'Back', 'Назад');
INSERT INTO `phrases_platform` VALUES (10092, 'disable_access', 'Disable access', 'Забрани достъпа');
INSERT INTO `phrases_platform` VALUES (10093, 'enable_access', 'Enable access', 'Позволи достъпа');
INSERT INTO `phrases_platform` VALUES (10095, 'random_password', 'Random password', 'Произволна парола');
INSERT INTO `phrases_platform` VALUES (10096, 'generate_password', 'Generate password', 'Генерирай парола');
INSERT INTO `phrases_platform` VALUES (10097, 'passwords_do_not_match', 'Passwords do not match', 'Паролите не съвпадат');
INSERT INTO `phrases_platform` VALUES (10099, 'symbol', 'Symbol', 'Символ');
INSERT INTO `phrases_platform` VALUES (10100, 'must_not_be_longer_than', 'Must not be longer than ', 'Не трябва да е повече от');
INSERT INTO `phrases_platform` VALUES (10111, 'change_password', 'Change password', 'Промени паролата');
INSERT INTO `phrases_platform` VALUES (10112, 'password_changed_successfully', 'Password changed successfully', 'Паролата е сменена успешно');
INSERT INTO `phrases_platform` VALUES (10113, 'register', 'Register', 'Регистрация');
INSERT INTO `phrases_platform` VALUES (10116, 'active', 'Active', 'В действие');
INSERT INTO `phrases_platform` VALUES (10117, 'choose_user', 'Choose user', 'Избери потребител');
INSERT INTO `phrases_platform` VALUES (10118, 'all', 'All', 'Всички');
INSERT INTO `phrases_platform` VALUES (10119, 'query', 'Query', 'Заявка');
INSERT INTO `phrases_platform` VALUES (10120, 'mysql_query_execution_error', 'MySQL query execution error', 'Грешка при изъплнението на MySQL заявка');
INSERT INTO `phrases_platform` VALUES (10121, 'enter_a_username_and_password_to_login', 'Enter a username and password to login', 'Въведете потребителско име и парола за вход');
INSERT INTO `phrases_platform` VALUES (10122, 'user_level_is_not_supported_or_invalid', 'User level is not supported or invalid', 'Нивото на потребителя е невалидно или не се поддържа');
INSERT INTO `phrases_platform` VALUES (10123, 'provide_user_data', 'Provide user data', 'Въведете потребителски данни');
-- ----------------------------
-- Table structure for phrases_text
-- ----------------------------
DROP TABLE IF EXISTS `phrases_text`;
CREATE TABLE `phrases_text`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phrase` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `en` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
  `bg` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `phrase`(`phrase`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
