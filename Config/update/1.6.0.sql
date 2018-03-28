SET FOREIGN_KEY_CHECKS = 0;
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
