-- Equipment Condition Check System
-- Tracks equipment condition when supervisors change

CREATE TABLE IF NOT EXISTS equipment_condition_checks (
    check_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    from_supervisor_id INT UNSIGNED NULL,
    to_supervisor_id INT UNSIGNED NOT NULL,
    checked_by_user_id INT UNSIGNED NULL,
    check_date DATETIME NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(store_id) ON DELETE CASCADE,
    FOREIGN KEY (from_supervisor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (to_supervisor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (checked_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_store (store_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Individual equipment condition records for each check
CREATE TABLE IF NOT EXISTS equipment_condition_records (
    record_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    check_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    condition_status ENUM('good', 'minor_damage', 'major_damage', 'missing') NOT NULL,
    notes TEXT,
    photo_url VARCHAR(500),
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (check_id) REFERENCES equipment_condition_checks(check_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    INDEX idx_check (check_id),
    INDEX idx_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stolen Equipment Incident Reports
CREATE TABLE IF NOT EXISTS stolen_equipment_reports (
    report_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    store_id INT NOT NULL,
    supervisor_id INT UNSIGNED NULL,
    reported_by_user_id INT UNSIGNED NOT NULL,
    incident_date DATE NOT NULL,
    incident_time TIME,
    incident_details TEXT NOT NULL,
    police_report_number VARCHAR(100),
    status ENUM('reported', 'investigating', 'closed') DEFAULT 'reported',
    resolution_notes TEXT,
    resolved_by_user_id INT UNSIGNED NULL,
    resolved_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES stores(store_id) ON DELETE CASCADE,
    FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reported_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_item (item_id),
    INDEX idx_store (store_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
