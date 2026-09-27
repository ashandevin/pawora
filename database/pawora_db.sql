CREATE DATABASE IF NOT EXISTS pawora_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pawora_db;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(40) NULL,
    address TEXT NULL,
    role ENUM('USER', 'ADMIN') NOT NULL DEFAULT 'USER',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    species ENUM('Dog', 'Cat', 'Rabbit', 'Bird', 'Other') NOT NULL,
    breed VARCHAR(120) NOT NULL,
    age_months SMALLINT UNSIGNED NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    size ENUM('Small', 'Medium', 'Large') NOT NULL,
    location VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    personality TEXT NULL,
    health_status VARCHAR(255) NULL,
    vaccinated TINYINT(1) NOT NULL DEFAULT 0,
    image VARCHAR(500) NULL,
    status ENUM('Available', 'Pending', 'Reserved', 'Adopted') NOT NULL DEFAULT 'Available',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pets_species_status (species, status),
    INDEX idx_pets_breed (breed),
    INDEX idx_pets_location (location),
    INDEX idx_pets_featured (featured, status),
    INDEX idx_pets_updated (updated_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS adoption_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    pet_id INT UNSIGNED NOT NULL,
    application_reference VARCHAR(30) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    address TEXT NOT NULL,
    home_type VARCHAR(80) NOT NULL,
    living_situation VARCHAR(120) NOT NULL,
    previous_pet_experience VARCHAR(120) NOT NULL,
    family_members TINYINT UNSIGNED NOT NULL,
    working_hours VARCHAR(120) NOT NULL,
    experience_with_animals VARCHAR(120) NOT NULL,
    reason TEXT NOT NULL,
    additional_information TEXT NULL,
    status ENUM('SUBMITTED', 'UNDER REVIEW', 'INTERVIEW', 'APPROVED', 'REJECTED', 'ADOPTION COMPLETED') NOT NULL DEFAULT 'SUBMITTED',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_applications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_applications_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    INDEX idx_applications_user (user_id, created_at),
    INDEX idx_applications_pet_status (pet_id, status),
    INDEX idx_applications_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    pet_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_favorites_user_pet UNIQUE (user_id, pet_id),
    CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_favorites_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    INDEX idx_favorites_pet (pet_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS waiting_list (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    pet_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_waiting_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_waiting_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    INDEX idx_waiting_pet (pet_id, created_at),
    INDEX idx_waiting_email (email)
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

CREATE TABLE IF NOT EXISTS feedback (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    status ENUM('PENDING', 'APPROVED', 'HIDDEN') NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_feedback_status (status, created_at),
    CONSTRAINT chk_feedback_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    category ENUM('General Question', 'Complaint', 'Technical Issue', 'Pet Inquiry', 'Adoption Issue', 'Other') NOT NULL,
    subject VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('NEW', 'IN_PROGRESS', 'RESOLVED', 'CLOSED') NOT NULL DEFAULT 'NEW',
    reference_number VARCHAR(30) NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_contact_status (status, created_at),
    INDEX idx_contact_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user_read (user_id, is_read, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS success_stories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_name VARCHAR(100) NOT NULL,
    image VARCHAR(500) NULL,
    story TEXT NOT NULL,
    adoption_date DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stories_adoption_date (adoption_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_logs_user FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_admin_logs_admin (admin_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_categories_name UNIQUE (name),
    CONSTRAINT uq_categories_slug UNIQUE (slug),
    INDEX idx_categories_status (status, name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    brand VARCHAR(80) NULL,
    name VARCHAR(160) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'LKR',
    price_type ENUM('DEMO', 'SOURCE') NOT NULL DEFAULT 'DEMO',
    price_source VARCHAR(500) NULL,
    source_price DECIMAL(10,2) NULL,
    source_currency CHAR(3) NULL,
    source_exchange_rate DECIMAL(14,4) NULL,
    price_checked_on DATE NULL,
    discount DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    sku VARCHAR(50) NOT NULL,
    stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    low_stock_threshold INT UNSIGNED NOT NULL DEFAULT 5,
    stock_type ENUM('DEMO', 'SOURCE') NOT NULL DEFAULT 'DEMO',
    stock_source VARCHAR(160) NULL,
    rating DECIMAL(2,1) NULL,
    review_count INT UNSIGNED NULL,
    rating_type ENUM('DEMO', 'SOURCE') NULL,
    image VARCHAR(500) NULL,
    image_url VARCHAR(500) NULL,
    image_alt VARCHAR(160) NULL,
    source_url VARCHAR(500) NULL,
    image_source_name VARCHAR(120) NULL,
    image_source_url VARCHAR(500) NULL,
    status ENUM('Active', 'Inactive', 'Out of Stock') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_products_sku UNIQUE (sku),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    CONSTRAINT chk_products_price CHECK (price >= 0),
    CONSTRAINT chk_products_discount CHECK (discount >= 0 AND discount <= 100),
    CONSTRAINT chk_products_threshold CHECK (low_stock_threshold >= 0),
    CONSTRAINT chk_products_rating CHECK (rating IS NULL OR (rating >= 0 AND rating <= 5)),
    CONSTRAINT chk_products_source_price CHECK (source_price IS NULL OR source_price >= 0),
    CONSTRAINT chk_products_exchange_rate CHECK (source_exchange_rate IS NULL OR source_exchange_rate > 0),
    INDEX idx_products_category (category_id, status),
    INDEX idx_products_stock (stock_quantity, low_stock_threshold),
    INDEX idx_products_brand (brand),
    INDEX idx_products_updated (updated_at)
) ENGINE=InnoDB;

DROP TRIGGER IF EXISTS trg_products_stock_status_before_insert;
CREATE TRIGGER trg_products_stock_status_before_insert
BEFORE INSERT ON products
FOR EACH ROW
SET NEW.status = CASE
    WHEN NEW.stock_quantity = 0 AND NEW.status <> 'Inactive' THEN 'Out of Stock'
    WHEN NEW.stock_quantity > 0 AND NEW.status = 'Out of Stock' THEN 'Active'
    ELSE NEW.status
END;

DROP TRIGGER IF EXISTS trg_products_stock_status_before_update;
CREATE TRIGGER trg_products_stock_status_before_update
BEFORE UPDATE ON products
FOR EACH ROW
SET NEW.status = CASE
    WHEN NEW.stock_quantity = 0 AND NEW.status <> 'Inactive' THEN 'Out of Stock'
    WHEN NEW.stock_quantity > 0 AND NEW.status = 'Out of Stock' THEN 'Active'
    ELSE NEW.status
END;

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    order_reference VARCHAR(30) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    shipping DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('PENDING', 'PAID', 'PROCESSING', 'SHIPPED', 'DELIVERED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
    payment_status ENUM('PENDING', 'PAID', 'FAILED', 'REFUNDED') NOT NULL DEFAULT 'PENDING',
    billing_name VARCHAR(120) NOT NULL,
    billing_email VARCHAR(190) NOT NULL,
    billing_phone VARCHAR(40) NULL,
    shipping_address TEXT NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_orders_reference UNIQUE (order_reference),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_orders_subtotal CHECK (subtotal >= 0),
    CONSTRAINT chk_orders_shipping CHECK (shipping >= 0),
    CONSTRAINT chk_orders_total CHECK (total >= 0),
    INDEX idx_orders_user (user_id, created_at),
    INDEX idx_orders_status (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_name VARCHAR(160) NOT NULL,
    sku VARCHAR(50) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    CONSTRAINT chk_order_items_quantity CHECK (quantity > 0),
    CONSTRAINT chk_order_items_unit_price CHECK (unit_price >= 0),
    CONSTRAINT chk_order_items_line_total CHECK (line_total >= 0),
    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    method VARCHAR(30) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reference VARCHAR(40) NOT NULL,
    status ENUM('PENDING', 'PAID', 'FAILED', 'REFUNDED') NOT NULL DEFAULT 'PENDING',
    card_brand VARCHAR(30) NULL,
    card_last_four CHAR(4) NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_payments_reference UNIQUE (reference),
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT chk_payments_amount CHECK (amount >= 0),
    INDEX idx_payments_order (order_id),
    INDEX idx_payments_status (status)
) ENGINE=InnoDB;

INSERT IGNORE INTO categories (id, name, slug, description, status) VALUES
(1, 'Dog Food', 'dog-food', 'Complete and balanced dry and wet food for dogs at every life stage.', 'Active'),
(2, 'Cat Food', 'cat-food', 'Everyday nutrition for adult cats and kittens, dry and wet recipes.', 'Active'),
(3, 'Treats', 'treats', 'Biscuits, dental treats and training rewards for dogs and cats.', 'Active'),
(4, 'Toys', 'toys', 'Interactive play, puzzle and enrichment toys for dogs and cats.', 'Active'),
(5, 'Grooming', 'grooming', 'Brushes, clippers, combs and shampoos for routine coat and nail care.', 'Active'),
(6, 'Accessories', 'accessories', 'Bowls, bottles, carriers, cameras and travel essentials.', 'Active'),
(7, 'Bed', 'bed', 'Cots, bolsters, orthopedic and self-warming beds for dogs and cats.', 'Active'),
(8, 'Collars', 'collars', 'Everyday, reflective and adjustable collars with a secure fit.', 'Active'),
(9, 'Leashes', 'leashes', 'Fixed, retractable and hands-free leads for walks and training.', 'Active'),
(10, 'Health and Care', 'health-and-care', 'Dental, supplement, topical and first-aid general pet-care products.', 'Active');

INSERT IGNORE INTO products (id, category_id, name, description, price, discount, sku, stock_quantity, low_stock_threshold, image, status) VALUES
(1, 1, 'Gentle Growth Puppy Food', 'Nutritious everyday food formulated for growing puppies, with balanced protein and gentle grains for sensitive digestion.', 24.99, 10.00, 'PF001', 18, 5, 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?auto=format&fit=crop&w=1000&q=85', 'Active'),
(2, 1, 'Daily Feast Adult Dog Food', 'A hearty, balanced recipe for adult dogs that helps support steady energy and a healthy daily routine.', 29.99, 0.00, 'PF002', 3, 5, 'https://images.unsplash.com/photo-1558788353-f76d92427f16?auto=format&fit=crop&w=1000&q=85', 'Active'),
(3, 2, 'Ocean Bites Salmon Cat Food', 'A gentle salmon recipe for cats, made with complete nutrition and a satisfying texture for everyday meals.', 18.50, 15.00, 'PF003', 0, 4, 'https://images.unsplash.com/photo-1548802673-380ab8ebc7b7?auto=format&fit=crop&w=1000&q=85', 'Out of Stock'),
(4, 3, 'Gentle Bites Training Treats', 'Small, soft and reward-friendly treats made for training sessions, walks and quiet moments at home.', 9.99, 0.00, 'PT001', 32, 6, 'https://images.unsplash.com/photo-1583337130417-3346a1be7dee?auto=format&fit=crop&w=1000&q=85', 'Active'),
(5, 4, 'FeatherFun Wand', 'A light, interactive toy that turns playtime into gentle movement and keeps curious cats engaged.', 7.99, 20.00, 'TY001', 12, 4, 'https://images.unsplash.com/photo-1530281700549-e82e7bf110d6?auto=format&fit=crop&w=1000&q=85', 'Active'),
(6, 5, 'Calm Coat Grooming Kit', 'A simple grooming set for brushing, cleaning and keeping everyday coat care comfortable for pets and people.', 34.00, 0.00, 'GR001', 6, 3, 'https://images.unsplash.com/photo-1516734212186-a967f81ad0d7?auto=format&fit=crop&w=1000&q=85', 'Active'),
(7, 6, 'Everyday Walking Harness', 'A comfortable, adjustable harness for everyday walks, with a secure fit designed for active little companions.', 22.00, 10.00, 'AC001', 9, 4, 'https://images.unsplash.com/photo-1601758228041-f3b2795255f1?auto=format&fit=crop&w=1000&q=85', 'Active'),
(8, 7, 'Cloudrest Pet Bed', 'A soft, supportive resting space with a washable cover for naps, quiet corners and new beginnings.', 42.00, 0.00, 'BD001', 4, 3, 'https://images.unsplash.com/photo-1541599540903-216a46ca1dc0?auto=format&fit=crop&w=1000&q=85', 'Active'),
(9, 8, 'Soft Control Collar', 'A light, comfortable everyday collar with an adjustable fit for gentle handling and responsible walks.', 16.50, 0.00, 'CL001', 15, 5, 'https://images.unsplash.com/photo-1558788353-f76d92427f16?auto=format&fit=crop&w=1000&q=85', 'Active'),
(10, 10, 'Daily Care Kit', 'A thoughtful little kit of everyday care essentials for grooming, comfort and regular pet wellbeing checks.', 27.50, 10.00, 'HC001', 2, 5, 'https://images.unsplash.com/photo-1576201836106-db1758fd1c97?auto=format&fit=crop&w=1000&q=85', 'Active');

INSERT IGNORE INTO users (id, name, email, password, phone, address, role) VALUES
(1, 'PAWORA Admin', 'admin@pawora.test', '$2y$12$JKR0r6L2h2z//11Bstj/a.xj2pPFxFcPh/SLxnb/R38vYchiugDk.', '+94 11 234 5678', 'PAWORA demonstration office, Colombo', 'ADMIN'),
(2, 'Ashan Perera', 'ashan@example.com', '$2y$12$JKR0r6L2h2z//11Bstj/a.xj2pPFxFcPh/SLxnb/R38vYchiugDk.', '+94 77 123 4567', 'Colombo, Sri Lanka', 'USER');

INSERT IGNORE INTO pets (id, name, species, breed, age_months, gender, size, location, description, personality, health_status, vaccinated, image, status, featured) VALUES
(1, 'Luna', 'Dog', 'Golden Retriever', 24, 'Female', 'Large', 'Colombo', 'Luna is a gentle, people-focused companion who is learning to trust new friendships. She enjoys quiet walks, sunny afternoons and being part of a household.', 'Affectionate, calm, playful and thoughtful around other gentle dogs.', 'Healthy and ready for a home check.', 1, 'https://images.unsplash.com/photo-1552053831-71594a27632d?auto=format&fit=crop&w=1200&q=85', 'Available', 1),
(2, 'Max', 'Dog', 'Labrador Retriever', 36, 'Male', 'Large', 'Kandy', 'Max is an optimistic, food-motivated Labrador who would love an active family and a daily routine filled with walks and games.', 'Friendly, energetic, loyal and eager to learn.', 'Healthy with routine veterinary care.', 1, 'https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1200&q=85', 'Available', 1),
(3, 'Milo', 'Cat', 'Domestic Shorthair', 12, 'Male', 'Small', 'Gampaha', 'Milo is a curious young cat with a playful side. He likes window watching, soft beds and the company of people who respect his space.', 'Curious, independent, affectionate on his own terms.', 'Healthy and neutered.', 1, 'https://images.unsplash.com/photo-1518791841217-8f162f1e1131?auto=format&fit=crop&w=1200&q=85', 'Available', 1),
(4, 'Bella', 'Dog', 'Beagle', 48, 'Female', 'Medium', 'Negombo', 'Bella is a cheerful, scent-driven Beagle who has already found a loving forever home. Her story is shared to celebrate a successful adoption.', 'Cheerful, sociable, curious and resilient.', 'Healthy and happy in her new home.', 1, 'https://images.unsplash.com/photo-1507146426996-ef05306b995a?auto=format&fit=crop&w=1200&q=85', 'Adopted', 0),
(5, 'Coco', 'Cat', 'Persian', 24, 'Female', 'Medium', 'Colombo', 'Coco is a calm, elegant Persian who enjoys peaceful homes and gentle brushing. She is currently with a foster carer while her adoption plan is being reviewed.', 'Quiet, gentle, routine-oriented and affectionate.', 'Under veterinary review; grooming support required.', 0, 'https://images.unsplash.com/photo-1573865526739-10659fec78a5?auto=format&fit=crop&w=1200&q=85', 'Pending', 1),
(6, 'Rocky', 'Dog', 'German Shepherd', 36, 'Male', 'Large', 'Kurunegala', 'Rocky is a thoughtful, loyal companion who would suit an active adopter experienced with large, intelligent breeds.', 'Loyal, intelligent, protective and trainable.', 'Healthy, vaccinated and microchipped.', 1, 'https://images.unsplash.com/photo-1589941013453-ec89f33b5e95?auto=format&fit=crop&w=1200&q=85', 'Available', 1),
(7, 'Nala', 'Cat', 'Mixed Breed', 24, 'Female', 'Small', 'Kandy', 'Nala is a sweet, observant cat who enjoys a predictable routine and a sunny spot near her favorite person.', 'Sweet, observant, playful and moderately active.', 'Healthy with completed routine vaccinations.', 1, 'https://images.unsplash.com/photo-1519052537078-e6302a4968d4?auto=format&fit=crop&w=1200&q=85', 'Available', 1),
(8, 'Oliver', 'Rabbit', 'Mini Lop', 12, 'Male', 'Small', 'Colombo', 'Oliver is a friendly Mini Lop who is learning litter habits and enjoys a calm home with space to explore.', 'Gentle, curious, food-motivated and friendly.', 'Healthy; spay/neuter and parasite care complete.', 1, 'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?auto=format&fit=crop&w=1200&q=85', 'Reserved', 0);

INSERT IGNORE INTO adoption_applications (user_id, pet_id, application_reference, full_name, email, phone, address, home_type, living_situation, previous_pet_experience, family_members, working_hours, experience_with_animals, reason, additional_information, status) VALUES
(2, 1, 'PAW-2026-00124', 'Ashan Perera', 'ashan@example.com', '+94 77 123 4567', 'Colombo, Sri Lanka', 'Apartment', 'Living with family', 'Some experience caring for family pets', 3, 'Office hours, usually 9am to 5pm', 'Comfortable with dogs and calm handling', 'We want to build a calm, responsible home where Luna can feel safe and loved.', 'Happy to meet her for a home compatibility conversation.', 'UNDER REVIEW');

INSERT IGNORE INTO notifications (user_id, title, message, is_read) VALUES
(2, 'Your adoption application has been received', 'We received your application for Luna. The PAWORA team will review it shortly.', 0),
(2, 'Welcome to PAWORA', 'Your account is ready. Keep an eye on your applications and saved pets.', 0);

INSERT IGNORE INTO success_stories (id, pet_name, image, story, adoption_date) VALUES
(1, 'Bella', 'https://images.unsplash.com/photo-1507146426996-ef05306b995a?auto=format&fit=crop&w=1000&q=85', 'Bella found a family that celebrates her curiosity, daily walks and wonderfully noisy nose. Her story is a reminder that a patient match can change everything.', '2025-11-14'),
(2, 'Mochi', 'https://images.unsplash.com/photo-1574158622682-e40e69881006?auto=format&fit=crop&w=1000&q=85', 'Machi was adopted by a first-time guardian who learned her favorite games and built a confident little routine around her.', '2025-08-22'),
(3, 'Shadow', 'https://images.unsplash.com/photo-1553884828-4f8827a57b7b?auto=format&fit=crop&w=1000&q=85', 'Shadow found a calm home with a patient guardian. He now enjoys quiet afternoons, soft toys and a sunny garden.', '2025-05-09'),
(4, 'Coco', 'https://images.unsplash.com/photo-1573865526739-10659fec78a5?auto=format&fit=crop&w=1000&q=85', 'Coco is currently building her next chapter with a foster family. Her story shows how care, patience and shared decisions create trust.', '2025-03-18'),
(5, 'Bruno', 'https://images.unsplash.com/photo-1558788353-f76d92427f16?auto=format&fit=crop&w=1000&q=85', 'Bruno was matched with an active family who gave him the time, guidance and structure he needed to thrive.', '2024-12-02');

INSERT IGNORE INTO feedback (user_id, rating, comment, status) VALUES
(2, 5, 'The adoption process felt calm and transparent. We knew what to expect at every step.', 'APPROVED'),
(NULL, 5, 'A thoughtful platform that makes responsible adoption easier to begin.', 'APPROVED'),
(NULL, 4, 'We found a wonderful match and felt supported after bringing our pet home.', 'APPROVED');

INSERT IGNORE INTO admin_logs (admin_id, action, description) VALUES
(1, 'SEED', 'Loaded the PAWORA demonstration dataset.');
