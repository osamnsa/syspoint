-- OPTIONAL preview catalog — sample laptops for showing the shop to the
-- client before real inventory is entered. Not part of seed.sql (that file
-- is real starter content only). Every row is is_demo = 1, so the site shows
-- a "Demo" badge beside each price. Safe to run more than once: INSERT
-- IGNORE keys on the unique slug, so it never duplicates or overwrites.
-- Remove them later from Admin -> Products, or:
--   DELETE FROM products WHERE is_demo = 1;
-- Images live in public/assets/img/shop/products/ (committed with the site).

-- Text below is UTF-8 (₦, —, ’); without this a latin1 client garbles it.
SET NAMES utf8mb4;
INSERT IGNORE INTO products (category_id, name, slug, description, specs, price, stock_qty, image_path, is_active, is_demo) VALUES
    (1, 'Apple MacBook Air M3', 'apple-macbook-air-m3', 'Featherweight, fanless and all-day battery — the everyday laptop that just works.', '13.6" Liquid Retina display\n8GB unified memory\n256GB SSD\nApple M3 chip', 1850000.00, 4, 'assets/img/shop/products/macbook-air-m3.jpg', 1, 1),
    (1, 'Dell XPS 13 Plus', 'dell-xps-13-plus', 'A seamless glass touchpad, edge-to-edge keyboard and a stunning 13.4" display.', '13.4" display\n16GB RAM\n512GB SSD\nIntel Core i7', 2150000.00, 3, 'assets/img/shop/products/dell-xps-13-plus.jpg', 1, 1),
    (1, 'HP Spectre x360 14', 'hp-spectre-x360-14', 'A premium 2-in-1 that folds from laptop to tablet, with pen support.', '14.0" touch display\n16GB RAM\n512GB SSD\nIntel Core Ultra 7', 1950000.00, 3, 'assets/img/shop/products/hp-spectre-x360-14.jpg', 1, 1),
    (1, 'Lenovo ThinkPad X1 Carbon', 'lenovo-thinkpad-x1-carbon', 'The legendary business ultrabook — light, tough and built to get work done.', '14.0" display\n16GB RAM\n512GB SSD\nIntel Core Ultra 7', 2250000.00, 2, 'assets/img/shop/products/lenovo-thinkpad-x1-carbon.jpg', 1, 1),
    (1, 'ASUS ROG Zephyrus G14', 'asus-rog-zephyrus-g14', 'A compact 14" gaming powerhouse with an RTX graphics card.', '14.0" 120Hz display\n16GB RAM\n1TB SSD\nNVIDIA RTX 4060', 2850000.00, 2, 'assets/img/shop/products/asus-rog-zephyrus-g14.jpg', 1, 1),
    (1, 'Slim Ultrabook 14', 'slim-ultrabook-14', 'Lightweight and thin — perfect for working on the move.', '14.0" display\n16GB RAM\n512GB SSD', 950000.00, 6, 'assets/img/shop/products/ultrabook.jpg', 1, 1),
    (1, 'Gaming Laptop 15.6 RTX', 'gaming-laptop-15-rtx', 'High-performance gaming laptop with a fast refresh-rate screen.', '15.6" 144Hz display\n16GB RAM\n1TB SSD\nNVIDIA RTX graphics', 1450000.00, 4, 'assets/img/shop/products/gaming-laptop.jpg', 1, 1),
    (1, 'Business Laptop 14', 'business-laptop-14', 'Power and productivity for the office and beyond.', '14.0" display\n8GB RAM\n512GB SSD', 780000.00, 8, 'assets/img/shop/products/business-laptop.jpg', 1, 1),
    (1, '2-in-1 Convertible Laptop 13', 'convertible-laptop-13', 'Versatile and flexible — use it as a laptop, tent or tablet.', '13.3" touch display\n8GB RAM\n256GB SSD', 690000.00, 5, 'assets/img/shop/products/2-in-1-laptop.jpg', 1, 1),
    (1, 'Student Laptop 15.6', 'student-laptop-15', 'Study smart with a reliable, affordable everyday laptop.', '15.6" display\n8GB RAM\n256GB SSD', 420000.00, 10, 'assets/img/shop/products/student-laptop.jpg', 1, 1),
    (1, 'Desktop Workstation + 24" Monitor', 'desktop-workstation-24', 'Built for professionals — a tower PC with a 24" monitor, ready to set up.', 'Tower PC + 24" monitor\n16GB RAM\n1TB SSD\nKeyboard & mouse included', 1250000.00, 3, 'assets/img/shop/products/workstation.jpg', 1, 1);

