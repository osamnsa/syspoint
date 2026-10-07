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

-- Homepage social proof, carried over from the previous WordPress site
-- (staging.syspoint.com.ng). Logos/photos point at that site's media
-- library until an admin re-uploads them here — re-upload before the old
-- site is taken down. Row 3's organisation wasn't named on the old site,
-- so it starts hidden: name it and tick "Visible" in Admin -> Clients.
INSERT IGNORE INTO clients (id, name, logo_path, sort_order, is_active) VALUES
    (1, 'Access Bank', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/Access_Bank_PLC_Logo-scaled.png', 10, 1),
    (2, 'Obafemi Awolowo University', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/Oau.png', 20, 1),
    (3, 'Unnamed client (rename me)', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/images.png', 30, 0),
    (4, 'FPB', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/fpb.png', 40, 1),
    (5, 'FMC Bida', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/Backup_of_FMC-BIDA-LOGO-NEW-COVERTED-188x300-1.png', 50, 1);

INSERT IGNORE INTO testimonials (id, quote, author_name, author_company, photo_path, sort_order) VALUES
    (1, 'Nothing like having a solid team handle your projects. They deliver!', 'Precious E.C', 'Nacham Tench & Sol', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/Ebube2.jpg', 10),
    (2, 'Professionalism at its peak. Reliability is it for me.', 'Elihu A.', 'Leads Glazing', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/Picture.jpg', 20);
