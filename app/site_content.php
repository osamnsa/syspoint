<?php
/**
 * Everything on the public site that staff can edit, in one registry:
 * business details (contacts, addresses, hours, links, socials), the
 * letterhead used on quotes/invoices/receipts, and every page's copy and
 * hero images. Admin → Website renders a form from SITE_CONTENT, and views
 * read values with site('key') / site_image(...).
 *
 * Values live in content_blocks; a key with no row (or an empty one) shows
 * the default below, so the site looks exactly as designed until someone
 * edits it, and "Reset" simply deletes the row.
 *
 * Field: [label, default, type, help]. Types: text, textarea (newlines kept),
 * html (simple formatting, sanitised), url, email, image (uploaded file; the
 * default is the built-in picture).
 */

declare(strict_types=1);

const SITE_PLAZA = 'Awesome Plaza, Opposite Chicken Republic, Apo Resettlement, Abuja';

function site_content(): array
{
    static $groups = null;
    if ($groups !== null) return $groups;
    $f = fn(string $label, string $default = '', string $type = 'text', string $help = '') => compact('label', 'default', 'type', 'help');

    return $groups = [
        'site' => ['label' => 'Business details', 'page' => '', 'intro' => 'Used across the whole site — header, footer, Visit Us and contact sections.', 'fields' => [
            'site.brand_first' => $f('Brand name (white part)', 'Syspoint'),
            'site.brand_second' => $f('Brand name (gold part)', 'Hub'),
            'site.tagline' => $f('Tagline', '...challenging conventions'),
            'site.meta_description' => $f('Search engine description', 'Syspoint — computers & accessories, a software clinic, a gaming lounge, IT consulting, and internship training, all in one place.', 'textarea', 'Shown by Google under the site name. About 150 characters.'),
            'site.email' => $f('Email', 'syspointmail@gmail.com', 'email'),
            'site.phone' => $f('Phone number', '', 'text', 'Shown in the footer and contact page when filled in.'),
            'site.whatsapp' => $f('WhatsApp number', '', 'text', 'e.g. 0803 123 4567 — adds a WhatsApp link when filled in.'),
            'site.hours' => $f('Opening hours', '9am – 10pm'),
            'site.plaza' => $f('Plaza address', SITE_PLAZA),
            'site.maps_url' => $f('Google Maps link', 'https://www.google.com/maps/search/?api=1&query=Awesome+Plaza+Apo+Resettlement+Abuja', 'url'),
            'site.hub_suite' => $f('Hub — suite', 'Suite C1'),
            'site.hub_name' => $f('Hub — name', 'Syspoint Hub'),
            'site.hub_desc' => $f('Hub — what’s there', 'Gaming lounge, VR arena and IT training.'),
            'site.hub_short' => $f('Hub — short label', 'Gaming & Training', 'text', 'Used in the footer and contact page.'),
            'site.store_suite' => $f('Gadget store — suite', 'Suite C20'),
            'site.store_name' => $f('Gadget store — name', 'Gadget Store'),
            'site.store_desc' => $f('Gadget store — what’s there', 'Syspoint Solutions Consult Limited — computers, gadgets and the software clinic.'),
            'site.store_short' => $f('Gadget store — short label', 'Syspoint Solutions Consult Limited', 'text', 'Used in the footer and contact page.'),
            'site.consulting_url' => $f('IT Consulting website', (string) (config()['app']['consulting_url'] ?? ''), 'url', 'Adds “Consulting” to the menu and links the IT Consulting card. Leave empty to hide.'),
            'site.instagram' => $f('Instagram link', '', 'url'),
            'site.facebook' => $f('Facebook link', '', 'url'),
            'site.x' => $f('X (Twitter) link', '', 'url'),
            'site.linkedin' => $f('LinkedIn link', '', 'url'),
            'site.tiktok' => $f('TikTok link', '', 'url'),
            'site.youtube' => $f('YouTube link', '', 'url'),
            'site.footer_blurb' => $f('Footer description', 'PS5, VR, board games, IT training, computers & gadgets — all under one roof, with free internet for every gamer.', 'textarea'),
            'site.copyright_name' => $f('Copyright name', 'Syspoint Hub'),
            'site.footer_company' => $f('Footer company line', 'A Syspoint Solutions Consult Limited company'),
        ]],
        'company' => ['label' => 'Documents', 'page' => '', 'intro' => 'The letterhead on quotes, invoices and walk-in receipts.', 'fields' => [
            'company.name' => $f('Company name', 'Syspoint Solutions Consult Limited'),
            'company.address' => $f('Address', SITE_PLAZA, 'textarea'),
            'company.phone' => $f('Phone', ''),
            'company.email' => $f('Email', 'syspointmail@gmail.com', 'email'),
            'company.bank_details' => $f('Bank details (printed on invoices)', '', 'textarea', 'e.g. Bank name, account name, account number.'),
            'company.quote_terms' => $f('Default quote terms', "This quote is valid until the date shown. Prices in Naira.\nA 70% deposit is required to begin work; balance on delivery.", 'textarea'),
            'company.invoice_terms' => $f('Default invoice terms', "Payment by bank transfer to Syspoint Solutions Consult Limited.\nPlease use the invoice number as your payment reference.", 'textarea'),
            'company.receipt_footer' => $f('Receipt footer', 'Thank you for shopping with Syspoint. Keep this receipt for warranty claims.', 'textarea'),
        ]],
        'home' => ['label' => 'Home', 'page' => '', 'intro' => 'Clients, products, rooms, courses and testimonials come from their own admin pages; the hero stats from Home Stats.', 'fields' => [
            'home.hero_title_prefix' => $f('Hero headline (white)', 'Everything Tech,'),
            'home.hero_title_highlight' => $f('Hero headline (gold)', 'In One Place'),
            'home.hero_subtitle' => $f('Hero text', 'Computers and accessories, a software clinic that builds what your business needs, a gaming lounge with a VIP and common room, and a training centre turning out interns ready to work.', 'textarea'),
            'home.screen_gaming' => $f('Laptop screen 1 (gaming)', 'assets/img/home/hero-desk-gaming.jpg', 'image', 'The hero laptop fades between these three pictures. Use the same size and framing (1472 × 844).'),
            'home.screen_shop' => $f('Laptop screen 2 (shop)', 'assets/img/home/hero-desk-shop.jpg', 'image'),
            'home.screen_training' => $f('Laptop screen 3 (training)', 'assets/img/home/hero-desk-training.jpg', 'image'),
            'home.svc1_title' => $f('Service card 1 — title', 'Computers & Gadgets'),
            'home.svc1_text' => $f('Service card 1 — text', 'Genuine laptops & accessories'),
            'home.svc2_title' => $f('Service card 2 — title', 'Software Clinic'),
            'home.svc2_text' => $f('Service card 2 — text', 'We build what your business needs'),
            'home.svc3_title' => $f('Service card 3 — title', 'Gaming Lounge'),
            'home.svc3_text' => $f('Service card 3 — text', 'PS5, VR arena & board games'),
            'home.svc4_title' => $f('Service card 4 — title', 'IT Training'),
            'home.svc4_text' => $f('Service card 4 — text', 'Courses & internships'),
            'home.svc5_title' => $f('Service card 5 — title', 'IT Consulting'),
            'home.svc5_text' => $f('Service card 5 — text', 'Strategy for people & businesses'),
            'home.stats_cta' => $f('Stats bar button', 'Get in Touch →'),
            'home.tagline' => $f('Tagline words', 'Shop, Play, Learn, Build', 'text', 'Comma-separated; shown with gold dots between.'),
            'home.clients_kicker' => $f('Clients — small heading', 'Our clients'),
            'home.clients_title' => $f('Clients — heading', 'Organisations we’ve consulted with'),
            'home.clients_link' => $f('Clients — link text', 'Need IT consulting or software? Tell us what you need →'),
            'home.shop_eyebrow' => $f('Shop — small heading', 'Gadget Store'),
            'home.shop_title' => $f('Shop — heading', 'New in the Shop'),
            'home.shop_link' => $f('Shop — link text', 'Visit the store →'),
            'home.gaming_eyebrow' => $f('Gaming — small heading', 'Gaming Lounge'),
            'home.gaming_title' => $f('Gaming — heading', 'Play. Immerse. Unwind.'),
            'home.gaming_text' => $f('Gaming — text', 'PS5, a VR arena and a shelf of board games — book the VIP room for your squad or drop into the common room.', 'textarea'),
            'home.gaming_btn_book' => $f('Gaming — book button', 'Book a Room'),
            'home.gaming_btn_games' => $f('Gaming — games button', 'Browse Games'),
            'home.gaming_tag' => $f('Gaming — gold tag', 'Free internet for all gamers'),
            'home.training_eyebrow' => $f('Training — small heading', 'Training & Internship'),
            'home.training_title' => $f('Training — heading', 'Learn skills that get you hired'),
            'home.training_link' => $f('Training — link text', 'See all courses →'),
            'home.training_empty' => $f('Training — when no courses', 'Our course list is being updated — ask us about the next intake.'),
            'home.internship_text' => $f('Internship bar — text', 'Want real work experience? We take on interns across sales, software and IT consulting.', 'textarea'),
            'home.internship_btn' => $f('Internship bar — button', 'Ask About Internships'),
            'home.testimonials_eyebrow' => $f('Testimonials — small heading', 'Testimonials'),
            'home.testimonials_title' => $f('Testimonials — heading', 'What our clients say'),
            'home.visit_eyebrow' => $f('Visit us — small heading', 'Visit Us'),
            'home.visit_title' => $f('Visit us — heading', 'Two doors, one plaza'),
            'home.visit_btn' => $f('Visit us — directions button', 'Get Directions →'),
        ]],
        'about' => ['label' => 'About', 'page' => 'about', 'intro' => '', 'fields' => [
            'about.title' => $f('Page heading', 'About Syspoint'),
            'about.body' => $f('Story', '<p>Syspoint brings together the tech essentials people and businesses actually need: computers and accessories you can trust, a software clinic that builds and deploys what your business is missing, a gaming lounge to unwind in, and hands-on training that turns beginners into job-ready talent.</p><p>This page is ready for the real story — edit it from the admin panel once it is live.</p>', 'html', 'Use the buttons for headings, bold, lists and links.'),
        ]],
        'shop' => ['label' => 'Shop', 'page' => 'shop', 'intro' => 'Products and categories are managed in Store & Inventory.', 'fields' => [
            'shop.eyebrow' => $f('Hero — small heading', 'Discover. Shop. Upgrade.'),
            'shop.hero_title_prefix' => $f('Hero headline (white)', 'Latest Tech'),
            'shop.hero_title_highlight' => $f('Hero headline (gold)', 'Gadgets'),
            'shop.hero_subtitle' => $f('Hero text', 'Genuine gadgets and accessories, in stock now, with secure checkout and fast local delivery.', 'textarea'),
            'shop.hero_image' => $f('Hero picture', 'assets/img/shop/hero-laptops.jpg', 'image', 'About 728 × 520, product on a transparent or dark background works best.'),
            'shop.btn_primary' => $f('Hero button 1', 'Shop Now →'),
            'shop.btn_secondary' => $f('Hero button 2', 'Browse Collection'),
            'shop.categories_eyebrow' => $f('Categories — small heading', 'Browse'),
            'shop.categories_title' => $f('Categories — heading', 'Shop by Category'),
            'shop.new_eyebrow' => $f('New arrivals — small heading', 'Just In'),
            'shop.new_title' => $f('New arrivals — heading', 'New Arrivals'),
            'shop.visit_eyebrow' => $f('Visit — small heading', 'Visit the gadget store'),
            'shop.visit_title' => $f('Visit — heading', 'See it, try it, take it home'),
            'shop.badge1_title' => $f('Badge 1 — title', '100% Genuine'),
            'shop.badge1_text' => $f('Badge 1 — text', 'Authentic products only'),
            'shop.badge2_title' => $f('Badge 2 — title', 'Secure Checkout'),
            'shop.badge2_text' => $f('Badge 2 — text', 'Paystack-protected payments'),
            'shop.badge3_title' => $f('Badge 3 — title', 'Fast Delivery'),
            'shop.badge3_text' => $f('Badge 3 — text', 'Quick local dispatch'),
            'shop.badge4_title' => $f('Badge 4 — title', 'Real Support'),
            'shop.badge4_text' => $f('Badge 4 — text', 'We answer, quote your order ref'),
        ]],
        'clinic' => ['label' => 'Software Clinic', 'page' => 'software-clinic', 'intro' => 'The portfolio list comes from Website → Clients.', 'fields' => [
            'clinic.title' => $f('Page heading', 'Software Clinic'),
            'clinic.intro' => $f('Intro', 'Tell us what your business needs — a website, an app, a system to run your operations — and we\'ll follow up with a plan and a quote.', 'textarea'),
            'clinic.form_title' => $f('Form heading', 'Request Software'),
            'clinic.form_button' => $f('Form button', 'Send Request'),
            'clinic.portfolio_title' => $f('Portfolio heading', 'Businesses We\'ve Deployed For'),
        ]],
        'gaming' => ['label' => 'Gaming', 'page' => 'gaming', 'intro' => 'Rooms, rates and game lists are managed under Gaming.', 'fields' => [
            'gaming.kicker' => $f('Hero — small heading', '...challenging conventions'),
            'gaming.hero_title' => $f('Hero headline', 'Play. Immerse. Learn.'),
            'gaming.hero_subtitle' => $f('Hero text', 'PS5, VR and board games — all under one roof, with free internet for every gamer. Grab a controller, step into VR, or book the VIP room for your squad.', 'textarea'),
            'gaming.select_label' => $f('Above the cards', 'Select your game'),
            'gaming.card1_name' => $f('Card 1 — name', 'PS5'),
            'gaming.card1_sub' => $f('Card 1 — text', 'The latest PS5 titles'),
            'gaming.card1_image' => $f('Card 1 — picture', 'assets/img/gaming/ps5.jpg', 'image', 'Portrait, about 3:4.'),
            'gaming.card2_name' => $f('Card 2 — name', 'VR Arena'),
            'gaming.card2_sub' => $f('Card 2 — text', 'Step inside the game'),
            'gaming.card2_image' => $f('Card 2 — picture', 'assets/img/gaming/vr.jpg', 'image'),
            'gaming.card3_name' => $f('Card 3 — name', 'Board Games'),
            'gaming.card3_sub' => $f('Card 3 — text', 'Classic & modern table games'),
            'gaming.card3_image' => $f('Card 3 — picture', 'assets/img/gaming/board.jpg', 'image'),
            'gaming.book_btn' => $f('Book button', 'Book a Room'),
            'gaming.free_tag' => $f('Gold tag', 'Free internet for all gamers'),
            'gaming.marquee' => $f('Scrolling banner', 'PS5, VR Arena, Board Games, Free internet for all gamers, VIP & Common Rooms, Open 9am – 10pm', 'text', 'Comma-separated phrases.'),
            'gaming.rooms_eyebrow' => $f('Rooms — small heading', 'Stage 01 · Reserve a room'),
            'gaming.rooms_title' => $f('Rooms — heading', 'Rooms'),
            'gaming.ps5_eyebrow' => $f('PS5 list — small heading', 'Stage 02 · PS5'),
            'gaming.ps5_title' => $f('PS5 list — heading', 'PS5 Game List'),
            'gaming.ps5_lede' => $f('PS5 list — text', 'The latest PS5 titles, ready to play.'),
            'gaming.vr_eyebrow' => $f('VR list — small heading', 'Stage 03 · VR Arena'),
            'gaming.vr_title' => $f('VR list — heading', 'VR Experience List'),
            'gaming.vr_lede' => $f('VR list — text', 'Step inside the game with virtual reality.'),
            'gaming.board_eyebrow' => $f('Board list — small heading', 'Stage 04 · Board Games'),
            'gaming.board_title' => $f('Board list — heading', 'Board Game List'),
            'gaming.board_lede' => $f('Board list — text', 'Classic and modern indoor table games.'),
            'gaming.cta_title' => $f('Banner — heading', 'Ready, Player One?'),
            'gaming.cta_text' => $f('Banner — text', 'Grab a PS5 controller, strap in for VR, or pull up a board — book your room now.'),
            'gaming.cta_btn' => $f('Banner — button', 'Book Your Session →'),
            'gaming.badge1_title' => $f('Badge 1 — title', 'Premium Setups'),
            'gaming.badge1_text' => $f('Badge 1 — text', 'Latest PS5 consoles & VR rigs'),
            'gaming.badge2_title' => $f('Badge 2 — title', 'Free Internet'),
            'gaming.badge2_text' => $f('Badge 2 — text', 'For every gamer, every visit'),
            'gaming.badge3_title' => $f('Badge 3 — title', 'VIP & Common Rooms'),
            'gaming.badge3_text' => $f('Badge 3 — text', 'Private or open shared space'),
            'gaming.badge4_title' => $f('Badge 4 — title', 'Real Reservations'),
            'gaming.badge4_text' => $f('Badge 4 — text', 'Book a slot, we confirm it'),
        ]],
        'training' => ['label' => 'Training', 'page' => 'training', 'intro' => 'Courses are managed under Training → Courses.', 'fields' => [
            'training.kicker' => $f('Hero — small heading', 'Unlock your tech potential'),
            'training.title_script' => $f('Headline — script word', 'The'),
            'training.title_lines' => $f('Headline — lines', "IT Training\n& Internship\nProgram", 'textarea', 'One line per row.'),
            'training.intro' => $f('Hero text', 'Hands-on courses and an internship program built to get you job-ready.', 'textarea'),
            'training.hero_image' => $f('Hero picture', 'assets/img/training/hero-student.jpg', 'image'),
            'training.empty' => $f('When no courses', 'Our course list is being updated — ask us about the next intake.'),
            'training.mentor_kicker' => $f('Mentor — small heading', 'Your mentor'),
            'training.external_title' => $f('Mentor — heading', 'Learn with Charles Onuoha'),
            'training.mentor_role' => $f('Mentor — role', 'CEO, Syspoint Solutions Consult Limited'),
            'training.mentor_org' => $f('Mentor — second role', 'The Challenge Circle Nigeria'),
            'training.mentor_org_url' => $f('Mentor — second role link', 'https://thecircle.ng', 'url'),
            'training.mentor_tag' => $f('Mentor — photo tag', 'CEO · Mentor'),
            'training.mentor_photo' => $f('Mentor — main photo', 'assets/img/training/charles-portrait.jpg', 'image'),
            'training.mentor_photo_alt' => $f('Mentor — second photo', 'assets/img/training/charles-duotone.jpg', 'image'),
            'training.mentor_quote' => $f('Mentor — quote', 'Skills open doors. Mentorship shows you which ones to walk through.', 'textarea'),
            'training.external_body' => $f('Mentor — text', 'Explore more courses, resources and mentorship at CharlesOnuoha.com.', 'textarea'),
            'training.mentor_points' => $f('Mentor — bullet points', "Hands-on guidance on real projects\nCareer direction from someone who builds businesses\nA path from student to intern to professional", 'textarea', 'One point per line.'),
            'training.mentor_btn' => $f('Mentor — button 1', 'View Mentorship Program'),
            'training.mentor_btn_short' => $f('Mentor — button 1 (phones)', 'Mentorship Program'),
            'training.mentor_btn_url' => $f('Mentor — button 1 link', 'https://charlesonuoha.com/services/mentorship-and-internship', 'url'),
            'training.call_btn' => $f('Mentor — button 2', 'Free 30-Min Clarity Call'),
            'training.call_btn_short' => $f('Mentor — button 2 (phones)', 'Free 30 Mins Clarity Call'),
            'training.internship_title' => $f('Internship bar — heading', 'Our Internship Concept'),
            'training.internship_short' => $f('Internship bar — text', 'Real work on real projects across sales, software and IT consulting — mentorship built in.', 'textarea'),
            'training.internship_btn' => $f('Internship bar — button', 'Ask About Internships'),
            'training.internship_url' => $f('Internship bar — button link', 'https://corelink.ng/hubmember', 'url'),
            'training.tagline' => $f('Tagline words', 'Learn, Build, Intern, Grow', 'text', 'Comma-separated.'),
        ]],
        'contact' => ['label' => 'Contact', 'page' => 'contact', 'intro' => 'Address, email and hours come from Business details.', 'fields' => [
            'contact.title' => $f('Page heading', 'Get in Touch'),
            'contact.button' => $f('Form button', 'Send Message'),
            'contact.success' => $f('Thank-you message', 'Thanks {name}, your message has been received. We\'ll get back to you soon.', 'text', '{name} becomes the sender’s name.'),
        ]],
    ];
}

