-- Syspoint database schema.
--
-- Written entirely with CREATE TABLE IF NOT EXISTS / ADD COLUMN IF NOT EXISTS
-- so this file is always safe to re-run against a live, populated database —
-- every deploy runs this, it only ever adds what's missing, never touches
-- existing rows. See README "Applying Updates" for the full discipline
-- (schema.sql = every deploy, seed.sql = once, at install, never again).

-- ---------------------------------------------------------------------------
-- Admin auth
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff roles: 'admin' sees everything; 'staff' sees only the areas ticked
-- for them in Admin -> Staff (comma-separated keys from ADMIN_AREAS in
-- app/admin.php, e.g. 'sales,store').
ALTER TABLE users MODIFY COLUMN role VARCHAR(20) NOT NULL DEFAULT 'staff';
ALTER TABLE users ADD COLUMN IF NOT EXISTS areas VARCHAR(255) NULL AFTER role;
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER areas;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_at TIMESTAMP NULL AFTER is_active;

-- ---------------------------------------------------------------------------
-- Gadget sales: categories -> products -> cart-derived orders.
-- Two top-level categories today (Computers, Accessories) but parent_id
-- allows subcategories later without a schema change.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS product_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    parent_id INT UNSIGNED NULL,
    image_path VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_product_categories_parent FOREIGN KEY (parent_id) REFERENCES product_categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    slug VARCHAR(190) NOT NULL UNIQUE,
    description TEXT NULL,
    specs TEXT NULL,
    price DECIMAL(12,2) NOT NULL,
    stock_qty INT UNSIGNED NOT NULL DEFAULT 0,
    image_path VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES product_categories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Marks placeholder/preview listings so a "Demo" label can render next to
-- the price and nobody mistakes them for real inventory or pricing. Real
-- products added through the admin panel default to 0.
ALTER TABLE products ADD COLUMN IF NOT EXISTS is_demo TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;

