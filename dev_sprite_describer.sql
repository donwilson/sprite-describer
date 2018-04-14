-- MySQL dump 10.13  Distrib 5.5.56, for Linux (x86_64)
--
-- Host: localhost    Database: dev_sprite_describer
-- ------------------------------------------------------
-- Server version	5.5.56

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

--
-- Table structure for table `spritesheet`
--

DROP TABLE IF EXISTS `spritesheet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spritesheet` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Local path to spritesheet image',
  `sprite_width` int(10) unsigned NOT NULL COMMENT 'Width in pixels of each sprite',
  `sprite_height` int(10) unsigned NOT NULL COMMENT 'Height in pixels of each sprite',
  `offset_x` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Offset in pixels from left of first sprite column',
  `offset_y` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Offset in pixels from top of first sprite row',
  `padding_x` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Space in pixels on x-axis between two sprites',
  `padding_y` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Space in pixels on y-axis between two sprites',
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename` (`filename`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `spritesheet`
--

LOCK TABLES `spritesheet` WRITE;
/*!40000 ALTER TABLE `spritesheet` DISABLE KEYS */;
INSERT INTO `spritesheet` VALUES (1,'roguelikeSheet_transparent.png',15,15,0,0,2,2);
/*!40000 ALTER TABLE `spritesheet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `spritesheet_sprite`
--

DROP TABLE IF EXISTS `spritesheet_sprite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spritesheet_sprite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `spritesheet` int(10) unsigned NOT NULL COMMENT 'spritesheet.id',
  `key` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Unique string to easily identify sprite',
  `ignore` enum('0','1') COLLATE utf8_unicode_ci NOT NULL COMMENT 'Whether to ignore this sprite on export. 0=dont ignore, 1=ignore',
  `offset_x` int(10) unsigned NOT NULL COMMENT 'Column from left, starting at 0',
  `offset_y` int(10) unsigned NOT NULL COMMENT 'Column from top, starting at 0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `spritesheet_offset_x_offset_y` (`spritesheet`,`offset_x`,`offset_y`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `spritesheet_sprite`
--

LOCK TABLES `spritesheet_sprite` WRITE;
/*!40000 ALTER TABLE `spritesheet_sprite` DISABLE KEYS */;
INSERT INTO `spritesheet_sprite` VALUES (1,1,'anvil','0',15,0),(7,1,'lake_tl','0',2,0),(8,1,'lake_t','0',3,0),(9,1,'lake_tr','0',4,0),(10,1,'lake','0',3,1),(11,1,'lake_r','0',4,1),(12,1,'lake_br','0',4,2),(13,1,'lake_b','0',3,2),(14,1,'lake_bl','0',2,2),(15,1,'lake_l','0',2,1);
/*!40000 ALTER TABLE `spritesheet_sprite` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-07-06 14:15:14