/** Field definition for a key (or null). */
function site_field(string $key): ?array
{
    foreach (site_content() as $group) {
        if (isset($group['fields'][$key])) return $group['fields'][$key];
    }
    return null;
}

/** Stored for a field someone deliberately emptied (an empty row means "use the default"). */
const SITE_EMPTY = '[empty]';

/** Current value of an editable site field (falls back to its default). */
function site(string $key): string
{
    $field = site_field($key);
    $value = content_block($key, $field['default'] ?? '');
    return $value === SITE_EMPTY ? '' : $value;
}

/** Save a field: the default (or a reset) removes the row; a deliberate blank is stored as SITE_EMPTY. */
function site_save(string $key, string $value): void
{
    $default = site_field($key)['default'] ?? '';
    if ($value === $default) {
        content_block_delete($key);
    } elseif ($value === '') {
        content_block_save($key, SITE_EMPTY);
    } else {
        content_block_save($key, $value);
    }
}

/** Has this field been changed from its default? */
function site_is_changed(string $key): bool
{
    return array_key_exists($key, content_blocks_all()) && content_blocks_all()[$key] !== '';
}

/** Comma-separated field as a list. */
function site_list(string $key, string $sep = ','): array
{
    return array_values(array_filter(array_map('trim', explode($sep, site($key))), fn($v) => $v !== ''));
}

