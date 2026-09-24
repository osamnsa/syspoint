-- Fresh-install-only starter content. Run exactly once, right after
-- schema.sql, against a brand-new empty database. NEVER re-run this
-- against a database that already has real content — INSERT IGNORE makes
-- a second run harmless (no duplicates, no error), but it also silently
-- won't push this starter copy over anything already edited, and can't
-- restore a row someone deliberately deleted. See README "Applying
-- Updates" for the full schema.sql-vs-seed.sql discipline.

-- Placeholder admin login — change the email/password from Admin ->
-- Settings (once that page exists) immediately after first login.
-- Email: admin@syspoint.example  Password: SyspointAdmin123!
INSERT IGNORE INTO users (id, name, email, password_hash, role) VALUES
    (1, 'Admin', 'admin@syspoint.example', '$2y$12$U.OT5X59uhOSE13GMmFXMOJLc1Cr7OvAcKRQoWfBGZCZbv8DcsnTy', 'admin');

-- Starter categories so the gadget catalog has somewhere to attach
-- products to as soon as Phase 2 lands.
INSERT IGNORE INTO product_categories (id, name, slug, sort_order) VALUES
    (1, 'Computers', 'computers', 10),
    (2, 'Accessories', 'accessories', 20);

-- The two physical rooms mentioned in the brief — real hourly rates/photos
-- to be set by the admin once Phase 4 (gaming) lands.
INSERT IGNORE INTO gaming_rooms (id, name, slug, description, hourly_rate, capacity) VALUES
    (1, 'VIP Room', 'vip-room', 'A private room for a smaller group.', 5000.00, 6),
    (2, 'Common Room', 'common-room', 'Open shared gaming space.', 1500.00, 20);
