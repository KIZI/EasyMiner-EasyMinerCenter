-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb2+deb7u1
-- http://www.phpmyadmin.net
--
-- Počítač: localhost
-- Vygenerováno: Stř 04. bře 2015, 00:45
-- Verze MySQL: 5.5.41
-- Verze PHP: 5.4.36-0+deb7u3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Databáze: `easyminercenter`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `attributes`
--

CREATE TABLE IF NOT EXISTS `attributes` (
  `attribute_id` int(11) NOT NULL AUTO_INCREMENT,
  `metasource_id` int(11) NOT NULL,
  `datasource_column_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `type` enum('string','float','int') COLLATE utf8_czech_ci NOT NULL,
  `preprocessing_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`attribute_id`),
  KEY `datasource_id` (`metasource_id`),
  KEY `datasource_column_id` (`datasource_column_id`),
  KEY `preprocessing_id` (`preprocessing_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující informaci o namapování datových sloupců na formáty z KB' AUTO_INCREMENT=92 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `cedents`
--

CREATE TABLE IF NOT EXISTS `cedents` (
  `cedent_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `connective` enum('conjunction','disjunction','negation') COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`cedent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující jednotlivé cedenty' AUTO_INCREMENT=7605 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `cedents_relations`
--

CREATE TABLE IF NOT EXISTS `cedents_relations` (
  `parent_cedent_id` bigint(20) NOT NULL,
  `child_cedent_id` bigint(20) NOT NULL,
  PRIMARY KEY (`parent_cedent_id`,`child_cedent_id`),
  KEY `child_cedent_id` (`child_cedent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `cedents_rule_attributes`
--

CREATE TABLE IF NOT EXISTS `cedents_rule_attributes` (
  `cedent_id` bigint(20) NOT NULL,
  `rule_attribute_id` bigint(20) NOT NULL,
  PRIMARY KEY (`cedent_id`,`rule_attribute_id`),
  KEY `rule_attribute_id` (`rule_attribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `datasources`
--

CREATE TABLE IF NOT EXISTS `datasources` (
  `datasource_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('mysql','cassandra') COLLATE utf8_czech_ci NOT NULL,
  `db_server` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_port` smallint(6) DEFAULT NULL,
  `db_username` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_password` varchar(300) COLLATE utf8_czech_ci NOT NULL,
  `db_name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_table` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`datasource_id`),
  KEY `is_user` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Table with definition of databases with user data' AUTO_INCREMENT=26 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `datasource_columns`
--

CREATE TABLE IF NOT EXISTS `datasource_columns` (
  `datasource_column_id` int(11) NOT NULL AUTO_INCREMENT,
  `datasource_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `type` enum('string','float','int') COLLATE utf8_czech_ci NOT NULL,
  `str_len` smallint(6) DEFAULT NULL,
  `format_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`datasource_column_id`),
  KEY `datasource_id` (`datasource_id`),
  KEY `format_id` (`format_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující informaci o namapování datových sloupců na formáty z KB' AUTO_INCREMENT=170 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `formats`
--

CREATE TABLE IF NOT EXISTS `formats` (
  `format_id` int(11) NOT NULL AUTO_INCREMENT,
  `meta_attribute_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `data_type` enum('values','interval') COLLATE utf8_czech_ci NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `shared` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`format_id`),
  KEY `meta_attribute_id` (`meta_attribute_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující definici jednotlivých formátů' AUTO_INCREMENT=91 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `helper_data`
--

CREATE TABLE IF NOT EXISTS `helper_data` (
  `miner` int(11) NOT NULL,
  `type` enum('','clipboard','hiddenAttr','settings') NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`miner`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table with working data for easyminer';

-- --------------------------------------------------------

--
-- Struktura tabulky `intervals`
--

CREATE TABLE IF NOT EXISTS `intervals` (
  `interval_id` int(11) NOT NULL AUTO_INCREMENT,
  `format_id` int(11) DEFAULT NULL,
  `left_margin` float NOT NULL,
  `right_margin` float NOT NULL,
  `left_closure` enum('closed','open') COLLATE utf8_czech_ci NOT NULL,
  `right_closure` enum('closed','open') COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`interval_id`),
  KEY `format_id` (`format_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=62 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `knowledge_bases`
--

CREATE TABLE IF NOT EXISTS `knowledge_bases` (
  `knowledge_base_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`knowledge_base_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující názvy jednotlivých KnowledgeBase' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `metasources`
--

CREATE TABLE IF NOT EXISTS `metasources` (
  `metasource_id` int(11) NOT NULL AUTO_INCREMENT,
  `miner_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('mysql','cassandra') COLLATE utf8_czech_ci NOT NULL,
  `db_server` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_port` smallint(6) DEFAULT NULL,
  `db_username` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_password` varchar(300) COLLATE utf8_czech_ci NOT NULL,
  `db_name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `attributes_table` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`metasource_id`),
  KEY `is_user` (`user_id`),
  KEY `miner_id` (`miner_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Table with definition of databases with user data' AUTO_INCREMENT=128 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `meta_attributes`
--

CREATE TABLE IF NOT EXISTS `meta_attributes` (
  `meta_attribute_id` int(11) NOT NULL AUTO_INCREMENT,
  `knowledge_base_id` int(11) DEFAULT NULL,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`meta_attribute_id`),
  KEY `knowledge_base_id` (`knowledge_base_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující definici metaatributů' AUTO_INCREMENT=37 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `miners`
--

CREATE TABLE IF NOT EXISTS `miners` (
  `miner_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('lm','r') NOT NULL,
  `datasource_id` int(11) DEFAULT NULL,
  `metasource_id` int(11) DEFAULT NULL,
  `config` text,
  `created` datetime DEFAULT NULL COMMENT 'Datum vytvoření mineru',
  `last_opened` datetime DEFAULT NULL COMMENT 'Datum posledního otevření mineru',
  PRIMARY KEY (`miner_id`),
  KEY `user_id` (`user_id`),
  KEY `datasource_id` (`datasource_id`),
  KEY `attributessource_id` (`metasource_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Table with definition of EasyMiner instances' AUTO_INCREMENT=23 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `preprocessings`
--

CREATE TABLE IF NOT EXISTS `preprocessings` (
  `preprocessing_id` int(11) NOT NULL AUTO_INCREMENT,
  `format_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `special_type` enum('','eachOne') COLLATE utf8_czech_ci NOT NULL DEFAULT '',
  `user_id` int(11) DEFAULT NULL,
  `shared` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`preprocessing_id`),
  KEY `format_id` (`format_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující seznam preprocessingů' AUTO_INCREMENT=42 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `preprocessings_values_bins`
--

CREATE TABLE IF NOT EXISTS `preprocessings_values_bins` (
  `preprocessing_id` int(11) NOT NULL,
  `values_bin_id` int(11) NOT NULL,
  PRIMARY KEY (`preprocessing_id`,`values_bin_id`),
  KEY `values_bin_id` (`values_bin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `rules`
--

CREATE TABLE IF NOT EXISTS `rules` (
  `rule_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `text` varchar(200) COLLATE utf8_czech_ci NOT NULL,
  `antecedent` bigint(20) DEFAULT NULL,
  `consequent` bigint(20) NOT NULL,
  `in_rule_clipboard` tinyint(1) NOT NULL DEFAULT '0',
  `a` int(11) NOT NULL,
  `b` int(11) NOT NULL,
  `c` int(11) NOT NULL,
  `d` int(11) NOT NULL,
  `confidence` float DEFAULT NULL,
  `support` float DEFAULT NULL,
  `lift` float DEFAULT NULL,
  PRIMARY KEY (`rule_id`),
  KEY `task_id` (`task_id`),
  KEY `consequent` (`consequent`),
  KEY `antecedent` (`antecedent`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující nalezená asociační pravidla' AUTO_INCREMENT=6652 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `rule_attributes`
--

CREATE TABLE IF NOT EXISTS `rule_attributes` (
  `rule_attribute_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `field` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `value` varchar(200) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`rule_attribute_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=6723 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `tasks`
--

CREATE TABLE IF NOT EXISTS `tasks` (
  `task_id` int(11) NOT NULL AUTO_INCREMENT,
  `task_uuid` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `miner_id` int(11) NOT NULL,
  `type` enum('lm','r') COLLATE utf8_czech_ci NOT NULL,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `state` enum('new','in_progress','solved','failed','interrupted') COLLATE utf8_czech_ci NOT NULL DEFAULT 'new',
  `in_rule_clipboard` tinyint(1) NOT NULL DEFAULT '0',
  `rules_count` int(11) NOT NULL DEFAULT '0',
  `rules_in_rule_clipboard_count` int(11) NOT NULL DEFAULT '0',
  `results_url` varchar(100) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'URL pro vyzvednutí výsledků úlohy',
  `task_settings_json` text COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`task_id`),
  KEY `miner_id` (`miner_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=947 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `email` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_password` varchar(300) COLLATE utf8_czech_ci NOT NULL COMMENT 'Heslo pro přístup k databázi s daty',
  `password` varchar(200) COLLATE utf8_czech_ci NOT NULL,
  `facebook_id` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `google_id` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `last_login` datetime NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Table with data of users' AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `user_forgotten_passwords`
--

CREATE TABLE IF NOT EXISTS `user_forgotten_passwords` (
  `user_forgotten_password_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `generated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_forgotten_password_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující bezpečnostní kódy pro změnu hesel' AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `values`
--

CREATE TABLE IF NOT EXISTS `values` (
  `value_id` int(11) NOT NULL AUTO_INCREMENT,
  `format_id` int(11) DEFAULT NULL,
  `value` varchar(200) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`value_id`),
  KEY `format_id` (`format_id`),
  KEY `value` (`value`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=1495 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `values_bins`
--

CREATE TABLE IF NOT EXISTS `values_bins` (
  `values_bin_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `format_id` int(11) NOT NULL,
  PRIMARY KEY (`values_bin_id`),
  KEY `format_id` (`format_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=42 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `values_bins_intervals`
--

CREATE TABLE IF NOT EXISTS `values_bins_intervals` (
  `values_bin_id` int(11) NOT NULL,
  `interval_id` int(11) NOT NULL,
  PRIMARY KEY (`values_bin_id`,`interval_id`),
  KEY `interval_id` (`interval_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `values_bins_values`
--

CREATE TABLE IF NOT EXISTS `values_bins_values` (
  `values_bin_id` int(11) NOT NULL,
  `value_id` int(11) NOT NULL,
  PRIMARY KEY (`values_bin_id`,`value_id`),
  KEY `value_id` (`value_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `attributes`
--
ALTER TABLE `attributes`
ADD CONSTRAINT `attributes_ibfk_4` FOREIGN KEY (`preprocessing_id`) REFERENCES `preprocessings` (`preprocessing_id`) ON UPDATE CASCADE,
ADD CONSTRAINT `attributes_ibfk_1` FOREIGN KEY (`metasource_id`) REFERENCES `metasources` (`metasource_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `attributes_ibfk_3` FOREIGN KEY (`datasource_column_id`) REFERENCES `datasource_columns` (`datasource_column_id`) ON UPDATE CASCADE;

--
-- Omezení pro tabulku `cedents_relations`
--
ALTER TABLE `cedents_relations`
ADD CONSTRAINT `cedents_relations_ibfk_1` FOREIGN KEY (`parent_cedent_id`) REFERENCES `cedents` (`cedent_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `cedents_relations_ibfk_2` FOREIGN KEY (`child_cedent_id`) REFERENCES `cedents` (`cedent_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `cedents_rule_attributes`
--
ALTER TABLE `cedents_rule_attributes`
ADD CONSTRAINT `cedents_rule_attributes_ibfk_1` FOREIGN KEY (`cedent_id`) REFERENCES `cedents` (`cedent_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `cedents_rule_attributes_ibfk_2` FOREIGN KEY (`rule_attribute_id`) REFERENCES `rule_attributes` (`rule_attribute_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `datasources`
--
ALTER TABLE `datasources`
ADD CONSTRAINT `datasources_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `datasource_columns`
--
ALTER TABLE `datasource_columns`
ADD CONSTRAINT `datasource_columns_ibfk_2` FOREIGN KEY (`format_id`) REFERENCES `formats` (`format_id`) ON UPDATE CASCADE,
ADD CONSTRAINT `datasource_columns_ibfk_1` FOREIGN KEY (`datasource_id`) REFERENCES `datasources` (`datasource_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `formats`
--
ALTER TABLE `formats`
ADD CONSTRAINT `formats_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
ADD CONSTRAINT `formats_ibfk_1` FOREIGN KEY (`meta_attribute_id`) REFERENCES `meta_attributes` (`meta_attribute_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `helper_data`
--
ALTER TABLE `helper_data`
ADD CONSTRAINT `helper_data_ibfk_1` FOREIGN KEY (`miner`) REFERENCES `miners` (`miner_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `intervals`
--
ALTER TABLE `intervals`
ADD CONSTRAINT `intervals_ibfk_1` FOREIGN KEY (`format_id`) REFERENCES `formats` (`format_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `metasources`
--
ALTER TABLE `metasources`
ADD CONSTRAINT `metasources_ibfk_1` FOREIGN KEY (`miner_id`) REFERENCES `miners` (`miner_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `metasources_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `meta_attributes`
--
ALTER TABLE `meta_attributes`
ADD CONSTRAINT `meta_attributes_ibfk_1` FOREIGN KEY (`knowledge_base_id`) REFERENCES `knowledge_bases` (`knowledge_base_id`) ON UPDATE CASCADE;

--
-- Omezení pro tabulku `miners`
--
ALTER TABLE `miners`
ADD CONSTRAINT `miners_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `miners_ibfk_2` FOREIGN KEY (`datasource_id`) REFERENCES `datasources` (`datasource_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `miners_ibfk_3` FOREIGN KEY (`metasource_id`) REFERENCES `metasources` (`metasource_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `preprocessings`
--
ALTER TABLE `preprocessings`
ADD CONSTRAINT `preprocessings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `preprocessings_ibfk_1` FOREIGN KEY (`format_id`) REFERENCES `formats` (`format_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `preprocessings_values_bins`
--
ALTER TABLE `preprocessings_values_bins`
ADD CONSTRAINT `preprocessings_values_bins_ibfk_2` FOREIGN KEY (`values_bin_id`) REFERENCES `values_bins` (`values_bin_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `preprocessings_values_bins_ibfk_1` FOREIGN KEY (`preprocessing_id`) REFERENCES `preprocessings` (`preprocessing_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `rules`
--
ALTER TABLE `rules`
ADD CONSTRAINT `rules_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `rules_ibfk_2` FOREIGN KEY (`antecedent`) REFERENCES `cedents` (`cedent_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `rules_ibfk_3` FOREIGN KEY (`consequent`) REFERENCES `cedents` (`cedent_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `tasks`
--
ALTER TABLE `tasks`
ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`miner_id`) REFERENCES `miners` (`miner_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `user_forgotten_passwords`
--
ALTER TABLE `user_forgotten_passwords`
ADD CONSTRAINT `user_forgotten_passwords_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `values`
--
ALTER TABLE `values`
ADD CONSTRAINT `values_ibfk_1` FOREIGN KEY (`format_id`) REFERENCES `formats` (`format_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `values_bins`
--
ALTER TABLE `values_bins`
ADD CONSTRAINT `values_bins_ibfk_1` FOREIGN KEY (`format_id`) REFERENCES `formats` (`format_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `values_bins_intervals`
--
ALTER TABLE `values_bins_intervals`
ADD CONSTRAINT `values_bins_intervals_ibfk_2` FOREIGN KEY (`interval_id`) REFERENCES `intervals` (`interval_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `values_bins_intervals_ibfk_1` FOREIGN KEY (`values_bin_id`) REFERENCES `values_bins` (`values_bin_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `values_bins_values`
--
ALTER TABLE `values_bins_values`
ADD CONSTRAINT `values_bins_values_ibfk_2` FOREIGN KEY (`value_id`) REFERENCES `values` (`value_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `values_bins_values_ibfk_1` FOREIGN KEY (`values_bin_id`) REFERENCES `values_bins` (`values_bin_id`) ON DELETE CASCADE ON UPDATE CASCADE;
