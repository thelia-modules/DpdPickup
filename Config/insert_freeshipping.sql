# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- icirelais_freeshipping
-- ---------------------------------------------------------------------
-- Add new entries
INSERT INTO `icirelais_freeshipping` (`id`,`active`, `created_at`, `updated_at`)
VALUES( '1', FALSE, NOW(), NOW());

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
