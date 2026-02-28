-- FP7: Align board_members table with required schema.
-- Run once in phpMyAdmin against learnandhelp_db.
-- Safe to run: CHANGE renames credentials→notes (preserves existing data),
-- ADD COLUMN adds missing fields without touching existing rows.

ALTER TABLE `board_members`
  CHANGE `credentials` `notes` varchar(255) DEFAULT NULL,
  ADD COLUMN `email`     varchar(100) DEFAULT NULL AFTER `role`,
  ADD COLUMN `mobile`    varchar(30)  DEFAULT NULL AFTER `email`,
  ADD COLUMN `date_from` date         DEFAULT NULL AFTER `sort_order`,
  ADD COLUMN `date_to`   date         DEFAULT NULL AFTER `date_from`;
