-- PAWORA catalog schema migration
-- Adds researched-catalog fields to products, category descriptions, and the
-- product "Notify me" waiting list. Safe to run more than once.
--
-- Usage:  php database/migrate-catalog.php            (apply, wrapped in a transaction)
--         php database/migrate-catalog.php --dry-run  (print the plan, change nothing)

SET NAMES utf8mb4;

-- ---------------------------------------------------------------- products
SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE products ADD COLUMN brand VARCHAR(80) NULL AFTER category_id', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'brand');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE products ADD COLUMN currency CHAR(3) NOT NULL DEFAULT ''LKR'' AFTER price', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'currency');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE products ADD COLUMN price_type ENUM(''DEMO'', ''SOURCE'') NOT NULL DEFAULT ''DEMO'' AFTER currency', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'price_type');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE products ADD COLUMN price_source VARCHAR(160) NULL AFTER price_type', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'price_source');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE products ADD COLUMN rating DECIMAL(2,1) NULL AFTER low_stock_threshold', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'rating');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE products ADD COLUMN review_count INT UNSIGNED NULL AFTER rating', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'review_count');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE products ADD COLUMN image_url VARCHAR(500) NULL AFTER image', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'image_url');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE products ADD COLUMN image_alt VARCHAR(160) NULL AFTER image_url', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'image_alt');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE products ADD COLUMN source_url VARCHAR(500) NULL AFTER image_alt', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'source_url');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE products ADD COLUMN image_source_name VARCHAR(120) NULL AFTER source_url', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'image_source_name');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE products ADD CONSTRAINT chk_products_rating CHECK (rating IS NULL OR (rating >= 0 AND rating <= 5))', 'DO 0') FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND CONSTRAINT_NAME = 'chk_products_rating');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'CREATE INDEX idx_products_brand ON products (brand)', 'DO 0') FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND INDEX_NAME = 'idx_products_brand');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Existing rows are prototype data, so they are labelled as demo prices.
UPDATE products SET price_type = 'DEMO' WHERE price_type IS NULL OR price_type = '';
UPDATE products SET currency = 'LKR' WHERE currency IS NULL OR currency = '';
UPDATE products SET brand = 'PAWORA Demo' WHERE brand IS NULL OR brand = '';

-- ------------------------------------------------------------- categories
SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE categories ADD COLUMN description VARCHAR(255) NULL AFTER slug', 'DO 0') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'description');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Category names must match the agreed catalog list exactly.
UPDATE categories SET name = 'Bed', slug = 'bed' WHERE name IN ('Beds') AND slug = 'beds';
UPDATE categories SET name = 'Health and Care', slug = 'health-and-care' WHERE name IN ('Health & Care', 'Health and Care') AND slug IN ('health-care', 'health-and-care');
UPDATE categories SET description = 'Complete and balanced dry and wet food for dogs at every life stage.' WHERE slug = 'dog-food' AND description IS NULL;
UPDATE categories SET description = 'Everyday nutrition for adult cats and kittens, dry and wet recipes.' WHERE slug = 'cat-food' AND description IS NULL;
UPDATE categories SET description = 'Biscuits, dental treats and training rewards for dogs and cats.' WHERE slug = 'treats' AND description IS NULL;
UPDATE categories SET description = 'Interactive play, puzzle and enrichment toys for dogs and cats.' WHERE slug = 'toys' AND description IS NULL;
UPDATE categories SET description = 'Brushes, clippers, combs and shampoos for routine coat and nail care.' WHERE slug = 'grooming' AND description IS NULL;
UPDATE categories SET description = 'Bowls, bottles, carriers, cameras and travel essentials.' WHERE slug = 'accessories' AND description IS NULL;
UPDATE categories SET description = 'Cots, bolsters, orthopedic and self-warming beds for dogs and cats.' WHERE slug = 'bed' AND description IS NULL;
UPDATE categories SET description = 'Everyday, reflective and adjustable collars with a secure fit.' WHERE slug = 'collars' AND description IS NULL;
UPDATE categories SET description = 'Fixed, retractable and hands-free leads for walks and training.' WHERE slug = 'leashes' AND description IS NULL;
UPDATE categories SET description = 'Dental, supplement, topical and first-aid general pet-care products.' WHERE slug = 'health-and-care' AND description IS NULL;

-- ----------------------------------------------- product notify-me waiting list
CREATE TABLE IF NOT EXISTS product_waiting_list (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    product_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pwl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_pwl_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_pwl_product (product_id, created_at),
    INDEX idx_pwl_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_product_favorites UNIQUE (user_id, product_id),
    CONSTRAINT fk_pf_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_pf_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_pf_product (product_id)
) ENGINE=InnoDB;

-- Source-attribution columns added after the first catalog pass.
SET @sql = 'SELECT 1';
PREPARE stmt FROM @sql;

SET @sql = 'ALTER TABLE products MODIFY price_source VARCHAR(500) NULL';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE products ADD COLUMN source_price DECIMAL(10,2) NULL AFTER price_source';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE products ADD COLUMN source_currency CHAR(3) NULL AFTER source_price';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE products ADD COLUMN source_exchange_rate DECIMAL(14,4) NULL AFTER source_currency';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE products ADD COLUMN price_checked_on DATE NULL AFTER source_exchange_rate';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE products ADD COLUMN stock_type ENUM(''DEMO'', ''SOURCE'') NOT NULL DEFAULT ''DEMO'' AFTER low_stock_threshold';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE products ADD COLUMN stock_source VARCHAR(160) NULL AFTER stock_type';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE products ADD COLUMN rating_type ENUM(''DEMO'', ''SOURCE'') NULL AFTER review_count';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE products ADD COLUMN image_source_url VARCHAR(500) NULL AFTER image_source_name';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE products ADD CONSTRAINT chk_products_source_price CHECK (source_price IS NULL OR source_price >= 0)';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE products ADD CONSTRAINT chk_products_exchange_rate CHECK (source_exchange_rate IS NULL OR source_exchange_rate > 0)';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
