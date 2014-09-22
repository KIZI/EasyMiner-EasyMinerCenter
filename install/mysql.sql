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
  `id_datasource` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `type` enum('mysql') COLLATE utf8_czech_ci NOT NULL,
  `db_server` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_username` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_password` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_table` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id_datasource`),
  KEY `is_user` (`id_user`)
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
  `id_miner` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  ```type``` enum('lm') NOT NULL,
  `id_datasource` int(11) NOT NULL,
  PRIMARY KEY (`id_miner`),
  KEY `id_user` (`id_user`),
  KEY `id_datasource` (`id_datasource`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table with definition of EasyMiner instances' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `email` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `password` varchar(200) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Table with data of users' AUTO_INCREMENT=1 ;

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `datasources`
--
ALTER TABLE `datasources`
ADD CONSTRAINT `datasources_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `helper_data`
--
ALTER TABLE `helper_data`
ADD CONSTRAINT `helper_data_ibfk_1` FOREIGN KEY (`miner`) REFERENCES `miners` (`id_miner`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `miners`
--
ALTER TABLE `miners`
ADD CONSTRAINT `miners_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `miners_ibfk_2` FOREIGN KEY (`id_datasource`) REFERENCES `datasources` (`id_datasource`) ON DELETE CASCADE ON UPDATE CASCADE;
