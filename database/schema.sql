-- Poultry Farm & Hatchery Management System
-- Relational Database Schema (MySQL / MariaDB)

CREATE DATABASE IF NOT EXISTS poultry_farm_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE poultry_farm_system;

-- 1. incubation_batches
CREATE TABLE IF NOT EXISTS incubation_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_code VARCHAR(50) NOT NULL UNIQUE,
    parent_flock_info VARCHAR(255) NOT NULL COMMENT 'Source/breed, flock age',
    flock_age_weeks INT DEFAULT 32,
    eggs_set INT NOT NULL,
    egg_cost DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    start_date DATE NOT NULL,
    status ENUM('Active', 'Completed', 'Moved to Production') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. incubation_daily_logs
CREATE TABLE IF NOT EXISTS incubation_daily_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incubation_batch_id INT NOT NULL,
    log_date DATE NOT NULL,
    milestone VARCHAR(50) DEFAULT 'Daily Inspection' COMMENT 'Daily Inspection, Day 7/10 Candling, Day 18 Transfer',
    infertiles INT NOT NULL DEFAULT 0,
    early_deaths INT NOT NULL DEFAULT 0,
    cracked INT NOT NULL DEFAULT 0,
    dead_in_shell INT NOT NULL DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inc_logs_batch FOREIGN KEY (incubation_batch_id) REFERENCES incubation_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. incubation_expenses
CREATE TABLE IF NOT EXISTS incubation_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incubation_batch_id INT NOT NULL,
    expense_type ENUM('gas', 'repair') NOT NULL,
    gas_weight_kg DECIMAL(10, 2) DEFAULT 0.00,
    amount DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    description VARCHAR(255) DEFAULT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inc_exp_batch FOREIGN KEY (incubation_batch_id) REFERENCES incubation_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. hatch_breakouts
CREATE TABLE IF NOT EXISTS hatch_breakouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incubation_batch_id INT NOT NULL UNIQUE,
    live_chicks INT NOT NULL DEFAULT 0,
    culls INT NOT NULL DEFAULT 0,
    pipped INT NOT NULL DEFAULT 0,
    dead_in_shell INT NOT NULL DEFAULT 0,
    fertility_rate DECIMAL(6, 2) NOT NULL DEFAULT 0.00,
    hatchability_rate DECIMAL(6, 2) NOT NULL DEFAULT 0.00,
    cost_per_chick DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    breakout_date DATE DEFAULT (CURRENT_DATE),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hatch_batch FOREIGN KEY (incubation_batch_id) REFERENCES incubation_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. inventory_items
CREATE TABLE IF NOT EXISTS inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(150) NOT NULL,
    category ENUM('starter', 'grower', 'layer', 'medicine') NOT NULL,
    unit_type ENUM('g', 'ml') NOT NULL DEFAULT 'g',
    initial_quantity_g_or_ml DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    total_quantity_g_or_ml DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    total_cost DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    unit_cost_per_g_or_ml DECIMAL(14, 6) NOT NULL DEFAULT 0.000000,
    purchase_date DATE DEFAULT (CURRENT_DATE),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 6. production_batches
CREATE TABLE IF NOT EXISTS production_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_code VARCHAR(50) NOT NULL UNIQUE,
    incubation_batch_id INT DEFAULT NULL,
    flock_type VARCHAR(100) DEFAULT 'Broiler Commercial',
    initial_bird_count INT NOT NULL,
    current_bird_count INT NOT NULL,
    transferred_incubation_cost DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    house_rent_total DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    target_profit_margin_pct DECIMAL(6, 2) NOT NULL DEFAULT 25.00,
    start_date DATE NOT NULL,
    status ENUM('Active', 'Depleted', 'Closed') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_prod_inc_batch FOREIGN KEY (incubation_batch_id) REFERENCES incubation_batches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 7. production_daily_logs
CREATE TABLE IF NOT EXISTS production_daily_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_batch_id INT NOT NULL,
    log_date DATE NOT NULL,
    mortality_count INT NOT NULL DEFAULT 0,
    water_liters DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    feed_item_id INT DEFAULT NULL,
    feed_grams DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    feed_cost_calculated DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    med_item_id INT DEFAULT NULL,
    med_dose_g_ml DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    med_cost_calculated DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    labor_cost DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    sanitation_cost DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_prod_logs_batch FOREIGN KEY (production_batch_id) REFERENCES production_batches(id) ON DELETE CASCADE,
    CONSTRAINT fk_prod_logs_feed FOREIGN KEY (feed_item_id) REFERENCES inventory_items(id) ON DELETE SET NULL,
    CONSTRAINT fk_prod_logs_med FOREIGN KEY (med_item_id) REFERENCES inventory_items(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 8. batch_sales
CREATE TABLE IF NOT EXISTS batch_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    production_batch_id INT NOT NULL,
    sale_date DATE NOT NULL,
    buyer_name VARCHAR(150) DEFAULT 'Walk-in Buyer',
    buyer_contact VARCHAR(100) DEFAULT NULL,
    birds_sold INT NOT NULL,
    unit_price DECIMAL(14, 2) NOT NULL,
    total_amount DECIMAL(14, 2) NOT NULL,
    payment_status ENUM('Paid', 'Partial', 'Pending') NOT NULL DEFAULT 'Paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_batch FOREIGN KEY (production_batch_id) REFERENCES production_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB;

