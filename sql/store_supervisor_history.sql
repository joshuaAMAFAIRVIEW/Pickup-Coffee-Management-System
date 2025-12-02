-- Store Supervisor Assignment History Table

CREATE TABLE IF NOT EXISTS store_supervisor_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    from_store_id INT NULL,
    to_store_id INT NOT NULL,
    changed_date DATE NOT NULL,
    changed_by_user_id INT UNSIGNED NOT NULL,
    reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (from_store_id) REFERENCES stores(store_id) ON DELETE SET NULL,
    FOREIGN KEY (to_store_id) REFERENCES stores(store_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_from_store (from_store_id),
    INDEX idx_to_store (to_store_id),
    INDEX idx_changed_date (changed_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
