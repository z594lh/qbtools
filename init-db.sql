CREATE DATABASE IF NOT EXISTS mysql;

USE mysql;

UPDATE mysql.user SET Host='%' WHERE User='root';

CREATE TABLE `free_size` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date_time` datetime NOT NULL,
  `free_space` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `torrent_info` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `size` bigint NOT NULL,
  `hash` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `upload_speed` int DEFAULT NULL,
  `download_speed` int DEFAULT NULL,
  `eta` int DEFAULT NULL,
  `share_ratio` decimal(5,2) DEFAULT NULL,
  `progress` decimal(5,2) DEFAULT NULL,
  `num_incomplete` int DEFAULT NULL,
  `num_leechs` int DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `added_on` datetime DEFAULT NULL,
  `completion_on` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


CREATE TABLE `torrent_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `operateKey` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `operateText` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;