-- Gadget samples (cut from the shop design reference and recoloured to the
-- brand): extra preview categories, one picture each for the category
-- cards, and seven demo products. Re-runnable like the laptops above.
INSERT IGNORE INTO product_categories (name, slug, sort_order, image_path) VALUES
    ('Audio', 'audio', 11, 'assets/img/shop/products/headphones.jpg'),
    ('Smartwatches', 'smartwatches', 12, 'assets/img/shop/products/smartwatch.jpg'),
    ('Phones', 'phones', 13, 'assets/img/shop/products/smartphone.jpg'),
    ('Gaming', 'gaming', 30, 'assets/img/shop/products/game-controller.jpg');
UPDATE product_categories SET image_path = 'assets/img/shop/products/power-bank.jpg' WHERE slug = 'accessories' AND image_path IS NULL;

INSERT IGNORE INTO products (category_id, name, slug, description, specs, price, stock_qty, image_path, is_active, is_demo)
    SELECT id, 'Wireless Over-Ear Headphones', 'wireless-over-ear-headphones', 'Deep, detailed sound with active noise cancelling and soft memory-foam cushions for all-day comfort.', 'Active noise cancelling\nUp to 40h battery\nBluetooth 5.3\nUSB-C fast charging', 185000.00, 6, 'assets/img/shop/products/headphones.jpg', 1, 1 FROM product_categories WHERE slug = 'audio';
INSERT IGNORE INTO products (category_id, name, slug, description, specs, price, stock_qty, image_path, is_active, is_demo)
    SELECT id, 'True Wireless Earbuds', 'true-wireless-earbuds', 'Compact earbuds with punchy bass, touch controls and a pocket-size charging case.', 'Charging case included\nUp to 24h with case\nTouch controls\nBluetooth 5.3', 45000.00, 12, 'assets/img/shop/products/earbuds-black.jpg', 1, 1 FROM product_categories WHERE slug = 'audio';
INSERT IGNORE INTO products (category_id, name, slug, description, specs, price, stock_qty, image_path, is_active, is_demo)
    SELECT id, 'Wireless Earbuds Pro (White)', 'wireless-earbuds-pro-white', 'Small size, big performance — noise cancelling earbuds with a transparency mode and wireless charging case.', 'Active noise cancelling\nTransparency mode\nWireless charging case\nSweat resistant', 95000.00, 8, 'assets/img/shop/products/earbuds-white.jpg', 1, 1 FROM product_categories WHERE slug = 'audio';
INSERT IGNORE INTO products (category_id, name, slug, description, specs, price, stock_qty, image_path, is_active, is_demo)
    SELECT id, 'Smartwatch S Series', 'smartwatch-s-series', 'Track workouts, heart rate and sleep, and see calls and messages at a glance.', '1.8" AMOLED display\nHeart-rate & SpO2\nUp to 7-day battery\nWater resistant', 120000.00, 7, 'assets/img/shop/products/smartwatch.jpg', 1, 1 FROM product_categories WHERE slug = 'smartwatches';
INSERT IGNORE INTO products (category_id, name, slug, description, specs, price, stock_qty, image_path, is_active, is_demo)
    SELECT id, 'Smartphone Pro 6.7"', 'smartphone-pro-6-7', 'A big, bright display, a triple camera and all-day battery — fast 5G in a slim body.', '6.7" OLED display\n256GB storage\nTriple camera\n5G', 650000.00, 4, 'assets/img/shop/products/smartphone.jpg', 1, 1 FROM product_categories WHERE slug = 'phones';
INSERT IGNORE INTO products (category_id, name, slug, description, specs, price, stock_qty, image_path, is_active, is_demo)
    SELECT id, 'Power Bank 20,000mAh', 'power-bank-20000mah', 'Keep your laptop and phone going — fast USB-C charging in a slim, pocketable case.', '20,000mAh capacity\nUSB-C PD fast charging\n2 × USB-A ports\nLED charge indicator', 38000.00, 15, 'assets/img/shop/products/power-bank.jpg', 1, 1 FROM product_categories WHERE slug = 'accessories';
INSERT IGNORE INTO products (category_id, name, slug, description, specs, price, stock_qty, image_path, is_active, is_demo)
    SELECT id, 'Wireless Game Controller', 'wireless-game-controller', 'Responsive sticks, rumble feedback and a comfortable grip for PC and Android gaming.', 'Bluetooth & USB-C\nWorks with PC and Android\nRumble feedback\nUp to 30h battery', 55000.00, 10, 'assets/img/shop/products/game-controller.jpg', 1, 1 FROM product_categories WHERE slug = 'gaming';
