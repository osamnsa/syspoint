-- Homepage social proof carried over from the old WordPress site
-- (staging.syspoint.com.ng): client logos and testimonials.
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
    SELECT 'FPB', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/fpb.png', 40, 1 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM deployed_businesses WHERE name = 'FPB');
INSERT INTO deployed_businesses (name, logo_path, sort_order, is_active)
    SELECT 'FMC Bida', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/Backup_of_FMC-BIDA-LOGO-NEW-COVERTED-188x300-1.png', 50, 1 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM deployed_businesses WHERE name = 'FMC Bida');

INSERT INTO testimonials (quote, author_name, author_company, photo_path, sort_order)
    SELECT 'Nothing like having a solid team handle your projects. They deliver!', 'Precious E.C', 'Nacham Tench & Sol', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/Ebube2.jpg', 10 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM testimonials WHERE author_name = 'Precious E.C' AND quote = 'Nothing like having a solid team handle your projects. They deliver!');
INSERT INTO testimonials (quote, author_name, author_company, photo_path, sort_order)
    SELECT 'Professionalism at its peak. Reliability is it for me.', 'Elihu A.', 'Leads Glazing', 'https://staging.syspoint.com.ng/wp-content/uploads/2026/03/Picture.jpg', 20 FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM testimonials WHERE author_name = 'Elihu A.' AND quote = 'Professionalism at its peak. Reliability is it for me.');
