-- Fix Multi-Store System - Update Existing Tables
-- This modifies existing tables instead of recreating them

-- ============================================
-- 1. UPDATE USERS TABLE ROLE ENUM
-- ============================================
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'area_manager', 'store_supervisor', 'borrower', 'manager', 'staff') DEFAULT 'borrower';

-- ============================================
-- 2. CREATE MISSING TABLES
-- ============================================

-- Area Manager History
CREATE TABLE IF NOT EXISTS area_manager_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    area_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    unassigned_date DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (area_id) REFERENCES areas(area_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_area (area_id),
    INDEX idx_dates (assigned_date, unassigned_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Release Packages
CREATE TABLE IF NOT EXISTS release_packages (
    package_id INT AUTO_INCREMENT PRIMARY KEY,
    package_code VARCHAR(50) UNIQUE NOT NULL,
    store_id INT NOT NULL,
    package_status ENUM('preparing', 'ready', 'in_transit', 'delivered', 'cancelled') DEFAULT 'preparing',
    prepared_by_user_id INT UNSIGNED NOT NULL,
    prepared_date DATE NULL,
    shipped_date DATE NULL,
    delivered_date DATE NULL,
    received_by_user_id INT UNSIGNED NULL,
    total_items INT DEFAULT 0,
    notes TEXT NULL,
    delivery_receipt_number VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(store_id) ON DELETE CASCADE,
    FOREIGN KEY (prepared_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_store (store_id),
    INDEX idx_status (package_status),
    INDEX idx_dates (prepared_date, shipped_date, delivered_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Release Package Items
CREATE TABLE IF NOT EXISTS release_package_items (
    package_item_id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES release_packages(package_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    INDEX idx_package (package_id),
    INDEX idx_item (item_id),
    UNIQUE KEY unique_package_item (package_id, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Store Item Assignments
CREATE TABLE IF NOT EXISTS store_item_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    store_id INT NOT NULL,
    package_id INT NULL,
    assigned_date DATE NOT NULL,
    assigned_by_user_id INT UNSIGNED NOT NULL,
    received_date DATE NULL,
    received_by_user_id INT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES stores(store_id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES release_packages(package_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_item (item_id),
    INDEX idx_store (store_id),
    INDEX idx_package (package_id),
    INDEX idx_dates (assigned_date, received_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
