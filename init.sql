-- Database initialization script for CAR Content Locator

CREATE TABLE IF NOT EXISTS `usc_data` (
  `usc_data_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_path` varchar(1000) NOT NULL,
  `sha1` varchar(100) NOT NULL,
  `title_id` varchar(50) NOT NULL,
  `file_id` int(11) NOT NULL,
  PRIMARY KEY (`usc_data_id`),
  KEY `index_file_path` (`file_path`(768)),
  KEY `usc_data_title_id` (`title_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1224999 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `usc_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `relative_path` text DEFAULT NULL,
  `sha1` text DEFAULT NULL,
  `size` bigint(11) DEFAULT NULL,
  `key_fp` text DEFAULT NULL,
  `encrypted_key` text DEFAULT NULL,
  `title_id` varchar(50) NOT NULL DEFAULT '',
  `usc_file_id` int(11) NOT NULL,
  `tar_source` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `index_relative_path` (`relative_path`(768)),
  KEY `files_title_id` (`title_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1284775 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `file_range_car` (
  `file_range_id` int(11) DEFAULT NULL,
  `car_id` int(11) DEFAULT NULL,
  KEY `idx_file_range_car_file_range_id` (`file_range_id`),
  KEY `idx_file_range_car_car_id` (`car_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;