-- Extra gallery images beyond the product's own primary image_path.
CREATE TABLE IF NOT EXISTS product_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- The cart itself is session-based (no DB table) — see app/cart.php. An
-- order is only ever created at checkout, from whatever's in the session
-- cart at that moment.
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_ref VARCHAR(40) NOT NULL UNIQUE,
    customer_name VARCHAR(150) NOT NULL,
    customer_email VARCHAR(190) NOT NULL,
    customer_phone VARCHAR(30) NULL,
    delivery_address TEXT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid', 'failed') NOT NULL DEFAULT 'unpaid',
    payment_reference VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- product_name/unit_price are snapshotted at order time (not read live off
-- products) so a historic order stays accurate even after a later price
-- change or the product being deleted — product_id itself is nullable with
-- ON DELETE SET NULL for exactly that reason.
CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_name VARCHAR(190) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Software Clinic: request-a-build form + a portfolio of businesses served.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS software_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_name VARCHAR(190) NOT NULL,
    contact_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) NULL,
    description TEXT NOT NULL,
    status ENUM('new', 'in_review', 'quoted', 'closed') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS deployed_businesses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    description VARCHAR(255) NULL,
    logo_path VARCHAR(255) NULL,
    website_url VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Gaming: three catalogs — PS5, VR, and board games (type distinguishes
-- them) — plus the two physical rooms and their bookings.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS games (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('ps5', 'vr', 'board') NOT NULL,
    name VARCHAR(190) NOT NULL,
    description TEXT NULL,
    price DECIMAL(12,2) NULL,
    image_path VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Same "Demo" labeling as products.is_demo — see the comment there.
ALTER TABLE games ADD COLUMN IF NOT EXISTS is_demo TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;

-- `type` started as ENUM('video','board') before the catalog split into
-- PS5 vs VR specifically (a branch briefly renamed 'ps5' to 'pc' — the
-- catalog is PS5, so that was reverted). A column can't hold a value
-- outside its current enum, so widen it first (union of every old + new
-- value), remap 'video' and 'pc' rows to 'ps5', then narrow to the final
-- three — safe to re-run against a database at any of those stages.
ALTER TABLE games MODIFY COLUMN type ENUM('video', 'board', 'ps5', 'vr', 'pc') NOT NULL;
UPDATE games SET type = 'ps5' WHERE type IN ('video', 'pc');
ALTER TABLE games MODIFY COLUMN type ENUM('ps5', 'vr', 'board') NOT NULL;

-- Two rows expected (VIP, Common) but not hardcoded as an enum, so a third
-- room is just another admin-added row, not a schema change.
CREATE TABLE IF NOT EXISTS gaming_rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    hourly_rate DECIMAL(10,2) NOT NULL,
    capacity INT UNSIGNED NULL,
    image_path VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Same "Demo" labeling as products.is_demo: the seeded room rates are
-- placeholders until the admin sets real ones, and they lead straight to
-- a booking, so they must be marked as such on the public page.
ALTER TABLE gaming_rooms ADD COLUMN IF NOT EXISTS is_demo TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;

-- A reservation request, not a paid booking — admin confirms/declines from
-- the admin panel (see "Phase 4" notes in README once built). idx_room_date
-- backs the availability check (same room + date) that both the booking
-- form and the admin calendar view run.
CREATE TABLE IF NOT EXISTS room_bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id INT UNSIGNED NOT NULL,
    customer_name VARCHAR(150) NOT NULL,
    customer_email VARCHAR(190) NOT NULL,
    customer_phone VARCHAR(30) NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    party_size INT UNSIGNED NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_room_bookings_room FOREIGN KEY (room_id) REFERENCES gaming_rooms (id) ON DELETE CASCADE,
    INDEX idx_room_bookings_date (room_id, booking_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Training & Internship
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS training_courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    description TEXT NULL,
    duration_label VARCHAR(100) NULL,
    price DECIMAL(12,2) NULL,
    image_path VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Shared across every section
-- ---------------------------------------------------------------------------

-- Lightweight in-house CMS: a view calls content_block('some.key', $default)
-- in place of a literal string; nothing changes until an admin actually
-- edits it, so a fresh install renders identically to before this table
-- existed. Used for editable copy on Home/About/the Internship page rather
-- than hardcoding it.
CREATE TABLE IF NOT EXISTS content_blocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    block_key VARCHAR(100) NOT NULL UNIQUE,
    content MEDIUMTEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(190) NULL,
    message TEXT NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Homepage social proof: testimonials, admin-managed. Client logos are
-- deployed_businesses rows (one list for the homepage and the Software
-- Clinic portfolio). The three headline stats (clients, years in business,
-- employees) are content_blocks, not a table — three numbers, not a list.
-- photo_path is a path under public/ (an admin upload) or an absolute
-- http(s) URL — see media_url(); deployed_businesses.logo_path likewise.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS testimonials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quote TEXT NOT NULL,
    author_name VARCHAR(150) NOT NULL,
    author_company VARCHAR(190) NULL,
    photo_path VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Inventory: stock ledger, suppliers & purchase orders, walk-in POS, serial
-- numbers / warranty, and the Hub's own equipment. products.stock_qty stays
-- the live on-hand figure; every change to it is also written to
-- stock_movements (app/inventory.php inventory_move()), so the ledger always
-- explains the number.
-- ---------------------------------------------------------------------------
ALTER TABLE products ADD COLUMN IF NOT EXISTS sku VARCHAR(60) NULL AFTER slug;
ALTER TABLE products ADD COLUMN IF NOT EXISTS cost_price DECIMAL(12,2) NULL AFTER price;
ALTER TABLE products ADD COLUMN IF NOT EXISTS reorder_level INT UNSIGNED NOT NULL DEFAULT 3 AFTER stock_qty;
ALTER TABLE products ADD COLUMN IF NOT EXISTS track_serials TINYINT(1) NOT NULL DEFAULT 0 AFTER reorder_level;
ALTER TABLE products ADD COLUMN IF NOT EXISTS warranty_months INT UNSIGNED NULL AFTER track_serials;
CREATE INDEX IF NOT EXISTS idx_products_sku ON products (sku);

CREATE TABLE IF NOT EXISTS stock_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    change_qty INT NOT NULL,
    balance_after INT NOT NULL,
    reason ENUM('opening', 'purchase', 'online_sale', 'pos_sale', 'adjustment', 'return', 'damage', 'order_cancelled', 'sale_voided') NOT NULL,
    ref_type VARCHAR(30) NULL,
    ref_id INT UNSIGNED NULL,
    note VARCHAR(255) NULL,
    user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_movements_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    INDEX idx_stock_movements_product (product_id, created_at),
    INDEX idx_stock_movements_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products that already had stock before the ledger existed get one
-- 'opening' row so their history starts from the right number.
INSERT INTO stock_movements (product_id, change_qty, balance_after, reason, note)
    SELECT p.id, p.stock_qty, p.stock_qty, 'opening', 'Stock on hand when inventory tracking started'
    FROM products p
    WHERE p.stock_qty > 0 AND NOT EXISTS (SELECT 1 FROM stock_movements m WHERE m.product_id = p.id);

CREATE TABLE IF NOT EXISTS suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    contact_name VARCHAR(150) NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(190) NULL,
    address TEXT NULL,
    notes TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(30) NOT NULL UNIQUE,
    supplier_id INT UNSIGNED NOT NULL,
    status ENUM('draft', 'ordered', 'partially_received', 'received', 'cancelled') NOT NULL DEFAULT 'draft',
    order_date DATE NULL,
    expected_date DATE NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchase_orders_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    qty_ordered INT UNSIGNED NOT NULL,
    qty_received INT UNSIGNED NOT NULL DEFAULT 0,
    unit_cost DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_po_items_po FOREIGN KEY (po_id) REFERENCES purchase_orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_po_items_product FOREIGN KEY (product_id) REFERENCES products (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pos_sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receipt_no VARCHAR(30) NOT NULL UNIQUE,
    customer_name VARCHAR(150) NULL,
    customer_phone VARCHAR(40) NULL,
    customer_email VARCHAR(190) NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    discount DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,
    payment_method ENUM('cash', 'transfer', 'card', 'split') NOT NULL DEFAULT 'cash',
    amount_paid DECIMAL(12,2) NULL,
    status ENUM('completed', 'voided') NOT NULL DEFAULT 'completed',
    void_reason VARCHAR(255) NULL,
    notes VARCHAR(255) NULL,
    cashier_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pos_sales_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pos_sale_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_name VARCHAR(190) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    unit_cost DECIMAL(12,2) NULL,
    quantity INT UNSIGNED NOT NULL,
    CONSTRAINT fk_pos_items_sale FOREIGN KEY (sale_id) REFERENCES pos_sales (id) ON DELETE CASCADE,
    CONSTRAINT fk_pos_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per physical unit of a serial-tracked product (laptops, phones).
CREATE TABLE IF NOT EXISTS product_units (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    serial VARCHAR(120) NOT NULL UNIQUE,
    status ENUM('in_stock', 'sold', 'returned', 'faulty') NOT NULL DEFAULT 'in_stock',
    po_id INT UNSIGNED NULL,
    received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sold_at TIMESTAMP NULL,
    sale_type ENUM('online', 'pos') NULL,
    sale_id INT UNSIGNED NULL,
    customer_name VARCHAR(150) NULL,
    customer_phone VARCHAR(40) NULL,
    warranty_until DATE NULL,
    notes VARCHAR(255) NULL,
    CONSTRAINT fk_product_units_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    INDEX idx_product_units_product (product_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- The Hub's own equipment (not for sale): consoles, controllers, VR headsets…
CREATE TABLE IF NOT EXISTS assets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_tag VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    category ENUM('console', 'controller', 'vr_headset', 'pc', 'display', 'audio', 'network', 'furniture', 'other') NOT NULL DEFAULT 'other',
    room_id INT UNSIGNED NULL,
    location VARCHAR(120) NULL,
    serial VARCHAR(120) NULL,
    purchase_date DATE NULL,
    purchase_cost DECIMAL(12,2) NULL,
    status ENUM('in_use', 'spare', 'in_repair', 'retired') NOT NULL DEFAULT 'in_use',
    asset_condition ENUM('good', 'fair', 'poor') NOT NULL DEFAULT 'good',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_assets_room FOREIGN KEY (room_id) REFERENCES gaming_rooms (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS asset_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    type ENUM('note', 'repair', 'moved', 'status') NOT NULL DEFAULT 'note',
    description VARCHAR(500) NOT NULL,
    cost DECIMAL(12,2) NULL,
    user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_asset_logs_asset FOREIGN KEY (asset_id) REFERENCES assets (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- CRM: one customer record per person/organisation, linked from every place
-- the site captures someone (orders, bookings, software requests, contact
-- messages, walk-in sales) by email, else phone. Deals pipeline, timeline
-- (notes/calls), tasks, and quotes & invoices with payments.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('person', 'organisation') NOT NULL DEFAULT 'person',
    name VARCHAR(190) NOT NULL,
    organisation_id INT UNSIGNED NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(40) NULL,
    phone_digits VARCHAR(20) NULL,
    address TEXT NULL,
    source ENUM('website', 'walk_in', 'referral', 'social', 'event', 'phone', 'other') NOT NULL DEFAULT 'website',
    tags VARCHAR(255) NULL,
    notes TEXT NULL,
    owner_id INT UNSIGNED NULL,
    last_activity_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_customers_org FOREIGN KEY (organisation_id) REFERENCES customers (id) ON DELETE SET NULL,
    INDEX idx_customers_email (email),
    INDEX idx_customers_phone (phone_digits),
    INDEX idx_customers_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_id INT UNSIGNED NULL AFTER id;
ALTER TABLE room_bookings ADD COLUMN IF NOT EXISTS customer_id INT UNSIGNED NULL AFTER id;
ALTER TABLE software_requests ADD COLUMN IF NOT EXISTS customer_id INT UNSIGNED NULL AFTER id;
ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS customer_id INT UNSIGNED NULL AFTER id;
ALTER TABLE pos_sales ADD COLUMN IF NOT EXISTS customer_id INT UNSIGNED NULL AFTER id;
CREATE INDEX IF NOT EXISTS idx_orders_customer ON orders (customer_id);
CREATE INDEX IF NOT EXISTS idx_room_bookings_customer ON room_bookings (customer_id);
CREATE INDEX IF NOT EXISTS idx_software_requests_customer ON software_requests (customer_id);
CREATE INDEX IF NOT EXISTS idx_contact_messages_customer ON contact_messages (customer_id);
CREATE INDEX IF NOT EXISTS idx_pos_sales_customer ON pos_sales (customer_id);

CREATE TABLE IF NOT EXISTS deals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    service ENUM('software', 'consulting', 'hardware', 'training', 'gaming', 'other') NOT NULL DEFAULT 'software',
    value DECIMAL(14,2) NOT NULL DEFAULT 0,
    stage ENUM('lead', 'contacted', 'proposal', 'negotiation', 'won', 'lost') NOT NULL DEFAULT 'lead',
    expected_close DATE NULL,
    owner_id INT UNSIGNED NULL,
    request_id INT UNSIGNED NULL,
    lost_reason VARCHAR(255) NULL,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_deals_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE,
    INDEX idx_deals_stage (stage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NULL,
    deal_id INT UNSIGNED NULL,
    type ENUM('note', 'call', 'email', 'meeting', 'whatsapp', 'stage') NOT NULL DEFAULT 'note',
    body TEXT NOT NULL,
    user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activities_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE,
    CONSTRAINT fk_activities_deal FOREIGN KEY (deal_id) REFERENCES deals (id) ON DELETE CASCADE,
    INDEX idx_activities_customer (customer_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    due_date DATE NULL,
    priority ENUM('normal', 'high') NOT NULL DEFAULT 'normal',
    status ENUM('open', 'done') NOT NULL DEFAULT 'open',
    customer_id INT UNSIGNED NULL,
    deal_id INT UNSIGNED NULL,
    assigned_to INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE,
    CONSTRAINT fk_tasks_deal FOREIGN KEY (deal_id) REFERENCES deals (id) ON DELETE CASCADE,
    INDEX idx_tasks_open (status, due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Quotes and invoices share one table (type) and numbering per type/year.
CREATE TABLE IF NOT EXISTS crm_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('quote', 'invoice') NOT NULL,
    number VARCHAR(30) NOT NULL UNIQUE,
    customer_id INT UNSIGNED NOT NULL,
    deal_id INT UNSIGNED NULL,
    status ENUM('draft', 'sent', 'accepted', 'declined', 'expired', 'part_paid', 'paid', 'void') NOT NULL DEFAULT 'draft',
    issue_date DATE NOT NULL,
    due_date DATE NULL,
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
    discount DECIMAL(14,2) NOT NULL DEFAULT 0,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    tax DECIMAL(14,2) NOT NULL DEFAULT 0,
    total DECIMAL(14,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(14,2) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    terms TEXT NULL,
    source_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_documents_customer FOREIGN KEY (customer_id) REFERENCES customers (id),
    CONSTRAINT fk_documents_deal FOREIGN KEY (deal_id) REFERENCES deals (id) ON DELETE SET NULL,
    INDEX idx_documents_type (type, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_document_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id INT UNSIGNED NOT NULL,
    description VARCHAR(500) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(14,2) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_document_items_doc FOREIGN KEY (document_id) REFERENCES crm_documents (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id INT UNSIGNED NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    method ENUM('transfer', 'cash', 'card', 'cheque', 'other') NOT NULL DEFAULT 'transfer',
    paid_on DATE NOT NULL,
    reference VARCHAR(120) NULL,
    user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_doc FOREIGN KEY (document_id) REFERENCES crm_documents (id) ON DELETE CASCADE,
    INDEX idx_payments_paid_on (paid_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
