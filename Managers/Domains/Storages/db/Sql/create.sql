CREATE TABLE IF NOT EXISTS `awm_domains` (
	`id_domain` INT(11) NOT NULL AUTO_INCREMENT,
	`id_tenant` INT(11) NOT NULL DEFAULT '0',
	`id_mail_server` INT(11) NOT NULL DEFAULT '0',
	`name` VARCHAR(255) NULL DEFAULT NULL,
	PRIMARY KEY (`id_domain`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
