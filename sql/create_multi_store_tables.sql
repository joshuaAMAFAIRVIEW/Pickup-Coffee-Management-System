-- Multi-Store Organizational Hierarchy Tables
-- Created: December 2, 2025

-- ============================================
-- AREAS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS areas (
    area_id INT AUTO_INCREMENT PRIMARY KEY,
    area_name VARCHAR(100) NOT NULL,
    parent_area_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    split_from_area_id INT NULL COMMENT 'Original area before split',
    split_date DATE NULL COMMENT 'Date when area was split',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_parent_area (parent_area_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add self-referencing foreign keys after table creation
ALTER TABLE areas ADD CONSTRAINT fk_areas_parent FOREIGN KEY (parent_area_id) REFERENCES areas(area_id) ON DELETE SET NULL;
ALTER TABLE areas ADD CONSTRAINT fk_areas_split FOREIGN KEY (split_from_area_id) REFERENCES areas(area_id) ON DELETE SET NULL;

-- ============================================
-- STORES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS stores (
    store_id INT AUTO_INCREMENT PRIMARY KEY,
    store_name VARCHAR(100) NOT NULL,
    store_code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Unique identifier like ST-001',
    area_id INT NULL,
    address TEXT NULL,
    contact_person VARCHAR(100) NULL,
    contact_number VARCHAR(20) NULL,
    opening_date DATE NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id) REFERENCES areas(area_id) ON DELETE SET NULL,
    INDEX idx_area (area_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AREA MANAGER HISTORY TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS area_manager_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    area_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    unassigned_date DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_area (area_id),
    INDEX idx_dates (assigned_date, unassigned_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- RELEASE PACKAGES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS release_packages (
    package_id INT AUTO_INCREMENT PRIMARY KEY,
    package_code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Package tracking number like PKG-2025-001',
    store_id INT NOT NULL,
    package_status ENUM('preparing', 'ready', 'in_transit', 'delivered', 'cancelled') DEFAULT 'preparing',
    prepared_by_user_id INT NOT NULL,
    prepared_date DATE NULL,
    shipped_date DATE NULL,
    delivered_date DATE NULL,
    received_by_user_id INT NULL,
    total_items INT DEFAULT 0,
    notes TEXT NULL,
    delivery_receipt_number VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(store_id) ON DELETE CASCADE,
    INDEX idx_store (store_id),
    INDEX idx_status (package_status),
    INDEX idx_dates (prepared_date, shipped_date, delivered_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- STORE ITEM ASSIGNMENTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS store_item_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    store_id INT NOT NULL,
    package_id INT NULL COMMENT 'If assigned via bulk package',
    assigned_date DATE NOT NULL,
    assigned_by_user_id INT NOT NULL,
    received_date DATE NULL,
    received_by_user_id INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES stores(store_id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES release_packages(package_id) ON DELETE SET NULL,
    INDEX idx_item (item_id),
    INDEX idx_store (store_id),
    INDEX idx_package (package_id),
    INDEX idx_dates (assigned_date, received_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- RELEASE PACKAGE ITEMS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS release_package_items (
    package_item_id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    item_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES release_packages(package_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    INDEX idx_package (package_id),
    INDEX idx_item (item_id),
    UNIQUE KEY unique_package_item (package_id, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ALTER USERS TABLE
-- ============================================
-- Add new columns for organizational hierarchy (step by step for MariaDB compatibility)

-- Check if columns exist before adding
SET @exist_role := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'inventory_system' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role');
SET @sql_role := IF(@exist_role = 0, 'ALTER TABLE users ADD COLUMN role ENUM(\'admin\', \'area_manager\', \'store_supervisor\', \'borrower\') DEFAULT \'borrower\' AFTER password', 'SELECT "Column role already exists"');
PREPARE stmt FROM @sql_role;
EXECUTE stmt;

SET @exist_area := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'inventory_system' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'area_id');
SET @sql_area := IF(@exist_area = 0, 'ALTER TABLE users ADD COLUMN area_id INT NULL AFTER role COMMENT \'For area managers\'', 'SELECT "Column area_id already exists"');
PREPARE stmt FROM @sql_area;
EXECUTE stmt;

SET @exist_store := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'inventory_system' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'store_id');
SET @sql_store := IF(@exist_store = 0, 'ALTER TABLE users ADD COLUMN store_id INT NULL AFTER area_id COMMENT \'For store supervisors\'', 'SELECT "Column store_id already exists"');
PREPARE stmt FROM @sql_store;
EXECUTE stmt;

SET @exist_manager := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'inventory_system' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'managed_by_user_id');
SET @sql_manager := IF(@exist_manager = 0, 'ALTER TABLE users ADD COLUMN managed_by_user_id INT NULL AFTER store_id COMMENT \'Reports to this user\'', 'SELECT "Column managed_by_user_id already exists"');
PREPARE stmt FROM @sql_manager;
EXECUTE stmt;

-- Add foreign keys and indexes after columns are created
-- Add foreign keys (with existence checks)
SET @exist_fk_area := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = 'inventory_system' AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'fk_users_area');
SET @sql_fk_area := IF(@exist_fk_area = 0 AND @exist_area = 0, 'ALTER TABLE users ADD CONSTRAINT fk_users_area FOREIGN KEY (area_id) REFERENCES areas(area_id) ON DELETE SET NULL', 'SELECT "FK fk_users_area exists or not needed"');
PREPARE stmt FROM @sql_fk_area;
EXECUTE stmt;

SET @exist_fk_store := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = 'inventory_system' AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'fk_users_store');
SET @sql_fk_store := IF(@exist_fk_store = 0 AND @exist_store = 0, 'ALTER TABLE users ADD CONSTRAINT fk_users_store FOREIGN KEY (store_id) REFERENCES stores(store_id) ON DELETE SET NULL', 'SELECT "FK fk_users_store exists or not needed"');
PREPARE stmt FROM @sql_fk_store;
EXECUTE stmt;

SET @exist_fk_manager := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = 'inventory_system' AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'fk_users_manager');
SET @sql_fk_manager := IF(@exist_fk_manager = 0 AND @exist_manager = 0, 'ALTER TABLE users ADD CONSTRAINT fk_users_manager FOREIGN KEY (managed_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL', 'SELECT "FK fk_users_manager exists or not needed"');
PREPARE stmt FROM @sql_fk_manager;
EXECUTE stmt;

-- Add indexes
SET @exist_idx_role := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'inventory_system' AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_role');
SET @sql_idx_role := IF(@exist_idx_role = 0 AND @exist_role = 0, 'ALTER TABLE users ADD INDEX idx_role (role)', 'SELECT "Index idx_role exists or not needed"');
PREPARE stmt FROM @sql_idx_role;
EXECUTE stmt;

SET @exist_idx_area := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'inventory_system' AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_area');
SET @sql_idx_area := IF(@exist_idx_area = 0 AND @exist_area = 0, 'ALTER TABLE users ADD INDEX idx_area (area_id)', 'SELECT "Index idx_area exists or not needed"');
PREPARE stmt FROM @sql_idx_area;
EXECUTE stmt;

SET @exist_idx_store := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'inventory_system' AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_store');
SET @sql_idx_store := IF(@exist_idx_store = 0 AND @exist_store = 0, 'ALTER TABLE users ADD INDEX idx_store (store_id)', 'SELECT "Index idx_store exists or not needed"');
PREPARE stmt FROM @sql_idx_store;
EXECUTE stmt;

-- Add foreign keys to area_manager_history after users columns exist
ALTER TABLE area_manager_history ADD CONSTRAINT fk_amh_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE;
ALTER TABLE area_manager_history ADD CONSTRAINT fk_amh_area FOREIGN KEY (area_id) REFERENCES areas(area_id) ON DELETE CASCADE;

-- Add foreign keys to release_packages and store_item_assignments
ALTER TABLE release_packages ADD CONSTRAINT fk_pkg_prepared_by FOREIGN KEY (prepared_by_user_id) REFERENCES users(user_id) ON DELETE CASCADE;
ALTER TABLE release_packages ADD CONSTRAINT fk_pkg_received_by FOREIGN KEY (received_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL;

ALTER TABLE store_item_assignments ADD CONSTRAINT fk_sia_assigned_by FOREIGN KEY (assigned_by_user_id) REFERENCES users(user_id) ON DELETE CASCADE;
ALTER TABLE store_item_assignments ADD CONSTRAINT fk_sia_received_by FOREIGN KEY (received_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL;

-- ============================================
-- ADD FOREIGN KEY TO STORE ITEM ASSIGNMENTS
-- ============================================
-- This will be added after release_packages table is created (handled by table definition above)

