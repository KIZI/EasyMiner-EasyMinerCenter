-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb2+deb7u1
-- http://www.phpmyadmin.net
--
-- Počítač: localhost
-- Vygenerováno: Čtv 20. lis 2014, 23:17
-- Verze MySQL: 5.5.40
-- Verze PHP: 5.4.35-0+deb7u2

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
  `preprocessing_id` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`attribute_id`),
  KEY `datasource_id` (`metasource_id`),
  KEY `datasource_column_id` (`datasource_column_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující informaci o namapování datových sloupců na formáty z KB' AUTO_INCREMENT=12 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Table with definition of databases with user data' AUTO_INCREMENT=4 ;

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
  `format_id` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`datasource_column_id`),
  KEY `datasource_id` (`datasource_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující informaci o namapování datových sloupců na formáty z KB' AUTO_INCREMENT=18 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Table with definition of databases with user data' AUTO_INCREMENT=114 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Table with definition of EasyMiner instances' AUTO_INCREMENT=6 ;

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
  `rules_count` int(11) NOT NULL DEFAULT '0',
  `task_settings_json` text COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`task_id`),
  KEY `miner_id` (`miner_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci AUTO_INCREMENT=42 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Table with data of users' AUTO_INCREMENT=6 ;

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `attributes`
--
ALTER TABLE `attributes`
ADD CONSTRAINT `attributes_ibfk_2` FOREIGN KEY (`datasource_column_id`) REFERENCES `datasource_columns` (`datasource_column_id`),
ADD CONSTRAINT `attributes_ibfk_1` FOREIGN KEY (`metasource_id`) REFERENCES `metasources` (`metasource_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `datasources`
--
ALTER TABLE `datasources`
ADD CONSTRAINT `datasources_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `datasource_columns`
--
ALTER TABLE `datasource_columns`
ADD CONSTRAINT `datasource_columns_ibfk_1` FOREIGN KEY (`datasource_id`) REFERENCES `datasources` (`datasource_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `helper_data`
--
ALTER TABLE `helper_data`
ADD CONSTRAINT `helper_data_ibfk_1` FOREIGN KEY (`miner`) REFERENCES `miners` (`miner_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `metasources`
--
ALTER TABLE `metasources`
ADD CONSTRAINT `metasources_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `metasources_ibfk_1` FOREIGN KEY (`miner_id`) REFERENCES `miners` (`miner_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `miners`
--
ALTER TABLE `miners`
ADD CONSTRAINT `miners_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `miners_ibfk_2` FOREIGN KEY (`datasource_id`) REFERENCES `datasources` (`datasource_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `miners_ibfk_3` FOREIGN KEY (`metasource_id`) REFERENCES `metasources` (`metasource_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `tasks`
--
ALTER TABLE `tasks`
ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`miner_id`) REFERENCES `miners` (`miner_id`) ON DELETE CASCADE ON UPDATE CASCADE;
