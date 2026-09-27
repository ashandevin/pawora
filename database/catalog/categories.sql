-- PAWORA product catalog: the ten approved store categories.
-- Read by database/catalog/import.php. Safe to re-import: rows are upserted by name.
-- Exactly ten categories, in the order the storefront shows them.

START TRANSACTION;

-- Dog Food
INSERT INTO categories (name, slug, description, status) VALUES (
    'Dog Food',
    'dog-food',
    'Complete and balanced dry and wet food for dogs at every life stage.',
    'Active'
)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    name = VALUES(name),
    slug = VALUES(slug),
    status = VALUES(status);

-- Cat Food
INSERT INTO categories (name, slug, description, status) VALUES (
    'Cat Food',
    'cat-food',
    'Everyday nutrition for adult cats and kittens, dry and wet recipes.',
    'Active'
)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    name = VALUES(name),
    slug = VALUES(slug),
    status = VALUES(status);

-- Treats
INSERT INTO categories (name, slug, description, status) VALUES (
    'Treats',
    'treats',
    'Biscuits, dental treats and training rewards for dogs and cats.',
    'Active'
)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    name = VALUES(name),
    slug = VALUES(slug),
    status = VALUES(status);

-- Toys
INSERT INTO categories (name, slug, description, status) VALUES (
    'Toys',
    'toys',
    'Interactive play, puzzle and enrichment toys for dogs and cats.',
    'Active'
)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    name = VALUES(name),
    slug = VALUES(slug),
    status = VALUES(status);

-- Grooming
INSERT INTO categories (name, slug, description, status) VALUES (
    'Grooming',
    'grooming',
    'Brushes, clippers, combs and shampoos for routine coat and nail care.',
    'Active'
)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    name = VALUES(name),
    slug = VALUES(slug),
    status = VALUES(status);

-- Accessories
INSERT INTO categories (name, slug, description, status) VALUES (
    'Accessories',
    'accessories',
    'Bowls, bottles, carriers, cameras and travel essentials.',
    'Active'
)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    name = VALUES(name),
    slug = VALUES(slug),
    status = VALUES(status);

-- Bed
INSERT INTO categories (name, slug, description, status) VALUES (
    'Bed',
    'bed',
    'Cots, bolsters, orthopedic and self-warming beds for dogs and cats.',
    'Active'
)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    name = VALUES(name),
    slug = VALUES(slug),
    status = VALUES(status);

-- Collars
INSERT INTO categories (name, slug, description, status) VALUES (
    'Collars',
    'collars',
    'Everyday, reflective and adjustable collars with a secure fit.',
    'Active'
)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    name = VALUES(name),
    slug = VALUES(slug),
    status = VALUES(status);

-- Leashes
INSERT INTO categories (name, slug, description, status) VALUES (
    'Leashes',
    'leashes',
    'Fixed, retractable and hands-free leads for walks and training.',
    'Active'
)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    name = VALUES(name),
    slug = VALUES(slug),
    status = VALUES(status);

-- Health and Care
INSERT INTO categories (name, slug, description, status) VALUES (
    'Health and Care',
    'health-and-care',
    'Dental, supplement, topical and first-aid general pet-care products.',
    'Active'
)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    name = VALUES(name),
    slug = VALUES(slug),
    status = VALUES(status);

COMMIT;
