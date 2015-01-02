# ************************************************************
# Sequel Pro SQL dump
# Version 4096
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: 127.0.0.1 (MySQL 5.6.19-0ubuntu0.14.04.1)
# Database: homestead
# Generation Time: 2015-01-02 19:01:30 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table account_meta
# ------------------------------------------------------------

DROP TABLE IF EXISTS `account_meta`;

CREATE TABLE `account_meta` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `account_id` int(10) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_meta_account_id_name_unique` (`account_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table account_types
# ------------------------------------------------------------

DROP TABLE IF EXISTS `account_types`;

CREATE TABLE `account_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `type` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `editable` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_types_type_unique` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `account_types` WRITE;
/*!40000 ALTER TABLE `account_types` DISABLE KEYS */;

INSERT INTO `account_types` (`id`, `created_at`, `updated_at`, `type`, `editable`)
VALUES
	(1,'2015-01-02 19:00:13','2015-01-02 19:00:13','Default account',1),
	(2,'2015-01-02 19:00:13','2015-01-02 19:00:13','Cash account',0),
	(3,'2015-01-02 19:00:13','2015-01-02 19:00:13','Asset account',1),
	(4,'2015-01-02 19:00:13','2015-01-02 19:00:13','Expense account',1),
	(5,'2015-01-02 19:00:13','2015-01-02 19:00:13','Revenue account',1),
	(6,'2015-01-02 19:00:13','2015-01-02 19:00:13','Initial balance account',0),
	(7,'2015-01-02 19:00:13','2015-01-02 19:00:13','Beneficiary account',1),
	(8,'2015-01-02 19:00:13','2015-01-02 19:00:13','Import account',0);

