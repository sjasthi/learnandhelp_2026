-- ============================================================
-- Migration Script for learnandhelp_db
--
-- What this does:
--   1. Drops the obsolete `schools_suggested` table
--   2. Drops the old `schools` table
--
-- INSTRUCTIONS:
--   Step 1: Run THIS file in phpMyAdmin (SQL tab)
--   Step 2: Then import sql/schools.sql in phpMyAdmin (Import tab)
--           This re-creates `schools` with the new schema + seed data.
-- ============================================================

DROP TABLE IF EXISTS `schools_suggested`;
DROP TABLE IF EXISTS `schools`;
