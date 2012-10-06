CREATE TABLE IF NOT EXISTS `translations` (
  `id` char(36) NOT NULL,
  `locale` char(5) NOT NULL DEFAULT 'en' COMMENT 'ISO 3166-1 alpha-2 country code + optional (_ + Region subtag). e.g. en_US',
  `domain` varchar(50) DEFAULT 'default',
  `category` varchar(50) DEFAULT 'LC_MESSAGES',
  `key` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `value` text,
  `has_plural` tinyint(1) DEFAULT 0 COMMENT 'If this is a singlular translation - is there a plural',
  `singular_key` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'If this is a plural translation, this is the singular form',
  `plural_case` tinyint(2) DEFAULT NULL COMMENT 'Only relevant for plural translations 0-6',
  PRIMARY KEY (`id`),
  UNIQUE KEY `locale` (`locale`,`domain`,`category`,`key`, `plural_case`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
