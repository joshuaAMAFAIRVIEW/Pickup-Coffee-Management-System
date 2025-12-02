-- Multi-Store System Database Setup
-- Simple version without conditional logic

-- ============================================
-- 1. AREAS TABLE
-- ============================================
DROP TABLE IF EXISTS areas;
CREATE TABLE areas (
    area_id INT AUTO_INCREMENT PRIMARY KEY,
    area_name VARCHAR(100) NOT NULL,
    parent_area_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    split_from_area_id INT NULL,
    split_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_parent_area (parent_area_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add self-referencing foreign keys
ALTER TABLE areas ADD CONSTRAINT fk_areas_parent FOREIGN KEY (parent_area_id) REFERENCES areas(area_id) ON DELETE SET NULL;
ALTER TABLE areas ADD CONSTRAINT fk_areas_split FOREIGN KEY (split_from_area_id) REFERENCES areas(area_id) ON DELETE SET NULL;

-- ============================================
-- 2. STORES TABLE
-- ============================================
DROP TABLE IF EXISTS stores;
CREATE TABLE stores (
    store_id INT AUTO_INCREMENT PRIMARY KEY,
    store_name VARCHAR(100) NOT NULL,
    store_code VARCHAR(50) UNIQUE NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 3. UPDATE USERS TABLE
-- ============================================
ALTER TABLE users ADD COLUMN role ENUM('admin', 'area_manager', 'store_supervisor', 'borrower') DEFAULT 'borrower' AFTER password;
ALTER TABLE users ADD COLUMN area_id INT NULL AFTER role;
ALTER TABLE users ADD COLUMN store_id INT NULL AFTER area_id;
ALTER TABLE users ADD COLUMN managed_by_user_id INT NULL AFTER store_id;

ALTER TABLE users ADD CONSTRAINT fk_users_area FOREIGN KEY (area_id) REFERENCES areas(area_id) ON DELETE SET NULL;
ALTER TABLE users ADD CONSTRAINT fk_users_store FOREIGN KEY (store_id) REFERENCES stores(store_id) ON DELETE SET NULL;
ALTER TABLE users ADD CONSTRAINT fk_users_manager FOREIGN KEY (managed_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL;

ALTER TABLE users ADD INDEX idx_role (role);
ALTER TABLE users ADD INDEX idx_area (area_id);
ALTER TABLE users ADD INDEX idx_store (store_id);

-- ============================================
-- 4. AREA MANAGER HISTORY TABLE
-- ============================================
DROP TABLE IF EXISTS area_manager_history;
CREATE TABLE area_manager_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    area_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    unassigned_date DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (area_id) REFERENCES areas(area_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_area (area_id),
    INDEX idx_dates (assigned_date, unassigned_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 5. RELEASE PACKAGES TABLE
-- ============================================
DROP TABLE IF EXISTS release_packages;
CREATE TABLE release_packages (
    package_id INT AUTO_INCREMENT PRIMARY KEY,
    package_code VARCHAR(50) UNIQUE NOT NULL,
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
    FOREIGN KEY (prepared_by_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (received_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_store (store_id),
    INDEX idx_status (package_status),
    INDEX idx_dates (prepared_date, shipped_date, delivered_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 6. RELEASE PACKAGE ITEMS TABLE
-- ============================================
DROP TABLE IF EXISTS release_package_items;
CREATE TABLE release_package_items (
    package_item_id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    item_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES release_packages(package_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    INDEX idx_package (package_id),
    INDEX idx_item (item_id),
    UNIQUE KEY unique_package_item (package_id, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 7. STORE ITEM ASSIGNMENTS TABLE
-- ============================================
DROP TABLE IF EXISTS store_item_assignments;
CREATE TABLE store_item_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    store_id INT NOT NULL,
    package_id INT NULL,
    assigned_date DATE NOT NULL,
    assigned_by_user_id INT NOT NULL,
    received_date DATE NULL,
    received_by_user_id INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES stores(store_id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES release_packages(package_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (received_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_item (item_id),
    INDEX idx_store (store_id),
    INDEX idx_package (package_id),
    INDEX idx_dates (assigned_date, received_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