/*!40000 ALTER TABLE `account_types` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table accounts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `accounts`;

CREATE TABLE `accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `account_type_id` int(10) unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounts_user_id_account_type_id_name_unique` (`user_id`,`account_type_id`,`name`),
  KEY `accounts_account_type_id_foreign` (`account_type_id`),
  CONSTRAINT `accounts_account_type_id_foreign` FOREIGN KEY (`account_type_id`) REFERENCES `account_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table bills
# ------------------------------------------------------------

DROP TABLE IF EXISTS `bills`;

CREATE TABLE `bills` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `match` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `amount_min` decimal(10,2) NOT NULL,
  `amount_max` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `active` tinyint(1) NOT NULL,
  `automatch` tinyint(1) NOT NULL,
  `repeat_freq` enum('daily','weekly','monthly','quarterly','half-year','yearly') COLLATE utf8_unicode_ci NOT NULL,
  `skip` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_name_unique` (`user_id`,`name`),
  CONSTRAINT `bills_uid_for` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table budget_limits
# ------------------------------------------------------------

DROP TABLE IF EXISTS `budget_limits`;

CREATE TABLE `budget_limits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `budget_id` int(10) unsigned DEFAULT NULL,
  `startdate` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `repeats` tinyint(1) NOT NULL,
  `repeat_freq` enum('daily','weekly','monthly','quarterly','half-year','yearly') COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ci_combi` (`startdate`,`repeat_freq`),
  UNIQUE KEY `unique_bl_combi` (`budget_id`,`startdate`,`repeat_freq`),
  CONSTRAINT `bid_foreign` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table budget_transaction_journal
# ------------------------------------------------------------

DROP TABLE IF EXISTS `budget_transaction_journal`;

CREATE TABLE `budget_transaction_journal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `budget_id` int(10) unsigned NOT NULL,
  `transaction_journal_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budid_tjid_unique` (`budget_id`,`transaction_journal_id`),
  KEY `budget_transaction_journal_transaction_journal_id_foreign` (`transaction_journal_id`),
  CONSTRAINT `budget_transaction_journal_transaction_journal_id_foreign` FOREIGN KEY (`transaction_journal_id`) REFERENCES `transaction_journals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `budget_transaction_journal_budget_id_foreign` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table budgets
# ------------------------------------------------------------

DROP TABLE IF EXISTS `budgets`;

CREATE TABLE `budgets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budgets_user_id_name_unique` (`user_id`,`name`),
  CONSTRAINT `budgets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table categories
# ------------------------------------------------------------

DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_user_id_name_unique` (`user_id`,`name`),
  CONSTRAINT `categories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table category_transaction_journal
# ------------------------------------------------------------

DROP TABLE IF EXISTS `category_transaction_journal`;

CREATE TABLE `category_transaction_journal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `transaction_journal_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `catid_tjid_unique` (`category_id`,`transaction_journal_id`),
  KEY `category_transaction_journal_transaction_journal_id_foreign` (`transaction_journal_id`),
  CONSTRAINT `category_transaction_journal_transaction_journal_id_foreign` FOREIGN KEY (`transaction_journal_id`) REFERENCES `transaction_journals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_transaction_journal_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table components
# ------------------------------------------------------------

DROP TABLE IF EXISTS `components`;

CREATE TABLE `components` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `class` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `components_user_id_class_name_unique` (`user_id`,`class`,`name`),
  CONSTRAINT `components_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table limit_repetitions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `limit_repetitions`;

CREATE TABLE `limit_repetitions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `budget_limit_id` int(10) unsigned NOT NULL,
  `startdate` date NOT NULL,
  `enddate` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `limit_repetitions_limit_id_startdate_enddate_unique` (`budget_limit_id`,`startdate`,`enddate`),
  CONSTRAINT `limit_repetitions_limit_id_foreign` FOREIGN KEY (`budget_limit_id`) REFERENCES `budget_limits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table migrations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `migration` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;

INSERT INTO `migrations` (`migration`, `batch`)
VALUES
	('2014_06_27_163032_create_users_table',1),
	('2014_06_27_163145_create_account_types_table',1),
	('2014_06_27_163259_create_accounts_table',1),
	('2014_06_27_163817_create_components_table',1),
	('2014_06_27_163818_create_piggybanks_table',1),
	('2014_06_27_164042_create_transaction_currencies_table',1),
	('2014_06_27_164512_create_transaction_types_table',1),
	('2014_06_27_164619_create_recurring_transactions_table',1),
	('2014_06_27_164620_create_transaction_journals_table',1),
	('2014_06_27_164836_create_transactions_table',1),
	('2014_06_27_165344_create_component_transaction_table',1),
	('2014_07_05_171326_create_component_transaction_journal_table',1),
	('2014_07_06_123842_create_preferences_table',1),
	('2014_07_09_204843_create_session_table',1),
	('2014_07_17_183717_create_limits_table',1),
	('2014_07_19_055011_create_limit_repeat_table',1),
	('2014_08_06_044416_create_component_recurring_transaction_table',1),
	('2014_08_12_173919_create_piggybank_repetitions_table',1),
	('2014_08_18_100330_create_piggybank_events_table',1),
	('2014_08_23_113221_create_reminders_table',1),
	('2014_11_10_172053_create_account_meta_table',1),
	('2014_11_29_135749_create_transaction_groups_table',1),
	('2014_11_29_140217_create_transaction_group_transaction_journal_table',1),
	('2014_12_13_190730_changes_for_v321',1),
	('2014_12_24_191544_changes_for_v322',1);

/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table piggy_bank_events
# ------------------------------------------------------------

DROP TABLE IF EXISTS `piggy_bank_events`;

CREATE TABLE `piggy_bank_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `piggy_bank_id` int(10) unsigned NOT NULL,
  `transaction_journal_id` int(10) unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `piggybank_events_piggybank_id_foreign` (`piggy_bank_id`),
  KEY `piggybank_events_transaction_journal_id_foreign` (`transaction_journal_id`),
  CONSTRAINT `piggybank_events_transaction_journal_id_foreign` FOREIGN KEY (`transaction_journal_id`) REFERENCES `transaction_journals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `piggybank_events_piggybank_id_foreign` FOREIGN KEY (`piggy_bank_id`) REFERENCES `piggy_banks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table piggy_bank_repetitions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `piggy_bank_repetitions`;

CREATE TABLE `piggy_bank_repetitions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `piggy_bank_id` int(10) unsigned NOT NULL,
  `startdate` date DEFAULT NULL,
  `targetdate` date DEFAULT NULL,
  `currentamount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `piggybank_repetitions_piggybank_id_startdate_targetdate_unique` (`piggy_bank_id`,`startdate`,`targetdate`),
  CONSTRAINT `piggybank_repetitions_piggybank_id_foreign` FOREIGN KEY (`piggy_bank_id`) REFERENCES `piggy_banks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table piggy_banks
# ------------------------------------------------------------

DROP TABLE IF EXISTS `piggy_banks`;

CREATE TABLE `piggy_banks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `account_id` int(10) unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `targetamount` decimal(10,2) NOT NULL,
  `startdate` date DEFAULT NULL,
  `targetdate` date DEFAULT NULL,
  `repeats` tinyint(1) NOT NULL,
  `rep_length` enum('day','week','quarter','month','year') COLLATE utf8_unicode_ci DEFAULT NULL,
  `rep_every` smallint(5) unsigned NOT NULL,
  `rep_times` smallint(5) unsigned DEFAULT NULL,
  `reminder` enum('day','week','quarter','month','year') COLLATE utf8_unicode_ci DEFAULT NULL,
  `reminder_skip` smallint(5) unsigned NOT NULL,
  `remind_me` tinyint(1) NOT NULL,
  `order` int(10) unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `piggybanks_account_id_name_unique` (`account_id`,`name`),
  CONSTRAINT `piggybanks_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table preferences
# ------------------------------------------------------------

DROP TABLE IF EXISTS `preferences`;

CREATE TABLE `preferences` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `preferences_user_id_name_unique` (`user_id`,`name`),
  CONSTRAINT `preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table reminders
# ------------------------------------------------------------

DROP TABLE IF EXISTS `reminders`;

CREATE TABLE `reminders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_id` int(10) unsigned NOT NULL,
  `startdate` date NOT NULL,
  `enddate` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL,
  `notnow` tinyint(1) NOT NULL DEFAULT '0',
  `remindersable_id` int(10) unsigned DEFAULT NULL,
  `remindersable_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reminders_user_id_foreign` (`user_id`),
  CONSTRAINT `reminders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table sessions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `sessions`;

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `payload` text COLLATE utf8_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL,
  UNIQUE KEY `sessions_id_unique` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table transaction_currencies
# ------------------------------------------------------------

DROP TABLE IF EXISTS `transaction_currencies`;

CREATE TABLE `transaction_currencies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `code` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(48) COLLATE utf8_unicode_ci DEFAULT NULL,
  `symbol` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_currencies_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `transaction_currencies` WRITE;
/*!40000 ALTER TABLE `transaction_currencies` DISABLE KEYS */;

