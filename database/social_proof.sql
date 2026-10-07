-- Homepage social proof carried over from the old WordPress site
-- (staging.syspoint.com.ng): client logos and testimonials, plus the full
-- list of institutions consulted for and the headline stats.
--
-- Run ONCE on any install, fresh or live, after schema.sql:
--   mysql -u root -p syspoint < database/social_proof.sql
-- Safe to re-run: each row is only inserted if no row with the same name
-- (clients) or same person + quote (testimonials) exists, so it never
-- duplicates and never overwrites edits made in the admin panel.
--
-- Client logos go into deployed_businesses — the one list behind the
-- homepage "Organisations we've worked with" section and the Software
-- Clinic portfolio (Admin -> Businesses).
--
-- Logos/photos still point at the old site's media library (media_url()
-- passes absolute URLs through). RE-UPLOAD THEM in the admin before the
-- WordPress site is taken down, or they'll break. Row 3's organisation
-- wasn't named on the old site, so it starts hidden: name it and tick
-- "Visible" in Admin -> Businesses.

-- Databases that ran an earlier version of this file have the two Bida
-- institutions under their short names; give them their full names first.
UPDATE deployed_businesses SET name = 'Federal Polytechnic, Bida' WHERE name = 'FPB';
UPDATE deployed_businesses SET name = 'Federal Medical Centre, Bida' WHERE name = 'FMC Bida';

INSERT INTO deployed_businesses (name, logo_path, sort_order, is_active)
    SELECT 'Access Bank', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/Access_Bank_PLC_Logo-scaled.png', 10, 1 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM deployed_businesses WHERE name = 'Access Bank');
INSERT INTO deployed_businesses (name, logo_path, sort_order, is_active)
    SELECT 'Obafemi Awolowo University', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/Oau.png', 20, 1 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM deployed_businesses WHERE name = 'Obafemi Awolowo University');
INSERT INTO deployed_businesses (name, logo_path, sort_order, is_active)
    SELECT 'Unnamed client (rename me)', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/images.png', 30, 0 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM deployed_businesses WHERE name = 'Unnamed client (rename me)');
INSERT INTO deployed_businesses (name, logo_path, sort_order, is_active)
    SELECT 'Federal Polytechnic, Bida', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/fpb.png', 40, 1 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM deployed_businesses WHERE name IN ('Federal Polytechnic, Bida', 'FPB'));
INSERT INTO deployed_businesses (name, logo_path, sort_order, is_active)
    SELECT 'Federal Medical Centre, Bida', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/Backup_of_FMC-BIDA-LOGO-NEW-COVERTED-188x300-1.png', 50, 1 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM deployed_businesses WHERE name IN ('Federal Medical Centre, Bida', 'FMC Bida'));

INSERT INTO testimonials (quote, author_name, author_company, photo_path, sort_order)
    SELECT 'Nothing like having a solid team handle your projects. They deliver!', 'Precious E.C', 'Nacham Tench & Sol', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/Ebube2.jpg', 10 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM testimonials WHERE author_name = 'Precious E.C' AND quote = 'Nothing like having a solid team handle your projects. They deliver!');
INSERT INTO testimonials (quote, author_name, author_company, photo_path, sort_order)
    SELECT 'Professionalism at its peak. Reliability is it for me.', 'Elihu A.', 'Leads Glazing', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/Picture.jpg', 20 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM testimonials WHERE author_name = 'Elihu A.' AND quote = 'Professionalism at its peak. Reliability is it for me.');

-- Institutions Syspoint has consulted for (list from Charles Onuoha, Oct 2026;
-- Federal Polytechnic and Federal Medical Centre, Bida are added above).
-- No logos yet: they show as gold initials until a logo is uploaded in
-- Admin -> Businesses.
INSERT INTO deployed_businesses (name, sort_order, is_active)
    SELECT 'National Cereals Research Institute, Badeggi', 120, 1 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM deployed_businesses WHERE name = 'National Cereals Research Institute, Badeggi');
INSERT INTO deployed_businesses (name, sort_order, is_active)
    SELECT 'University of Ibadan', 130, 1 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM deployed_businesses WHERE name = 'University of Ibadan');
INSERT INTO deployed_businesses (name, sort_order, is_active)
    SELECT 'University of Nigeria, Nsukka', 140, 1 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM deployed_businesses WHERE name = 'University of Nigeria, Nsukka');
INSERT INTO deployed_businesses (name, sort_order, is_active)
    SELECT 'Federal University of Technology, Minna', 150, 1 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM deployed_businesses WHERE name = 'Federal University of Technology, Minna');
INSERT INTO deployed_businesses (name, sort_order, is_active)
    SELECT 'IVCU, Ibadan', 160, 1 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM deployed_businesses WHERE name = 'IVCU, Ibadan');
INSERT INTO deployed_businesses (name, sort_order, is_active)
    SELECT 'St. Louis Model School', 170, 1 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM deployed_businesses WHERE name = 'St. Louis Model School');
INSERT INTO deployed_businesses (name, sort_order, is_active)
    SELECT 'Chapel of Glory, Bida', 180, 1 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM deployed_businesses WHERE name = 'Chapel of Glory, Bida');
INSERT INTO deployed_businesses (name, sort_order, is_active)
    SELECT 'Ladela Schools', 190, 1 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM deployed_businesses WHERE name = 'Ladela Schools');

-- Headline stats (Admin -> Home Stats). Only filled in where still empty, so
-- re-running never overwrites a figure changed in the admin.
INSERT INTO content_blocks (block_key, content) VALUES ('home.stat_clients', '573')
    ON DUPLICATE KEY UPDATE content = IF(content = '', VALUES(content), content);
INSERT INTO content_blocks (block_key, content) VALUES ('home.stat_years', '10')
    ON DUPLICATE KEY UPDATE content = IF(content = '', VALUES(content), content);
INSERT INTO content_blocks (block_key, content) VALUES ('home.stat_employees', '38')
    ON DUPLICATE KEY UPDATE content = IF(content = '', VALUES(content), content);
