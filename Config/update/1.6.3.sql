SET FOREIGN_KEY_CHECKS = 0;
-- ---------------------------------------------------------------------
-- dpdpickup_labels
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `dpdpickup_labels`;

CREATE TABLE `dpdpickup_labels`
(
  `id` INTEGER NOT NULL AUTO_INCREMENT,
  `order_id` INTEGER NOT NULL,
  `label_number` VARCHAR(255),
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  INDEX `FI_dpdpickup_labels_order_id` (`order_id`),
  CONSTRAINT `fk_dpdpickup_labels_order_id`
  FOREIGN KEY (`order_id`)
  REFERENCES `order` (`id`)
    ON UPDATE RESTRICT
    ON DELETE RESTRICT
) ENGINE=InnoDB;