/** Is an image field still the built-in picture? */
function site_image_is_default(string $key): bool
{
    return site($key) === (site_field($key)['default'] ?? '');
}

/**
 * <picture>/<img> for an editable image. Built-in pictures keep their WebP
 * twin; an uploaded replacement is served as-is.
 */
function site_picture(string $key, string $alt, array $attrs = [], string $class = ''): string
{
    $src = site($key);
    $attr = '';
    foreach ($attrs as $k => $v) $attr .= ' ' . $k . '="' . e((string) $v) . '"';
    $img = '<img src="' . e(media_url($src)) . '" alt="' . e($alt) . '"' . $attr . '>';
    if (site_image_is_default($key) && str_ends_with($src, '.jpg') && is_file(__DIR__ . '/../public/' . substr($src, 0, -4) . '.webp')) {
        return '<picture' . ($class ? ' class="' . e($class) . '"' : '') . '><source srcset="' . asset(substr($src, 0, -4) . '.webp') . '" type="image/webp">' . $img . '</picture>';
    }
    return '<picture' . ($class ? ' class="' . e($class) . '"' : '') . '>' . $img . '</picture>';
}

/** Keep only simple formatting in rich-text fields: no scripts, styles or event handlers. */
function site_sanitize_html(string $html): string
{
    $html = strip_tags($html, '<p><br><strong><b><em><i><u><a><ul><ol><li><h2><h3><blockquote>');
    $html = preg_replace('/\s(on\w+|style|class|id)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    $html = preg_replace_callback('/<a\b([^>]*)>/i', function ($m) {
        if (!preg_match('/href\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $m[1], $h)) return '<a>';
        $url = html_entity_decode($h[2] !== '' ? $h[2] : ($h[3] ?? ''));
        if (!preg_match('#^(https?://|mailto:|tel:|/|\#)#i', trim($url))) return '<a>';
        return '<a href="' . e(trim($url)) . '"' . (preg_match('#^https?://#i', $url) ? ' target="_blank" rel="noopener"' : '') . '>';
    }, $html);
    return trim((string) $html);
}

function content_block_delete(string $key): void
{
    db()->prepare('DELETE FROM content_blocks WHERE block_key = :key')->execute(['key' => $key]);
}

/** The plaza address broken over lines for an <address> block (first two commas become line breaks). */
function site_address_lines(string $suite): string
{
    return nl2br(e($suite . ', ' . preg_replace('/, /', ",\n", site('site.plaza'), 2)));
}

/** WhatsApp chat link for the site's number, or null. */
function site_whatsapp_url(): ?string
{
    $digits = crm_phone_digits(site('site.whatsapp'));
    return $digits ? 'https://wa.me/234' . ltrim($digits, '0') : null;
}
