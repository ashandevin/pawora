# PAWORA

**Everything They Need. A Home They Deserve.**

A PHP/MySQL pet-care storefront and adoption platform with separate public,
customer and administrator workspaces.

---

## Contents

- [Highlights](#highlights)
- [Requirements](#requirements)
- [Setup](#setup)
- [Demo accounts](#demo-accounts)
- [Configuration](#configuration)
- [Product images](#product-images)
- [Catalog provenance](#catalog-provenance)
- [Project layout](#project-layout)
- [Security notes](#security-notes)
- [Testing](#testing)
- [License](#license)

---

## Highlights

| | |
|---|---|
| Products | 95 across 10 categories, 92 with verified source pricing |
| Brands | 51 manufacturers, prices sourced from 5 currencies (USD, CAD, GBP, EUR, AUD) |
| Adoption | Full application lifecycle with admin review workflow |
| Commerce | Session cart, stock-aware checkout, simulated card + cash on delivery |
| Roles | Public guest, customer and administrator workspaces |
| Stack | PHP 8, MySQL/MariaDB, Bootstrap 5, vanilla JavaScript |

### Features

**Storefront**

- Category browsing, pagination, price/name sorting, brand filter and search
- Product detail pages with stock state, favourites and notify-me
- Image fallback so the shop stays usable when remote images cannot load

**Adoption**

- Pet listings with availability status (Available, Pending, Reserved, Adopted)
- Per-pet adoption application with household and housing details
- Waiting list for unavailable pets
- Admin review workflow: `SUBMITTED → UNDER REVIEW → INTERVIEW → APPROVED → ADOPTION COMPLETED`
- Approving an adoption marks the pet `Adopted` and blocks duplicate applications

**Commerce**

- Session cart with stock clamping and quantity validation
- Free delivery above Rs 5,000, otherwise Rs 350
- Checkout with billing and shipping details
- Simulated card payment (Luhn + expiry check, only brand and last 4 digits stored)
  and cash on delivery
- Transactional order placement that is safe under concurrent stock changes
- Order status transitions with automatic restock and refund on cancellation
- Printable invoice
- Customers can cancel their own eligible orders; administrators manage all orders

**Customer area**

- Dashboard, orders, favourites, notifications, profile
- Adoption applications with per-application detail pages

**Administrator area**

- Dashboard with summary panels
- Product, category and pet CRUD with image upload
- Inventory stock adjustment
- Order management and status transitions
- Adoption application review
- Contact messages, homepage feedback and waiting list
- User management, reports and an admin activity log

---

## Requirements

- PHP 8.0 or newer with `pdo_mysql` (`mbstring` is used when present, with a
  UTF-8-safe fallback when it is not)
- MySQL 5.7+ or MariaDB 10.3+
- Any web server (Apache, or PHP's built-in server for development)

Developed and verified against **PHP 8.4.24** and **MariaDB 10.4.32**.

---

## Setup

### 1. Get the code

```bash
git clone <your-fork-url> pawora
cd pawora
```

### 2. Create the database

```bash
mysql -u root -p -e "CREATE DATABASE pawora_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p pawora_db < database/pawora_db.sql
```

The schema file is idempotent, so re-running it will not duplicate seed data.

### 3. Configure

```bash
cp .env.example .env
$EDITOR .env
```

The defaults match a stock LAMPP/XAMPP install, so `.env` is optional for a
local LAMP setup. See [Configuration](#configuration).

### 4. Make the upload folders writable

```bash
chmod 775 assets/images/products assets/images/pets
```

If your web server runs as a different user (for example `www-data` under
Apache), give that user ownership instead:

```bash
chown -R www-data:www-data assets/images/products assets/images/pets
```

### 5. Run it

**With Apache (LAMPP/XAMPP):** point the document root at the project, or drop
it into `htdocs/`, then browse to `http://localhost/pawora/`.

**With PHP's built-in server:**

```bash
PAWORA_BASE_URL=/ php -S 0.0.0.0:8080 -t .
```

Then open `http://localhost:8080/`.

> The built-in server is for development only. It has no TLS, so cookies are
> issued without the `secure` flag.

---

## Demo accounts

Seeded by `database/pawora_db.sql`. **These only exist in a database you create
yourself locally** — there is no hosted instance, so they are throwaway.

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@pawora.test` | `Admin@12345` |
| Customer | `ashan@example.com` | `User@12345` |
| Customer | `buyer@pawora.test` | `Buyer@12345` |

Change or delete these before deploying anywhere reachable from the internet.

---

## Configuration

All settings are read from the environment, so no credentials are stored in the
repository. `config/database.php` falls back to LAMPP defaults when a variable
is unset.

| Variable | Default | Purpose |
|---|---|---|
| `PAWORA_DB_HOST` | `127.0.0.1` | Database host |
| `PAWORA_DB_PORT` | `3306` | Database port |
| `PAWORA_DB_NAME` | `pawora_db` | Database name |
| `PAWORA_DB_USER` | `root` | Database user |
| `PAWORA_DB_PASS` | *(empty)* | Database password |
| `PAWORA_BASE_URL` | `/pawora` | Sub-folder the app is served from, no trailing slash |

Set `PAWORA_BASE_URL=/` if the app is the site root, or `/pawora` if it lives in
a sub-directory.

---

## Product images

**The 92 catalog photographs are intentionally not in this repository.** They
are third-party brand images downloaded from retailer CDNs during catalog
research, and they are not ours to redistribute. The repository is ~1 MB
instead of ~36 MB for this reason.

The shop works without them:

- `database/pawora_db.sql` stores the remote image URLs
- `assets/images/product-placeholder.svg` is used as a fallback

To restore the local copies, re-download them with the bundled importer:

```bash
php database/catalog/localize-images.php --apply
```

It writes `assets/images/products/<sku>.<ext>`, updates the `products.image`
column, and keeps the source URL for attribution. It processes at most 50 rows
per run, and retries oversized images at a smaller CDN width.

User-uploaded images are stored with a random 24-character hex filename and are
git-ignored, so no user content is ever committed.

---

## Catalog provenance

Every product records where its data came from. The `products` table carries:

- `price_type` — `SOURCE` (verified) or `DEMO` (house/placeholder row)
- `source_price`, `source_currency`, `source_url`, `source_sku`, `source_gtin`
- `image_source_name`, `image_source_url` — where the photograph came from
- `price_checked_at` — when the price was last verified

92 of 95 products are `SOURCE` rows with a machine-readable price and a working
attribution URL. The remaining 3 are PAWORA house products
(`PAWORA-LSH-001`, `PAWORA-TOY-001`, `PAWORA-TRT-001`) that use the local
placeholder and are not passed off as verified merchandise.

Research worksheets live in [`database/catalog/research/`](database/catalog/research/)
as Markdown and JSON, one pair per category, including the source URL and the
reasoning behind any substitution. The import pipeline is:

```bash
php database/catalog/build-research-sql.php <category>   # JSON -> products.sql
php database/catalog/import.php --category "<category>"  # dry run
php database/catalog/import.php --category "<category>" --apply
php database/catalog/localize-images.php --apply --id <id>
```

Build and import are paired per category — the generated `products.sql` is a
single shared file that each build overwrites.

---

## Project layout

```
├── admin/               # administrator workspace (16 pages)
├── user/                # customer workspace (8 pages)
├── includes/            # shared helpers, partials and validation
├── assets/              # CSS, JavaScript, images, icons
├── config/database.php  # PDO connection and app constants
├── database/
│   ├── pawora_db.sql    # schema + seed data
│   └── catalog/         # research worksheets and import tooling
├── *.php                # public storefront and shared pages
└── .htaccess            # Apache hardening rules
```

Server-side rules live in `includes/`:

| File | Responsibility |
|---|---|
| `auth.php` | Session handling, CSRF tokens, `require_auth()`, `is_admin()` |
| `functions.php` | Formatting, sanitising, upload handling, notifications |
| `cart-helpers.php` | Session cart, stock clamping, delivery calculation |
| `order-helpers.php` | Checkout validation, transactional order placement, status transitions |
| `product-admin-helpers.php` | Product validation and image lifecycle |
| `admin-helpers.php` | Pet validation and image lifecycle |

---

## Security notes

- Every state-changing request requires a CSRF token
- Prepared statements throughout; `PDO::EMULATE_PREPARES` is disabled
- Output is escaped at render time
- Session cookies are `HttpOnly`, `SameSite=Lax`, and `Secure` over HTTPS
- Session ID is regenerated on login; strict mode and cookies-only are enforced
- Uploads are validated by real MIME type, extension, size and dimensions
- Uploaded filenames are regenerated, and script execution is blocked in the
  upload folders via `.htaccess`
- Order, invoice and adoption records are scoped to the owning account
- `Strict SQL mode` is set per connection, with server-side length validation so
  over-length input is rejected rather than silently truncated

> Payment is **simulated**. No payment gateway is integrated. Card numbers are
> validated and then discarded — only the brand and last four digits are stored.

Before deploying publicly, also: change the demo passwords, serve over HTTPS,
and disable directory listing.

---

## Testing

No test framework is bundled. The project was verified with a set of
idempotent HTTP regression scripts covering the cart and checkout flow, the
adoption lifecycle, admin CRUD, image handling, access control, CSRF, IDOR,
open-redirect and XSS/SQL-injection probes, plus full guest, customer and
administrator crawls. See `AGENTS.md` for the harness index and the current
verified state.

---

## License

Coursework project. The product photographs are excluded because they belong to
their respective manufacturers. `LICENSE` is not yet specified — all rights
reserved by default.
