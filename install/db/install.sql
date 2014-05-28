CREATE TABLE IF NOT EXISTS `ws_migrations_log` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `date` DATETIME NOT NULL,
  `updateData` MEDIUMTEXT NOT NULL ,
  `originalData` MEDIUMTEXT DEFAULT NULL ,
  `type` VARCHAR (128),
  `description` TEXT NOT NULL ,
  INDEX (`type`)
);