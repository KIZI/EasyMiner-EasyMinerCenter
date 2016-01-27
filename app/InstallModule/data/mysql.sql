
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attributes` (
  `attribute_id` int(11) NOT NULL AUTO_INCREMENT,
  `metasource_id` int(11) NOT NULL,
  `datasource_column_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `type` enum('string','float','int') COLLATE utf8_czech_ci NOT NULL,
  `preprocessing_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`attribute_id`),
  KEY `datasource_id` (`metasource_id`),
  KEY `datasource_column_id` (`datasource_column_id`),
  KEY `preprocessing_id` (`preprocessing_id`),
  CONSTRAINT `attributes_ibfk_1` FOREIGN KEY (`metasource_id`) REFERENCES `metasources` (`metasource_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `attributes_ibfk_3` FOREIGN KEY (`datasource_column_id`) REFERENCES `datasource_columns` (`datasource_column_id`) ON UPDATE CASCADE,
  CONSTRAINT `attributes_ibfk_4` FOREIGN KEY (`preprocessing_id`) REFERENCES `preprocessings` (`preprocessing_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=775 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující informaci o namapování datových sloupců na formáty z KB';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cedents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cedents` (
  `cedent_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `connective` enum('conjunction','disjunction','negation') COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`cedent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=270083 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující jednotlivé cedenty';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cedents_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cedents_relations` (
  `parent_cedent_id` bigint(20) NOT NULL,
  `child_cedent_id` bigint(20) NOT NULL,
  PRIMARY KEY (`parent_cedent_id`,`child_cedent_id`),
  KEY `child_cedent_id` (`child_cedent_id`),
  CONSTRAINT `cedents_relations_ibfk_1` FOREIGN KEY (`parent_cedent_id`) REFERENCES `cedents` (`cedent_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cedents_relations_ibfk_2` FOREIGN KEY (`child_cedent_id`) REFERENCES `cedents` (`cedent_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cedents_rule_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cedents_rule_attributes` (
  `cedent_id` bigint(20) NOT NULL,
  `rule_attribute_id` bigint(20) NOT NULL,
  PRIMARY KEY (`cedent_id`,`rule_attribute_id`),
  KEY `rule_attribute_id` (`rule_attribute_id`),
  CONSTRAINT `cedents_rule_attributes_ibfk_1` FOREIGN KEY (`cedent_id`) REFERENCES `cedents` (`cedent_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cedents_rule_attributes_ibfk_2` FOREIGN KEY (`rule_attribute_id`) REFERENCES `rule_attributes` (`rule_attribute_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `datasource_columns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `datasource_columns` (
  `datasource_column_id` int(11) NOT NULL AUTO_INCREMENT,
  `datasource_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `type` enum('string','float','int') COLLATE utf8_czech_ci NOT NULL,
  `str_len` smallint(6) DEFAULT NULL,
  `format_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`datasource_column_id`),
  KEY `datasource_id` (`datasource_id`),
  KEY `format_id` (`format_id`),
  CONSTRAINT `datasource_columns_ibfk_1` FOREIGN KEY (`datasource_id`) REFERENCES `datasources` (`datasource_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `datasource_columns_ibfk_2` FOREIGN KEY (`format_id`) REFERENCES `formats` (`format_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2958 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující informaci o namapování datových sloupců na formáty z KB';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `datasources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `datasources` (
  `datasource_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('mysql','limited','unlimited') COLLATE utf8_czech_ci NOT NULL,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `remote_id` int(11) DEFAULT NULL,
  `available` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Informace o tom, jestli daný datasource existuje',
  `db_server` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_port` smallint(6) DEFAULT NULL,
  `db_username` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_password` varchar(300) COLLATE utf8_czech_ci NOT NULL,
  `db_name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_table` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`datasource_id`),
  KEY `is_user` (`user_id`),
  CONSTRAINT `datasources_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Table with definition of databases with user data';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `formats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `formats` (
  `format_id` int(11) NOT NULL AUTO_INCREMENT,
  `meta_attribute_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `data_type` enum('values','interval') COLLATE utf8_czech_ci NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `shared` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`format_id`),
  KEY `meta_attribute_id` (`meta_attribute_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `formats_ibfk_1` FOREIGN KEY (`meta_attribute_id`) REFERENCES `meta_attributes` (`meta_attribute_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `formats_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2685 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující definici jednotlivých formátů';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `helper_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `helper_data` (
  `miner` int(11) NOT NULL,
  `type` enum('','clipboard','hiddenAttr','settings') NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`miner`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table with working data for easyminer';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `intervals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `intervals` (
  `interval_id` int(11) NOT NULL AUTO_INCREMENT,
  `format_id` int(11) DEFAULT NULL,
  `left_margin` float NOT NULL,
  `right_margin` float NOT NULL,
  `left_closure` enum('closed','open') COLLATE utf8_czech_ci NOT NULL,
  `right_closure` enum('closed','open') COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`interval_id`),
  KEY `format_id` (`format_id`),
  CONSTRAINT `intervals_ibfk_1` FOREIGN KEY (`format_id`) REFERENCES `formats` (`format_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2104 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_bases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knowledge_bases` (
  `knowledge_base_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`knowledge_base_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující názvy jednotlivých KnowledgeBase';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meta_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meta_attributes` (
  `meta_attribute_id` int(11) NOT NULL AUTO_INCREMENT,
  `knowledge_base_id` int(11) DEFAULT NULL,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`meta_attribute_id`),
  KEY `knowledge_base_id` (`knowledge_base_id`),
  CONSTRAINT `meta_attributes_ibfk_1` FOREIGN KEY (`knowledge_base_id`) REFERENCES `knowledge_bases` (`knowledge_base_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1562 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující definici metaatributů';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `metasources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `metasources` (
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
  KEY `miner_id` (`miner_id`),
  CONSTRAINT `metasources_ibfk_1` FOREIGN KEY (`miner_id`) REFERENCES `miners` (`miner_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `metasources_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=200 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Table with definition of databases with user data';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `miners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `miners` (
  `miner_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('lm','r') NOT NULL,
  `datasource_id` int(11) DEFAULT NULL,
  `metasource_id` int(11) DEFAULT NULL,
  `rule_set_id` int(11) DEFAULT NULL COMMENT 'ID rule setu, který je používán v souvislosti s tímto minerem',
  `config` text,
  `created` datetime DEFAULT NULL COMMENT 'Datum vytvoření mineru',
  `last_opened` datetime DEFAULT NULL COMMENT 'Datum posledního otevření mineru',
  PRIMARY KEY (`miner_id`),
  KEY `user_id` (`user_id`),
  KEY `datasource_id` (`datasource_id`),
  KEY `attributessource_id` (`metasource_id`),
  KEY `rule_set_id` (`rule_set_id`),
  CONSTRAINT `miners_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `miners_ibfk_2` FOREIGN KEY (`datasource_id`) REFERENCES `datasources` (`datasource_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `miners_ibfk_3` FOREIGN KEY (`metasource_id`) REFERENCES `metasources` (`metasource_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=123 DEFAULT CHARSET=utf8 COMMENT='Table with definition of EasyMiner instances';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `preprocessings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `preprocessings` (
  `preprocessing_id` int(11) NOT NULL AUTO_INCREMENT,
  `format_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `special_type` enum('','eachOne') COLLATE utf8_czech_ci NOT NULL DEFAULT '',
  `user_id` int(11) DEFAULT NULL,
  `shared` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`preprocessing_id`),
  KEY `format_id` (`format_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `preprocessings_ibfk_1` FOREIGN KEY (`format_id`) REFERENCES `formats` (`format_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `preprocessings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=623 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující seznam preprocessingů';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `preprocessings_values_bins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `preprocessings_values_bins` (
  `preprocessing_id` int(11) NOT NULL,
  `values_bin_id` int(11) NOT NULL,
  PRIMARY KEY (`preprocessing_id`,`values_bin_id`),
  KEY `values_bin_id` (`values_bin_id`),
  CONSTRAINT `preprocessings_values_bins_ibfk_1` FOREIGN KEY (`preprocessing_id`) REFERENCES `preprocessings` (`preprocessing_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `preprocessings_values_bins_ibfk_2` FOREIGN KEY (`values_bin_id`) REFERENCES `values_bins` (`values_bin_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rule_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rule_attributes` (
  `rule_attribute_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `attribute_id` int(11) DEFAULT NULL COMMENT 'ID atributu z tabulky attributes',
  `values_bin_id` int(11) DEFAULT NULL,
  `value_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`rule_attribute_id`),
  KEY `attribute_id` (`attribute_id`),
  KEY `values_bin_id` (`values_bin_id`),
  KEY `value_id` (`value_id`),
  CONSTRAINT `rule_attributes_ibfk_1` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`attribute_id`) ON UPDATE CASCADE,
  CONSTRAINT `rule_attributes_ibfk_2` FOREIGN KEY (`values_bin_id`) REFERENCES `values_bins` (`values_bin_id`) ON UPDATE CASCADE,
  CONSTRAINT `rule_attributes_ibfk_3` FOREIGN KEY (`value_id`) REFERENCES `values` (`value_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51107 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rule_set_rule_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rule_set_rule_relations` (
  `rule_set_rule_relation_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `rule_id` bigint(20) NOT NULL,
  `rule_set_id` int(11) NOT NULL,
  `relation` enum('positive','neutral','negative') COLLATE utf8_czech_ci NOT NULL,
  `priority` float NOT NULL COMMENT 'Priorita daného pravidla v rámci rule setu',
  PRIMARY KEY (`rule_set_rule_relation_id`),
  UNIQUE KEY `rule_id` (`rule_id`,`rule_set_id`),
  KEY `rule_set_id` (`rule_set_id`),
  CONSTRAINT `rule_set_rule_relations_ibfk_1` FOREIGN KEY (`rule_set_id`) REFERENCES `rule_sets` (`rule_set_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `rule_set_rule_relations_ibfk_2` FOREIGN KEY (`rule_id`) REFERENCES `rules` (`rule_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=206 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rule_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rule_sets` (
  `rule_set_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `description` varchar(200) COLLATE utf8_czech_ci NOT NULL,
  `rules_count` int(11) NOT NULL,
  PRIMARY KEY (`rule_set_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `rule_sets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující definici jednotlivých rulesetů';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rules` (
  `rule_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `text` varchar(200) COLLATE utf8_czech_ci NOT NULL,
  `pmml_rule_id` varchar(100) COLLATE utf8_czech_ci NOT NULL COMMENT 'ID pravidla v importním PMML',
  `antecedent` bigint(20) DEFAULT NULL,
  `consequent` bigint(20) DEFAULT NULL,
  `in_rule_clipboard` tinyint(1) NOT NULL DEFAULT '0',
  `a` bigint(20) unsigned NOT NULL,
  `b` bigint(20) unsigned NOT NULL,
  `c` bigint(20) unsigned NOT NULL,
  `d` bigint(20) unsigned NOT NULL,
  `confidence` float DEFAULT NULL,
  `support` float DEFAULT NULL,
  `lift` float DEFAULT NULL,
  PRIMARY KEY (`rule_id`),
  KEY `task_id` (`task_id`),
  KEY `consequent` (`consequent`),
  KEY `antecedent` (`antecedent`),
  CONSTRAINT `rules_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `rules_ibfk_2` FOREIGN KEY (`antecedent`) REFERENCES `cedents` (`cedent_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `rules_ibfk_3` FOREIGN KEY (`consequent`) REFERENCES `cedents` (`cedent_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1615992 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující nalezená asociační pravidla';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `task_id` int(11) NOT NULL AUTO_INCREMENT,
  `task_uuid` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `miner_id` int(11) NOT NULL,
  `type` enum('lm','r') COLLATE utf8_czech_ci NOT NULL,
  `name` varchar(200) COLLATE utf8_czech_ci NOT NULL,
  `state` enum('new','in_progress','solved','failed','interrupted','solved_heads') COLLATE utf8_czech_ci NOT NULL DEFAULT 'new',
  `rules_count` int(11) NOT NULL DEFAULT '0',
  `rules_in_rule_clipboard_count` int(11) NOT NULL DEFAULT '0',
  `rules_order` varchar(10) COLLATE utf8_czech_ci NOT NULL DEFAULT 'default' COMMENT 'Identitikace míry zajímavosti, podle které mají být pravidla řazena',
  `results_url` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'URL pro vyzvednutí výsledků úlohy',
  `task_settings_json` text COLLATE utf8_czech_ci NOT NULL,
  `import_state` enum('none','waiting','partial','done') COLLATE utf8_czech_ci NOT NULL DEFAULT 'none' COMMENT 'Informace o stavu dokončení importu',
  `import_json` text COLLATE utf8_czech_ci NOT NULL COMMENT 'JSON s informacemi pro postupný import dat',
  PRIMARY KEY (`task_id`),
  KEY `miner_id` (`miner_id`),
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`miner_id`) REFERENCES `miners` (`miner_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2281 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_forgotten_passwords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_forgotten_passwords` (
  `user_forgotten_password_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `generated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_forgotten_password_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_forgotten_passwords_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Tabulka obsahující bezpečnostní kódy pro změnu hesel';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `email` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `db_password` varchar(300) COLLATE utf8_czech_ci NOT NULL COMMENT 'Heslo pro přístup k databázi s daty',
  `password` varchar(200) COLLATE utf8_czech_ci NOT NULL,
  `facebook_id` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `google_id` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `api_key` varchar(45) COLLATE utf8_czech_ci NOT NULL,
  `last_login` datetime NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='Table with data of users';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values` (
  `value_id` int(11) NOT NULL AUTO_INCREMENT,
  `format_id` int(11) DEFAULT NULL,
  `value` varchar(200) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`value_id`),
  KEY `format_id` (`format_id`),
  KEY `value` (`value`),
  CONSTRAINT `values_ibfk_1` FOREIGN KEY (`format_id`) REFERENCES `formats` (`format_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=122320 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `values_bins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_bins` (
  `values_bin_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `format_id` int(11) NOT NULL,
  PRIMARY KEY (`values_bin_id`),
  KEY `format_id` (`format_id`),
  CONSTRAINT `values_bins_ibfk_1` FOREIGN KEY (`format_id`) REFERENCES `formats` (`format_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=378 DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `values_bins_intervals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_bins_intervals` (
  `values_bin_id` int(11) NOT NULL,
  `interval_id` int(11) NOT NULL,
  PRIMARY KEY (`values_bin_id`,`interval_id`),
  KEY `interval_id` (`interval_id`),
  CONSTRAINT `values_bins_intervals_ibfk_1` FOREIGN KEY (`values_bin_id`) REFERENCES `values_bins` (`values_bin_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `values_bins_intervals_ibfk_2` FOREIGN KEY (`interval_id`) REFERENCES `intervals` (`interval_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `values_bins_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `values_bins_values` (
  `values_bin_id` int(11) NOT NULL,
  `value_id` int(11) NOT NULL,
  PRIMARY KEY (`values_bin_id`,`value_id`),
  KEY `value_id` (`value_id`),
  CONSTRAINT `values_bins_values_ibfk_1` FOREIGN KEY (`values_bin_id`) REFERENCES `values_bins` (`values_bin_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `values_bins_values_ibfk_2` FOREIGN KEY (`value_id`) REFERENCES `values` (`value_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

