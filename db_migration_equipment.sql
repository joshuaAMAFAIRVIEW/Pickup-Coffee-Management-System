-- Migration: equipment & categories for Pickup Coffee inventory
-- Run this in phpMyAdmin or via MySQL CLI to add the necessary tables.

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `category_modifiers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(191) NOT NULL,
  `key_name` VARCHAR(191) NOT NULL,
  `type` VARCHAR(50) DEFAULT 'text', -- could be text, number, ip, etc.
  `required` TINYINT(1) DEFAULT 0,
  `position` INT DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  CONSTRAINT `fk_catmod_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `display_name` VARCHAR(255) DEFAULT NULL,
  `attributes` JSON DEFAULT NULL,
  `assigned_user_id` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_items_cat` (`category_id`),
  CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `item_assignments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `assigned_by` INT UNSIGNED DEFAULT NULL,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `unassigned_at` TIMESTAMP NULL DEFAULT NULL,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  KEY `idx_item` (`item_id`),
  CONSTRAINT `fk_assign_item` FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- End of migration

-- Add profile columns to `users` for onboarding details (if not present)
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `first_name` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `middle_name` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `last_name` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `position` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `department` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `store` VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `email` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `region` VARCHAR(100) DEFAULT NULL;
