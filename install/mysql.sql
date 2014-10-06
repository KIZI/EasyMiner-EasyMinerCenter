-- phpMyAdmin SQL Dump
-- version 4.1.6
-- http://www.phpmyadmin.net
--
-- Počítač: 127.0.0.1
-- Vytvořeno: Pon 22. zář 2014, 16:28
-- Verze serveru: 5.6.16
-- Verze PHP: 5.5.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Databáze: `brserver2`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `datasources`
--

CREATE TABLE IF NOT EXISTS `datasources` (
  `datasource_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('mysql') COLLATE utf8_czech_ci NOT NULL,
  `db_server` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_username` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_password` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_table` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`datasource_id`),
  KEY `is_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Table with definition of databases with user data' AUTO_INCREMENT=1 ;

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
-- Struktura tabulky `miners`
--

CREATE TABLE IF NOT EXISTS `miners` (
  `miner_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  ```type``` enum('lm','r') NOT NULL,
  `datasource_id` int(11) NOT NULL,
  PRIMARY KEY (`miner_id`),
  KEY `user_id` (`user_id`),
  KEY `datasource_id` (`datasource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table with definition of EasyMiner instances' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `email` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `password` varchar(200) COLLATE utf8_czech_ci NOT NULL,
  `facebook_id` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `google_id` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `last_login` datetime NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Table with data of users' AUTO_INCREMENT=1 ;

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `datasources`
--
ALTER TABLE `datasources`
ADD CONSTRAINT `datasources_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `helper_data`
--
ALTER TABLE `helper_data`
ADD CONSTRAINT `helper_data_ibfk_1` FOREIGN KEY (`miner`) REFERENCES `miners` (`miner_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `miners`
--
ALTER TABLE `miners`
ADD CONSTRAINT `miners_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `miners_ibfk_2` FOREIGN KEY (`datasource_id`) REFERENCES `datasources` (`datasource_id`) ON DELETE CASCADE ON UPDATE CASCADE;
