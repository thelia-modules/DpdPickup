SET FOREIGN_KEY_CHECKS = 0;
-- ---------------------------------------------------------------------
-- address_icirelais
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `address_icirelais`;

CREATE TABLE `address_icirelais`
(
    `id` INTEGER NOT NULL,
    `title_id` INTEGER NOT NULL,
    `company` VARCHAR(255),
    `firstname` VARCHAR(255) NOT NULL,
    `lastname` VARCHAR(255) NOT NULL,
    `address1` VARCHAR(255) NOT NULL,
    `address2` VARCHAR(255) NOT NULL,
    `address3` VARCHAR(255) NOT NULL,
    `zipcode` VARCHAR(10) NOT NULL,
    `city` VARCHAR(255) NOT NULL,
    `country_id` INTEGER NOT NULL,
    `code` VARCHAR(10) NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `FI_address_icirelais_customer_title_id` (`title_id`),
    INDEX `FI_address_country_id` (`country_id`),
    CONSTRAINT `fk_address_icirelais_customer_title_id`
        FOREIGN KEY (`title_id`)
        REFERENCES `customer_title` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT,
    CONSTRAINT `fk_address_country_id`
        FOREIGN KEY (`country_id`)
        REFERENCES `country` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- dpdpickup_price
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `dpdpickup_price`;

CREATE TABLE `dpdpickup_price`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `area_id` INTEGER NOT NULL,
    `weight` FLOAT NOT NULL,
    `price` DECIMAL(16,6) DEFAULT 0.000000,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