INSERT INTO `transaction_currencies` (`id`, `created_at`, `updated_at`, `deleted_at`, `code`, `name`, `symbol`)
VALUES
	(1,'2015-01-02 19:00:13','2015-01-02 19:00:13',NULL,'EUR','Euro','&#8364;'),
	(2,'2015-01-02 19:00:13','2015-01-02 19:00:13',NULL,'USD','US Dollar','$'),
	(3,'2015-01-02 19:00:13','2015-01-02 19:00:13',NULL,'HUF','Hungarian forint','Ft');

/*!40000 ALTER TABLE `transaction_currencies` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table transaction_group_transaction_journal
# ------------------------------------------------------------

DROP TABLE IF EXISTS `transaction_group_transaction_journal`;

CREATE TABLE `transaction_group_transaction_journal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_group_id` int(10) unsigned NOT NULL,
  `transaction_journal_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tt_joined` (`transaction_group_id`,`transaction_journal_id`),
  KEY `tr_trj_id` (`transaction_journal_id`),
  CONSTRAINT `tr_trj_id` FOREIGN KEY (`transaction_journal_id`) REFERENCES `transaction_journals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tr_grp_id` FOREIGN KEY (`transaction_group_id`) REFERENCES `transaction_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table transaction_groups
# ------------------------------------------------------------

DROP TABLE IF EXISTS `transaction_groups`;

CREATE TABLE `transaction_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `relation` enum('balance') COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_groups_user_id_foreign` (`user_id`),
  CONSTRAINT `transaction_groups_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table transaction_journals
# ------------------------------------------------------------

DROP TABLE IF EXISTS `transaction_journals`;

CREATE TABLE `transaction_journals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `transaction_type_id` int(10) unsigned NOT NULL,
  `bill_id` int(10) unsigned DEFAULT NULL,
  `transaction_currency_id` int(10) unsigned NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `completed` tinyint(1) NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_journals_user_id_foreign` (`user_id`),
  KEY `transaction_journals_transaction_type_id_foreign` (`transaction_type_id`),
  KEY `transaction_journals_transaction_currency_id_foreign` (`transaction_currency_id`),
  KEY `bill_id_foreign` (`bill_id`),
  CONSTRAINT `bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transaction_journals_transaction_currency_id_foreign` FOREIGN KEY (`transaction_currency_id`) REFERENCES `transaction_currencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_journals_transaction_type_id_foreign` FOREIGN KEY (`transaction_type_id`) REFERENCES `transaction_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_journals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table transaction_types
# ------------------------------------------------------------

DROP TABLE IF EXISTS `transaction_types`;

CREATE TABLE `transaction_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_types_type_unique` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `transaction_types` WRITE;
/*!40000 ALTER TABLE `transaction_types` DISABLE KEYS */;

INSERT INTO `transaction_types` (`id`, `created_at`, `updated_at`, `deleted_at`, `type`)
VALUES
	(1,'2015-01-02 19:00:13','2015-01-02 19:00:13',NULL,'Withdrawal'),
	(2,'2015-01-02 19:00:13','2015-01-02 19:00:13',NULL,'Deposit'),
	(3,'2015-01-02 19:00:13','2015-01-02 19:00:13',NULL,'Transfer'),
	(4,'2015-01-02 19:00:13','2015-01-02 19:00:13',NULL,'Opening balance');

/*!40000 ALTER TABLE `transaction_types` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table transactions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `transactions`;

CREATE TABLE `transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `transaction_journal_id` int(10) unsigned NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transactions_account_id_foreign` (`account_id`),
  KEY `transactions_transaction_journal_id_foreign` (`transaction_journal_id`),
  CONSTRAINT `transactions_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transactions_transaction_journal_id_foreign` FOREIGN KEY (`transaction_journal_id`) REFERENCES `transaction_journals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  `reset` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `remember_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
