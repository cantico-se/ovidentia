
CREATE TABLE `favorites_list` (
`id` INT(11) UNSIGNED NOT NULL auto_increment, 
`id_owner` INT(11) UNSIGNED NOT NULL, 
`url` TINYTEXT NOT NULL, 
`description` TEXT NOT NULL,
PRIMARY KEY (`id`)
); 

