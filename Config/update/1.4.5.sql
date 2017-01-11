SET FOREIGN_KEY_CHECKS = 0;
-- ---------------------------------------------------------------------
-- dpdpickup_price
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `dpdpickup_price`;

CREATE TABLE `dpdpickup_price`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `area_id` INTEGER NOT NULL,
    `weight_max` FLOAT NOT NULL,
    `price` DECIMAL(16,6) DEFAULT 0.000000,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `FI_dpdpickup_price_area_id` (`area_id`),
    CONSTRAINT `fk_dpdpickup_price_area_id`
        FOREIGN KEY (`area_id`)
        REFERENCES `area` (`id`)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
