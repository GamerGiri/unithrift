-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: unithrift_db
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
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `category` enum('Textbooks','Lab Gear & Kits','Drawing & Tools','Electronics & Calculators','Other') NOT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `item_condition` enum('Like New','Gently Used','Fair') NOT NULL,
  `original_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `description` text NOT NULL,
  `meetup_location` varchar(120) NOT NULL,
  `image_icon` varchar(10) DEFAULT '?',
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('Available','Reserved','Sold') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
INSERT INTO `items` VALUES (1,3,'Introduction to Algorithms (CLRS 3rd Edition)','Textbooks','CSE 311','Gently Used',1200.00,450.00,'Standard algorithms textbook required for CSE 311. Includes clean pages, all key chapter markers intact, and zero pencil marks on exercise sections.','SEU Main Cafeteria or Library Ground Floor','📚','assets/uploads/clrs_algorithms.jpg','Available','2026-09-29 21:16:36','2026-09-29 22:11:22'),(2,4,'Arduino Uno R3 + Sensor Starter Kit (16 Sensors + Cables)','Lab Gear & Kits','CSE 316','Like New',2800.00,1250.00,'Complete kit used for Microprocessor & Interfacing lab. Contains Arduino Uno microcontroller board, ultrasonic sensor, IR sensor, servo motor, and 65x jumper wires.','CSE Hardware Lab 4, 5th Floor','🔬','assets/uploads/arduino_kit.jpg','Available','2026-09-29 21:16:36','2026-09-29 22:11:22'),(3,5,'Casio fx-991EX ClassWiz Scientific Calculator (Original)','Electronics & Calculators','MAT 101','Like New',2400.00,1100.00,'Original natural textbook display scientific calculator with 552 functions, matrix calculation, vector and quadratic solvers. Dual solar and battery power.','Campus Reception Lobby','🔢','assets/uploads/casio_calculator.jpg','Available','2026-09-29 21:16:36','2026-09-29 22:11:22'),(4,5,'Rotring Engineering Drawing Board (A2 Size) + T-Square & Set Squares','Drawing & Tools','ENG 103','Gently Used',3200.00,1300.00,'Complete Engineering Graphics drafting board set with parallel motion ruler, 45/90 degree set squares, and clip locks. Essential for 1st-year engineering graphics.','Architecture Dept Studio or Main Gate','📐','assets/uploads/drawing_tools.jpg','Available','2026-09-29 21:16:36','2026-09-29 22:11:22'),(5,4,'Digital Multimeter DT-830D + Test Leads & 9V Battery','Lab Gear & Kits','EEE 102','Like New',750.00,300.00,'Compact digital multimeter for measuring AC/DC voltage, DC current, and resistance. Used in Basic Electrical Engineering Lab. Tested and working 100%.','EEE Lab 2, 4th Floor','⚡','assets/uploads/multimeter.jpg','Available','2026-09-29 21:16:36','2026-09-29 22:11:22'),(6,3,'Database System Concepts (Silberschatz, Korth 7th Edition)','Textbooks','CSE 341','Gently Used',950.00,380.00,'Must-have book for Database Management Systems. Covers relational algebra, SQL optimization, transaction management, and indexing in depth.','Study Zone, 3rd Floor','📖','assets/uploads/db_textbook.jpg','Available','2026-09-29 21:16:36','2026-09-29 22:11:22'),(7,4,'Solderless Breadboard (830 Tie Points) + 4x IC 7400/7408/7432 Chips','Lab Gear & Kits','CSE 225','Fair',600.00,200.00,'Digital Logic Design lab essentials. Includes standard full-size breadboard with power rails and 4 basic logic gate ICs. Ideal for DLD experiments.','Main Gate or Canteen','💡','assets/uploads/breadboard_ic.jpg','Available','2026-09-29 21:16:36','2026-09-29 22:11:22'),(8,5,'USB 3.0 to Gigabit Ethernet Adapter (Aluminium Body)','Electronics & Calculators','CSE 411','Like New',1400.00,650.00,'High-speed network adapter used in Computer Networks lab to connect thin laptops lacking RJ45 ports to campus LAN switch configurations.','CSE Computer Lab 6','💻','assets/uploads/ethernet_adapter.jpg','Sold','2026-09-29 21:16:36','2026-09-29 22:11:22'),(9,2,'esp32 s3','Electronics & Calculators',NULL,'Gently Used',1200.00,700.00,'everything','cafe','⚡','assets/uploads/esp32_board.jpg','Available','2026-09-29 21:25:50','2026-09-29 22:01:57');
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(30) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(25) NOT NULL,
  `department` varchar(50) DEFAULT 'CSE',
  `role` varchar(20) DEFAULT 'student',
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'2021000000001','Admin Student','admin@seu.edu.bd','01711223344','CSE','admin','$2y$10$Z89xRm4snDwZv1YCCdWk0.jIgGRhpx1ebrdtvkcmJHcohRiqNXKrm','2026-09-29 21:16:36'),(2,'2023200000732','Md. Remon Islam','2023200000732@seu.edu.bd','01996346893','CSE','student','$2y$10$7QQ9YxiY/Boj0yomMcGxouqIcQQpyezLB74x2xbWedM0P2pr.AEyi','2026-09-29 21:24:54'),(3,'2021100000145','Tanvir Ahmed','tanvir.cse@seu.edu.bd','01811223344','CSE','student','$2y$10$e5N5h4Es/WR/Puy0scjbf.AisRIC28rjHIn33T.hMPaQGrY/29XuC','2026-09-29 22:11:22'),(4,'2021200000098','Nusrat Jahan','nusrat.eee@seu.edu.bd','01911445566','EEE','student','$2y$10$e5N5h4Es/WR/Puy0scjbf.AisRIC28rjHIn33T.hMPaQGrY/29XuC','2026-09-29 22:11:22'),(5,'2021100000312','Sabbir Hossain','sabbir.arc@seu.edu.bd','01722556677','Architecture','student','$2y$10$e5N5h4Es/WR/Puy0scjbf.AisRIC28rjHIn33T.hMPaQGrY/29XuC','2026-09-29 22:11:22');
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

-- Dump completed on 2026-09-30  4:12:04
