-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: asup_nilest_journals
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `asup_nilest_journals`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `asup_nilest_journals` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `asup_nilest_journals`;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(500) NOT NULL,
  `published_at` date NOT NULL,
  `summary` text NOT NULL,
  `url` varchar(500) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,'The Leather and Leather Products Industry in Nigeria: Approaches to Sustainable Manufacturing and Marketing','2026-10-02','A book project exploring sustainable manufacturing systems, innovative processing technologies, environmental management, market development, policy frameworks, and strategic investment pathways for Nigeria\'s leather industry.','https://nijostam.org/journals/index.php/njstm/announcement/view/1',1);
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(60) DEFAULT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_created` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (6,NULL,'user.register','user',6,'author account created for smoke.0c6f145a@example.test','','2026-10-20 23:46:11'),(7,NULL,'user.register','user',7,'editor account created for smoke.editor.b6b385@example.test','','2026-10-20 23:46:11'),(8,NULL,'user.register','user',8,'reviewer account created for smoke.reviewer.090ea5@example.test','','2026-10-20 23:46:11'),(10,NULL,'user.register','user',9,'author account created for author.bd951e@example.test','','2026-10-21 00:31:56'),(11,NULL,'user.register','user',10,'editor account created for editor.bd951e@example.test','','2026-10-21 00:31:57'),(12,NULL,'user.register','user',11,'reviewer account created for reviewer.bd951e@example.test','','2026-10-21 00:31:57'),(13,NULL,'user.register','user',12,'author account created for admin.bd951e@example.test','','2026-10-21 00:31:57'),(14,NULL,'user.register','user',13,'author account created for other.bd951e@example.test','','2026-10-21 00:31:57'),(16,NULL,'user.register','user',14,'author account created for author.341424@example.test','','2026-10-21 00:32:12'),(17,NULL,'user.register','user',15,'editor account created for editor.341424@example.test','','2026-10-21 00:32:12'),(18,NULL,'user.register','user',16,'reviewer account created for reviewer.341424@example.test','','2026-10-21 00:32:13'),(19,NULL,'user.register','user',17,'author account created for admin.341424@example.test','','2026-10-21 00:32:13'),(20,NULL,'user.register','user',18,'author account created for other.341424@example.test','','2026-10-21 00:32:13'),(22,NULL,'user.register','user',19,'author account created for author.6e983c@example.test','','2026-10-21 00:32:52'),(23,NULL,'user.register','user',20,'editor account created for editor.6e983c@example.test','','2026-10-21 00:32:52'),(24,NULL,'user.register','user',21,'reviewer account created for reviewer.6e983c@example.test','','2026-10-21 00:32:52'),(25,NULL,'user.register','user',22,'author account created for admin.6e983c@example.test','','2026-10-21 00:32:52'),(26,NULL,'user.register','user',23,'author account created for other.6e983c@example.test','','2026-10-21 00:32:52'),(28,NULL,'user.register','user',24,'author account created for smoke.ec2dcc90@example.test','','2026-10-21 00:32:53'),(29,NULL,'user.register','user',25,'editor account created for smoke.editor.ae778d@example.test','','2026-10-21 00:32:53'),(30,NULL,'user.register','user',26,'reviewer account created for smoke.reviewer.b73c9f@example.test','','2026-10-21 00:32:53'),(32,NULL,'user.register','user',27,'author account created for smoke.15d6f6d9@example.test','','2026-10-21 00:33:13'),(33,NULL,'user.register','user',28,'editor account created for smoke.editor.6ed198@example.test','','2026-10-21 00:33:13'),(34,NULL,'user.register','user',29,'reviewer account created for smoke.reviewer.50555e@example.test','','2026-10-21 00:33:13'),(36,NULL,'user.register','user',30,'author account created for smoke.aa2bc30d@example.test','','2026-10-21 00:33:33'),(37,NULL,'user.register','user',31,'editor account created for smoke.editor.1812b3@example.test','','2026-10-21 00:33:33'),(38,NULL,'user.register','user',32,'reviewer account created for smoke.reviewer.ec4fa0@example.test','','2026-10-21 00:33:33'),(40,NULL,'user.register','user',33,'author account created for demo.author@nijostam.test','','2026-10-21 00:34:00'),(41,NULL,'user.register','user',34,'editor account created for demo.editor@nijostam.test','','2026-10-21 00:34:00'),(42,NULL,'user.register','user',35,'reviewer account created for demo.reviewer@nijostam.test','','2026-10-21 00:34:00'),(43,NULL,'user.register','user',36,'author account created for demo.admin@nijostam.test','','2026-10-21 00:34:00'),(44,NULL,'user.register','user',37,'author account created for webtest.4a85a586@example.test','::1','2026-10-21 00:42:52'),(45,NULL,'auth.register','user',37,'Self-registered author account','::1','2026-10-21 00:42:52'),(46,NULL,'auth.logout','user',37,'User signed out','::1','2026-10-21 00:42:53'),(47,NULL,'auth.login','user',37,'User signed in','::1','2026-10-21 00:42:53'),(48,NULL,'user.register','user',38,'author account created for webtest.7bb7b768@example.test','::1','2026-10-21 00:43:28'),(49,NULL,'auth.register','user',38,'Self-registered author account','::1','2026-10-21 00:43:28'),(50,NULL,'user.register','user',39,'author account created for webtest.327023ff@example.test','::1','2026-10-21 00:44:26'),(51,NULL,'auth.register','user',39,'Self-registered author account','::1','2026-10-21 00:44:26'),(52,NULL,'auth.logout','user',39,'User signed out','::1','2026-10-21 00:44:27'),(53,NULL,'auth.login','user',39,'User signed in','::1','2026-10-21 00:44:27'),(54,NULL,'user.register','user',40,'author account created for webtest.4ebd79c1@example.test','::1','2026-10-21 00:44:58'),(55,NULL,'auth.register','user',40,'Self-registered author account','::1','2026-10-21 00:44:58'),(56,NULL,'auth.logout','user',40,'User signed out','::1','2026-10-21 00:44:58'),(57,NULL,'auth.login','user',40,'User signed in','::1','2026-10-21 00:44:59'),(58,NULL,'user.register','user',41,'author account created for webtest.22c34e90@example.test','::1','2026-10-21 09:16:49'),(59,NULL,'auth.register','user',41,'Self-registered author account','::1','2026-10-21 09:16:49'),(60,NULL,'auth.logout','user',41,'User signed out','::1','2026-10-21 09:16:56'),(61,NULL,'auth.login','user',41,'User signed in','::1','2026-10-21 09:16:57'),(62,NULL,'user.register','user',42,'author account created for webtest.53dea1c5@example.test','::1','2026-10-21 09:37:01'),(63,NULL,'auth.register','user',42,'Self-registered author account','::1','2026-10-21 09:37:01'),(64,NULL,'auth.logout','user',42,'User signed out','::1','2026-10-21 09:37:02'),(65,NULL,'auth.login','user',42,'User signed in','::1','2026-10-21 09:37:02'),(66,NULL,'user.register','user',43,'author account created for webtest.f9fc41e2@example.test','::1','2026-10-21 10:23:01'),(67,NULL,'auth.register','user',43,'Self-registered author account','::1','2026-10-21 10:23:01'),(68,NULL,'manuscript.submit','manuscript',10,'NIJOSTAM-2026-0002 submitted','::1','2026-10-21 10:23:02'),(70,NULL,'auth.logout','user',43,'User signed out','::1','2026-10-21 10:23:03'),(71,NULL,'auth.login','user',43,'User signed in','::1','2026-10-21 10:23:03');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `author_profiles`
--

DROP TABLE IF EXISTS `author_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `author_profiles` (
  `user_id` bigint(20) unsigned NOT NULL,
  `affiliation` varchar(255) DEFAULT NULL,
  `department` varchar(180) DEFAULT NULL,
  `institution` varchar(255) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `is_corresponding` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_author_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `author_profiles`
--

LOCK TABLES `author_profiles` WRITE;
/*!40000 ALTER TABLE `author_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `author_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(254) NOT NULL,
  `subject` varchar(180) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','closed') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_contact_messages_status_created` (`status`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `editor_assignments`
--

DROP TABLE IF EXISTS `editor_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `editor_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `manuscript_id` bigint(20) unsigned NOT NULL,
  `editor_id` bigint(20) unsigned NOT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `screening_status` varchar(32) DEFAULT NULL,
  `screening_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ea_manuscript` (`manuscript_id`),
  KEY `idx_ea_editor` (`editor_id`),
  CONSTRAINT `fk_ea_editor` FOREIGN KEY (`editor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ea_manuscript` FOREIGN KEY (`manuscript_id`) REFERENCES `manuscripts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `editor_assignments`
--

LOCK TABLES `editor_assignments` WRITE;
/*!40000 ALTER TABLE `editor_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `editor_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `editorial_decisions`
--

DROP TABLE IF EXISTS `editorial_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `editorial_decisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `manuscript_id` bigint(20) unsigned NOT NULL,
  `editor_id` bigint(20) unsigned NOT NULL,
  `decision` varchar(40) NOT NULL,
  `letter` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ed_manuscript` (`manuscript_id`),
  KEY `fk_ed_editor` (`editor_id`),
  CONSTRAINT `fk_ed_editor` FOREIGN KEY (`editor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ed_manuscript` FOREIGN KEY (`manuscript_id`) REFERENCES `manuscripts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `editorial_decisions`
--

LOCK TABLES `editorial_decisions` WRITE;
/*!40000 ALTER TABLE `editorial_decisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `editorial_decisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `journal_issues`
--

DROP TABLE IF EXISTS `journal_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_issues` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `volume` varchar(12) NOT NULL,
  `issue_number` varchar(12) NOT NULL,
  `period` varchar(80) NOT NULL,
  `published_at` date NOT NULL,
  `cover_url` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_issues`
--

LOCK TABLES `journal_issues` WRITE;
/*!40000 ALTER TABLE `journal_issues` DISABLE KEYS */;
INSERT INTO `journal_issues` VALUES (1,'4','1','January - June, 2026','2026-06-30','https://nijostam.org/journals/public/journals/1/cover_issue_4_en.jpg','2026-10-20 00:40:58'),(2,'3','2','April - June, 2025','2025-06-30','https://nijostam.org/journals/public/journals/1/cover_issue_8_en.jpg','2026-10-20 00:40:58'),(3,'4','1','January - March, 2025','2025-03-30','https://nijostam.org/journals/public/journals/1/cover_issue_1_en.jpg','2026-10-20 00:40:58'),(4,'2','2','July - December, 2025','2025-12-30','https://nijostam.org/journals/public/journals/1/cover_issue_2_en.jpg','2026-10-20 00:40:58'),(5,'2','1','January - June, 2024','2024-06-30','https://nijostam.org/journals/public/journals/1/cover_issue_7_en.jpg','2026-10-20 00:40:58'),(6,'1','1','July - December, 2023','2023-12-30','https://nijostam.org/journals/public/journals/1/cover_issue_3_en.jpg','2026-10-20 00:40:58');
/*!40000 ALTER TABLE `journal_issues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `manuscript_authors`
--

DROP TABLE IF EXISTS `manuscript_authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `manuscript_authors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `manuscript_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(160) NOT NULL,
  `email` varchar(254) DEFAULT NULL,
  `affiliation` varchar(255) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `is_corresponding` tinyint(1) NOT NULL DEFAULT 0,
  `author_order` smallint(5) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ma_manuscript` (`manuscript_id`),
  KEY `fk_ma_user` (`user_id`),
  CONSTRAINT `fk_ma_manuscript` FOREIGN KEY (`manuscript_id`) REFERENCES `manuscripts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ma_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `manuscript_authors`
--

LOCK TABLES `manuscript_authors` WRITE;
/*!40000 ALTER TABLE `manuscript_authors` DISABLE KEYS */;
/*!40000 ALTER TABLE `manuscript_authors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `manuscript_files`
--

DROP TABLE IF EXISTS `manuscript_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `manuscript_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `manuscript_id` bigint(20) unsigned NOT NULL,
  `file_type` varchar(32) NOT NULL DEFAULT 'manuscript',
  `original_name` varchar(255) NOT NULL,
  `stored_path` varchar(500) NOT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `size_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
  `version` smallint(5) unsigned NOT NULL DEFAULT 1,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mf_manuscript` (`manuscript_id`),
  CONSTRAINT `fk_mf_manuscript` FOREIGN KEY (`manuscript_id`) REFERENCES `manuscripts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `manuscript_files`
--

LOCK TABLES `manuscript_files` WRITE;
/*!40000 ALTER TABLE `manuscript_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `manuscript_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `manuscripts`
--

DROP TABLE IF EXISTS `manuscripts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `manuscripts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference_no` varchar(32) NOT NULL,
  `title` varchar(600) NOT NULL,
  `abstract` text DEFAULT NULL,
  `keywords` varchar(500) DEFAULT NULL,
  `subject` varchar(120) DEFAULT NULL,
  `article_type` varchar(60) DEFAULT NULL,
  `language` varchar(40) NOT NULL DEFAULT 'English',
  `status` varchar(32) NOT NULL DEFAULT 'submitted',
  `revision_round` smallint(5) unsigned NOT NULL DEFAULT 0,
  `submitted_by` bigint(20) unsigned NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `screened_at` datetime DEFAULT NULL,
  `screened_by` bigint(20) unsigned DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `decided_by` bigint(20) unsigned DEFAULT NULL,
  `decision` varchar(40) DEFAULT NULL,
  `published_article_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_manuscripts_reference` (`reference_no`),
  KEY `idx_manuscripts_status` (`status`),
  KEY `idx_manuscripts_submitter` (`submitted_by`),
  KEY `idx_manuscripts_submitted_at` (`submitted_at`),
  CONSTRAINT `fk_manuscripts_submitter` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `manuscripts`
--

LOCK TABLES `manuscripts` WRITE;
/*!40000 ALTER TABLE `manuscripts` DISABLE KEYS */;
/*!40000 ALTER TABLE `manuscripts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(40) NOT NULL DEFAULT 'general',
  `title` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(300) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `published_articles`
--

DROP TABLE IF EXISTS `published_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `published_articles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `issue_id` int(10) unsigned NOT NULL,
  `title` varchar(600) NOT NULL,
  `authors` text NOT NULL,
  `pages` varchar(30) NOT NULL,
  `category` varchar(100) NOT NULL,
  `article_url` varchar(500) NOT NULL,
  `pdf_url` varchar(500) NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `manuscript_id` bigint(20) unsigned DEFAULT NULL,
  `doi` varchar(120) DEFAULT NULL,
  `abstract` text DEFAULT NULL,
  `keywords` varchar(500) DEFAULT NULL,
  `published_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_articles_issue` (`issue_id`),
  CONSTRAINT `fk_articles_issue` FOREIGN KEY (`issue_id`) REFERENCES `journal_issues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `published_articles`
--

LOCK TABLES `published_articles` WRITE;
/*!40000 ALTER TABLE `published_articles` DISABLE KEYS */;
INSERT INTO `published_articles` VALUES (1,1,'Physico-mechanical properties of dual compatibilized luffa sponge / recycled low density polyethylene (rLDPE) composites','Henry Onweneke Opara, David Ibeabuchi Onyemachi, Leo Yakubu Zango, Eli Musa, Ebere Promise Akabuogu, Beatrice Kadanga','1-7','Materials science','https://nijostam.org/journals/index.php/njstm/article/view/77','https://nijostam.org/journals/index.php/njstm/article/view/77/51',1,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(2,1,'Interactive antifungal relationships of Callitris glaucophylla, Acalypha wilkesiana and Jatropha curcas methanol extracts against Malassezia furfur isolated from children with suspected Seborrheic dermatitis','Mustapha Umar, Jamil Garba Abubakar, Joseph Odey Oko, Sagir Muhammad Rabiu, Ajiya Garba Kilgori, Aliyu Mansur Sulaiman, Faiza Muhammad Danjuma, Albert Amos Kigbu, Salisu Yusuf, Saudat Bashir Mustapha, Khalid Muhammad Sani','8-16','Health sciences','https://nijostam.org/journals/index.php/njstm/article/view/44','https://nijostam.org/journals/index.php/njstm/article/view/44/49',2,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(3,1,'The static mechanical and abrasion resistance properties of dual compatibilized cow horn fibre - recycled low density polyethylene composites','Henry Onweneke Opara, David Ibeabuchi Onyemachi, Ebere Promise Akabuogu, Leo Yakubu Zango, Grace Otini Idoko, John Abba','17-22','Materials science','https://nijostam.org/journals/index.php/njstm/article/view/78','https://nijostam.org/journals/index.php/njstm/article/view/78/52',3,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(4,1,'Neural network-based prediction of leather manufacturing defects: A tabular data approach for quality assurance in Nigerian tanneries','O. A. Abdullahi, J. E. Agida, Jafar Mikail, U. B. Agballa, U. C. Odonwodo, Hamza Abdulqadir, I. M. Karagama, J. T. Bahago, Garba Zakariyya','23-29','Technology','https://nijostam.org/journals/index.php/njstm/article/view/17','https://nijostam.org/journals/index.php/njstm/article/view/17/50',4,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(5,1,'Physical and chemical resistance properties of dual compatibilized biofiller / waste low density polyethylene composites','Henry Onweneke Opara, David Ibeabuchi Onyemachi, Eli Musa, Leo Yakubu Zango, Blessing Ikasuwa Samuel, Oluwatomilola Deborah Adeoye','30-35','Materials science','https://nijostam.org/journals/index.php/njstm/article/view/79','https://nijostam.org/journals/index.php/njstm/article/view/79/53',5,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(6,1,'Silent threat in the Wupa waterway: Multidrug-resistant Vibrio species, MAR indices, and resistance gene profiles as indicators of a compromised wastewater treatment system in Abuja, Nigeria','Ini-Abasi Ibangha','36-45','Environmental science','https://nijostam.org/journals/index.php/njstm/article/view/72','https://nijostam.org/journals/index.php/njstm/article/view/72/48',6,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(7,1,'In-silico evaluation of Parkia biglobosa plant extract for the preservation of raw hides and skins','Habila Bulus, Olusegun Debola Oshibanjo, Tachye Ninas Shekarri, Jonathan Danladi Gaiya','46-54','Leather science','https://nijostam.org/journals/index.php/njstm/article/view/22','https://nijostam.org/journals/index.php/njstm/article/view/22/55',7,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(8,2,'Sociodemographic Predictors of Health Insurance Enrolment in Imo State, Nigeria: A Cross-Sectional Facility-Based Study','Uchenna D. Mmereole, Stanley J. Ozims, Chinagorom O. Asinobi','1-13','Health sciences','https://nijostam.org/journals/index.php/njstm/article/view/51','https://nijostam.org/journals/index.php/njstm/article/view/51/30',1,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(9,3,'An Overview of Energy Efficiency Policies and Initiatives in Nigeria','Abdulrauf A. Ibrahim, Aminu O. Yusuf, Musa B. Dalil','1-10','Management','https://nijostam.org/journals/index.php/njstm/article/view/3','https://nijostam.org/journals/index.php/njstm/article/view/3/2',1,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(10,3,'Heat Treatment of High Carbon Steel for Engineering and Industrial Application','Aminu O. Yusuf, Aishat O. Salawu, Bucham J. Osang','11-19','Engineering','https://nijostam.org/journals/index.php/njstm/article/view/1','https://nijostam.org/journals/index.php/njstm/article/view/1/1',2,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(11,5,'Development of brake pads for automobiles and other industrial applications using locally sourced biomaterials','A. A. Ibrahim, A. N. Mohammed, L. I. Atiku','1-6','Engineering','https://nijostam.org/journals/index.php/njstm/article/view/85','https://nijostam.org/journals/index.php/njstm/article/view/85/56',1,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(12,5,'Investigation of the optimum condition of particle size concentration, time and water for the production of vegetable tanned Capra hircus (Sokoto Red Goat Leather)','Ekwuribe S.A, Ajibola V.O., Abechi S.E., Akawu P.I.','7-13','Leather science','https://nijostam.org/journals/index.php/njstm/article/view/86','https://nijostam.org/journals/index.php/njstm/article/view/86/57',2,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(13,5,'Mycological studies of dermatophytosis associated with alopecic animal hides and skin','M. Umar, G.K. Ajiya, M.R. Sagir, M.L. Riskuwa-Shehu, J.O. Oko, S.F. Ojeleye, A. Aliyu, F.M. Danjuma, A.K. Albert, I.C. Amuta','14-18','Health sciences','https://nijostam.org/journals/index.php/njstm/article/view/87','https://nijostam.org/journals/index.php/njstm/article/view/87/58',3,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(14,5,'An alternative approach for solving a system of linear equations using the matrix inversion technique','J.D. Galadima, U.E. Okon, A.A. Obafemi, L.E. Esezobor','19-22','Mathematics','https://nijostam.org/journals/index.php/njstm/article/view/88','https://nijostam.org/journals/index.php/njstm/article/view/88/59',4,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(15,5,'A comparison of certain matrix-based techniques for solving systems of equations','U.E. Okon, J.D. Galadima, L.E. Esezobo, A.A. Obafemi','23-25','Mathematics','https://nijostam.org/journals/index.php/njstm/article/view/89','https://nijostam.org/journals/index.php/njstm/article/view/89/60',5,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(16,5,'Impact of tricyanoethynlbenzoic acid anti-aggregant on dye-sensitised solar cells??? photovoltaic performance','Obadahun J., Bello K.A., Yakubu M.K., Abdelmalik A.A., Osazee O.B., Samuel B.I., Omotola M.B., Oladimeji R.A.','26-30','Science','https://nijostam.org/journals/index.php/njstm/article/view/90','https://nijostam.org/journals/index.php/njstm/article/view/90/61',6,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(17,5,'An appraisal of the entrepreneurial ecosystem for leather start-ups in Nigeria','Dashe N.E., Midat C.Y., Olatunji T.E., Bannah B.D., Osazee O.B.','31-38','Management','https://nijostam.org/journals/index.php/njstm/article/view/91','https://nijostam.org/journals/index.php/njstm/article/view/91/62',7,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(18,5,'Innovation and sustainability of the Nigerian leather small and medium-sized enterprises: A systematic review','Dashe N.E., Midat C.Y., Olatunji T.E., Bannah B.D., Igbehnadun O.J.','39-51','Management','https://nijostam.org/journals/index.php/njstm/article/view/92','https://nijostam.org/journals/index.php/njstm/article/view/92/63',8,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(19,5,'Phytochemical and proximate analysis of Monodora myristica (African nutmeg) dry seeds','S.I. Ama, O.S. Enyieko, O.E. Ogaji, F.A. Akpanke, J.O. Oko','52-59','Science','https://nijostam.org/journals/index.php/njstm/article/view/93','https://nijostam.org/journals/index.php/njstm/article/view/93/64',9,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(20,5,'The application of internet of thing (IOT) to enhance productivity and sustainability in Nigerian agriculture: A review','Agballa U.B., Odonodo C.U., Agida J., Bature R.S., Kabir U., Onunka O.','60-64','Technology','https://nijostam.org/journals/index.php/njstm/article/view/94','https://nijostam.org/journals/index.php/njstm/article/view/94/65',10,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(21,5,'Physico-mechanical properties of recycled low-density polyethylene / wood flour for floor ties applications','Musa E., Salami W.O., Shekaari T.N.B., Kadanga B., Patrick B.T., Patrick K.','65-70','Materials science','https://nijostam.org/journals/index.php/njstm/article/view/95','https://nijostam.org/journals/index.php/njstm/article/view/95/66',11,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(22,5,'Production of floor tiles from recycled low-density polyethylene (water sachets) / sawdust composites','E. Musa, T.N.B. Shekaari, B. Kadanga, B.T. Patrick, K. Patrick','71-73','Materials science','https://nijostam.org/journals/index.php/njstm/article/view/96','https://nijostam.org/journals/index.php/njstm/article/view/96/67',12,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(24,6,'An Investigation into the Use of Sodium Alginate as a Chelating Ligand For the Treatment of Tannery Gaseous Waste','B. T. Patrick, D. P. Feka, I. Akawu, K. Patrick','1-4','Environmental science','https://nijostam.org/journals/index.php/njstm/article/view/21','https://nijostam.org/journals/index.php/njstm/article/view/21/31',1,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(25,6,'Effect of Calcinated Palm Kernel Shell Powder as Reinforcement on the Physico-Mechanical Properties of Recycled Low-Density Polyethylene Composites for Interlocks','W.O. Salami, Y.O. Ganiyu, S.M. Ali, T.N.B. Shekari, H. Opara','5-10','Materials science','https://nijostam.org/journals/index.php/njstm/article/view/38','https://nijostam.org/journals/index.php/njstm/article/view/38/32',2,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(26,6,'Phytochemical investigation, proximate and trace elements analysis of the leaves of mango (Mangifera indica) grown at tanning area','J. Abba, Z. Haruna, E. P. Akabuogu, M. B. Omotola','11-15','Science','https://nijostam.org/journals/index.php/njstm/article/view/37','https://nijostam.org/journals/index.php/njstm/article/view/37/33',3,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(27,6,'Synthesis of azo acid dyes derived from phenylhydrazine as a diazo component and their assessments on chrome-tanned leather','O. B. Agho, C. C. Enyeribe, J. Obadahun, M. B. Omotola, G. O. Idoko, M.I. Ulakpa, M. Micah','16-20','Materials science','https://nijostam.org/journals/index.php/njstm/article/view/36','https://nijostam.org/journals/index.php/njstm/article/view/36/35',4,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(28,6,'Assessment of the bonding properties of epoxidized natural rubber (ENR-25) based adhesives filled with calcium carbonate for leather and footwear applications','A. H. Bayero, U. S. Ishiaku, M.I. Uzochukwu, H. Opara, Jonathan D. Gaiya, T.G. Osi','21-26','Engineering','https://nijostam.org/journals/index.php/njstm/article/view/35','https://nijostam.org/journals/index.php/njstm/article/view/35/36',5,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(29,6,'Investigation of physicochemical and trace metal levels in drinking water sources within the Nigerian Institute of Leather Science and Technology, Zaria, Nigeria','J Abba, P. Gyang, M. B. Omotola, E. P. Akabuogu','27-31','Environmental science','https://nijostam.org/journals/index.php/njstm/article/view/33','https://nijostam.org/journals/index.php/njstm/article/view/33/37',6,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(30,6,'Mechanical characterisation of recycled high-density polyethylene HDPE (rHDPE) reinforced with banana stem fibre using mix-design of experimental (MDOE) analysis','S. M. Ali, Y. O. Ganiyu, W. O. Salami, I. Abubakar','32-37','Materials science','https://nijostam.org/journals/index.php/njstm/article/view/16','https://nijostam.org/journals/index.php/njstm/article/view/16/38',7,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(31,6,'Synthesis and dyeing performance of heterocyclic dyes containing aminothiophene moiety on polyester fibre','O. B. Agho, J. Obadahun, C. C. Enyeribe, M. B. Omotola, A. I. Okele, G. O. Idoko, S. Hassan','38-42','Materials science','https://nijostam.org/journals/index.php/njstm/article/view/15','https://nijostam.org/journals/index.php/njstm/article/view/15/39',8,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(32,6,'Synthesis and dyeing performance of monoazo acid dyes derived from 2-amino-thiophene on chrome tanned leather and nylon 6,6 substrate','I. Y. Magaji, S. A. Abdulkadir, P. L. Pascalina, C. I. Yakubu, S. Hassan, K. A. Bichi, E. P. Akabuogu','43-47','Materials science','https://nijostam.org/journals/index.php/njstm/article/view/14','https://nijostam.org/journals/index.php/njstm/article/view/14/40',9,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(33,6,'Effect of filler loading on physico-mechanical properties of polypropylene filled with huracreptan pods composite','I. Y. Magaji, S. A. Abdulkadir, C. I. Yakubu, P. L. Pascalina, S. Hassan, K. A. Bichi, E. P. Akabuogu','48-52','Materials science','https://nijostam.org/journals/index.php/njstm/article/view/13','https://nijostam.org/journals/index.php/njstm/article/view/13/41',10,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(34,6,'Microbes as tools for eco-sustainable bioremediation: A review','O. O. Usman, S. O. Obayori, A. Muhktiar, A. E. Elijah','53-58','Environmental science','https://nijostam.org/journals/index.php/njstm/article/view/7','https://nijostam.org/journals/index.php/njstm/article/view/7/42',11,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(35,6,'Tannery wastewater treatment using Jatropha curcas as bio-coagulant and biosorbent','B. T. Patrick, M. A. Buba, I. B. Mohammed, I. Akawu, D. P. Feka, K. Patrick','59-64','Environmental science','https://nijostam.org/journals/index.php/njstm/article/view/5','https://nijostam.org/journals/index.php/njstm/article/view/5',12,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(36,6,'Assessment of the impact of fuel subsidy removal on civil servants??? efficiency in the Nigerian Institute of Leather and Science Technology, Zaria','S. Y. Mohammed, O. D. Ibeabuchi, A. M. Sulaiman, B. E. Egwu','65-70','Management','https://nijostam.org/journals/index.php/njstm/article/view/8','https://nijostam.org/journals/index.php/njstm/article/view/8',13,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(37,6,'Renaissance of leather: Transforming branding for modern leather products','Shaibu Jibril Mohammed','71-76','Management','https://nijostam.org/journals/index.php/njstm/article/view/39','https://nijostam.org/journals/index.php/njstm/article/view/39',14,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58'),(38,6,'Production of emulsion paint using expanded polystyrene as a binder and snail shell as filler','Musa E., Shekarri T.N.B., Kadanga B., Patrick B.T., Patrick K., Gaiya J.D., Danlami H.','77-81','Materials science','https://nijostam.org/journals/index.php/njstm/article/view/52','https://nijostam.org/journals/index.php/njstm/article/view/52',15,NULL,NULL,NULL,NULL,NULL,'2026-10-20 00:40:58');
/*!40000 ALTER TABLE `published_articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviewer_assignments`
--

DROP TABLE IF EXISTS `reviewer_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviewer_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `manuscript_id` bigint(20) unsigned NOT NULL,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `editor_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'invited',
  `invited_at` datetime NOT NULL DEFAULT current_timestamp(),
  `responded_at` datetime DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `comments_for_author` text DEFAULT NULL,
  `comments_for_editor` text DEFAULT NULL,
  `recommendation` varchar(32) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ra_manuscript` (`manuscript_id`),
  KEY `idx_ra_reviewer` (`reviewer_id`),
  KEY `idx_ra_status` (`status`),
  KEY `fk_ra_editor` (`editor_id`),
  CONSTRAINT `fk_ra_editor` FOREIGN KEY (`editor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ra_manuscript` FOREIGN KEY (`manuscript_id`) REFERENCES `manuscripts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ra_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviewer_assignments`
--

LOCK TABLES `reviewer_assignments` WRITE;
/*!40000 ALTER TABLE `reviewer_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviewer_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `manuscript_id` bigint(20) unsigned NOT NULL,
  `reviewer_assignment_id` bigint(20) unsigned DEFAULT NULL,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `round` smallint(5) unsigned NOT NULL DEFAULT 1,
  `recommendation` varchar(32) DEFAULT NULL,
  `comments_for_author` text DEFAULT NULL,
  `comments_for_editor` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reviews_manuscript` (`manuscript_id`),
  KEY `idx_reviews_reviewer` (`reviewer_id`),
  CONSTRAINT `fk_reviews_manuscript` FOREIGN KEY (`manuscript_id`) REFERENCES `manuscripts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revision_history`
--

DROP TABLE IF EXISTS `revision_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `revision_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `manuscript_id` bigint(20) unsigned NOT NULL,
  `version` smallint(5) unsigned NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `file_id` bigint(20) unsigned DEFAULT NULL,
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rh_manuscript` (`manuscript_id`),
  CONSTRAINT `fk_rh_manuscript` FOREIGN KEY (`manuscript_id`) REFERENCES `manuscripts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revision_history`
--

LOCK TABLES `revision_history` WRITE;
/*!40000 ALTER TABLE `revision_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `revision_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `label` varchar(160) DEFAULT NULL,
  `setting_group` varchar(60) NOT NULL DEFAULT 'general',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('allowed_file_types','application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword','Allowed file types','uploads','2026-10-20 00:40:58'),('apc_international','30','Article processing charge (USD)','fees','2026-10-20 00:40:58'),('apc_nigeria','25000','Article processing charge (NGN)','fees','2026-10-20 00:40:58'),('eissn','3027-2580','eISSN','journal','2026-10-20 00:40:58'),('journal_full_name','Nigerian Journal of Science, Technology, and Applied Materials','Full journal name','journal','2026-10-20 00:40:58'),('journal_name','NIJOSTAM','Journal name','journal','2026-10-20 00:40:58'),('manuscript_prefix','NIJOSTAM','Manuscript reference prefix','workflow','2026-10-20 00:40:58'),('max_file_size_mb','10','Maximum upload size (MB)','uploads','2026-10-20 00:40:58'),('publication_frequency','Quarterly','Publication frequency','journal','2026-10-20 00:40:58'),('publisher','Nigerian Institute of Leather and Science Technology','Publisher','journal','2026-10-20 00:40:58'),('review_period_days','21','Peer review period (days)','workflow','2026-10-20 00:40:58'),('social_facebook','https://www.facebook.com/nijostam','Facebook page URL','social','2026-10-20 22:52:26'),('social_instagram','https://www.instagram.com/nijostam','Instagram profile URL','social','2026-10-20 22:52:26'),('social_linkedin','https://www.linkedin.com/company/nijostam','LinkedIn page URL','social','2026-10-20 22:52:26'),('social_whatsapp','https://wa.me/2348000000000','WhatsApp contact URL','social','2026-10-20 22:52:26'),('social_x','https://x.com/nijostam','X (Twitter) profile URL','social','2026-10-20 22:52:26'),('social_youtube','https://www.youtube.com/@nijostam','YouTube channel URL','social','2026-10-20 22:52:26'),('submission_open','1','Accepting submissions','workflow','2026-10-20 00:40:58');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(254) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` enum('author','editor','reviewer','admin') NOT NULL DEFAULT 'author',
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-21 10:33:40
