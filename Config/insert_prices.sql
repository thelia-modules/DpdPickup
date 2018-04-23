# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Data for dpdpickup_price
-- ---------------------------------------------------------------------

-- Then add new entries
SELECT @max := MAX(`id`) FROM `dpdpickup_price`;
SET @max := @max+1;
-- insert dpdpickup_price
INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '0.25',
   '5.15',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '0.5',
   '5.59',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '1',
   '5.89',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '2',
   '6.19',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '3',
   '6.51',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '5',
   '7.15',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '6',
   '7.52',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '7',
   '7.89',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '8',
   '8.26',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '9',
   '8.63',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '10',
   '9',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '11',
   '9.4',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '12',
   '9.75',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '13',
   '10.2',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '14',
   '10.62',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '15',
   '11.05',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '16',
   '11.55',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '17',
   '12.05',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '18',
   '12.55',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '19',
   '13.05',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '20',
   '13.55',
   NOW(),
   NOW()
  );

INSERT INTO `dpdpickup_price` (`id`, `area_id`, `weight`,`price`, `created_at`, `updated_at`) VALUES
  (@max,
   '1',
   '100',
   '67.75',
   NOW(),
   NOW()
  );

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;