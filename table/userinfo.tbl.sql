CREATE TABLE IF NOT EXISTS `userinfo` (
  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Internal unique sequential identifier',
  `user_id` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Login User ID',
  `name` VARCHAR(50) NOT NULL COMMENT 'User Name',
  `password` VARCHAR(255) NOT NULL COMMENT 'Encrypted Password Hash',
  `phone` VARCHAR(20) DEFAULT NULL COMMENT 'Phone Number',
  `email` VARCHAR(100) DEFAULT NULL COMMENT 'Email Address',
  `address` VARCHAR(255) DEFAULT NULL COMMENT 'Postal Address',
  `last_login_at` DATETIME DEFAULT NULL COMMENT 'Last Login Date/Time',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Registration Date/Time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
