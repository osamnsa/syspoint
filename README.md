# Syspoint — Website

Computers & accessories, a software clinic, a gaming lounge (VIP + common
room), IT consulting, and internship/training — one site, five offerings.
Server-rendered PHP, vanilla CSS/JS, MySQL. No frameworks, no build step,
no Composer dependencies — same proven approach as The Icon's site, chosen
for the same reasons: cheap shared hosting, nothing to compile, nothing to
keep updated but the code itself.

## Requirements

- PHP 8.1+ (with `pdo_mysql`)
- MySQL or MariaDB
- No other dependencies

## Local Setup

1. Copy the env file and fill in your local DB credentials:

   ```bash
   cp .env.example .env
   ```

2. Create the database and import the schema + starter data:

   ```bash
   mysql -u root -p -e "CREATE DATABASE syspoint"
   mysql -u root -p syspoint < database/schema.sql
   mysql -u root -p syspoint < database/seed.sql
   ```

   `seed.sql` is for this one-time fresh install only — see **Applying
   Updates** below for what to run (and what *not* to run) against a
   database that already has real content in it.

3. Start PHP's built-in server, pointed at `public/`:

   ```bash
   cd public
   php -S 127.0.0.1:8000 index.php
   ```

4. Visit `http://127.0.0.1:8000`. Admin panel: `/admin` —
   `admin@syspoint.example` / `SyspointAdmin123!` (change this immediately
   once real credentials are set up; see seed.sql).

   `public/index.php` includes a small `PHP_SAPI === 'cli-server'` check
   so CSS/JS/uploaded images actually load under this dev server — PHP's
   built-in server always runs the router script for every request
   unless that script explicitly opts a file out, unlike Apache, which
   already keeps real files away from the router via `.htaccess`. Only
   matters for local `php -S`; production is unaffected either way.

## Applying Updates

Same discipline as every other build of this shape:

- **Run `database/schema.sql` on every update, always.** Written entirely
  with `CREATE TABLE IF NOT EXISTS` / `ADD COLUMN IF NOT EXISTS`, so
  re-running it against a live database is always safe.
- **Never re-run `database/seed.sql` against a live database.**
  One-time-only fresh-install content. It uses `INSERT IGNORE`, so a
  second run can't duplicate rows or error, but it also won't push this
  starter copy over anything already edited in the admin panel, and can't
  restore a row someone deliberately deleted.

**schema.sql = every deploy. seed.sql = once, at install, never again.**

## Production Deployment

Document root must point at `public/`, never the repo root — `app/`,
`database/`, and `.env` all sit outside `public/` on purpose so they're
never reachable by URL. See The Icon's site README for the fuller
cPanel-specific walkthrough (document root field, the repo-root fallback
`index.php`/`.htaccess`, credential rotation if this was ever deployed
wrong) — identical situation, identical fix, not repeated here.

## Project Structure

```
app/
  config.php           Loads .env, exposes config() — see the comment in
                         that file for why it's a cached function and not
                         a plain `require`-returned array (redeclare-safe).
  bootstrap.php          Session start, PDO connection (db()), requires
                          every domain file — each phase adds its own file
                          here as it's built.
  helpers.php              e(), csrf_token()/csrf_field()/verify_csrf(),
                            flash(), path()/asset()/url(), slugify(),
                            handle_image_upload()
  admin.php                 Session-based admin auth, staff roles/areas and
                              central page access (ADMIN_AREA_PATHS)
  content_blocks.php         Lightweight in-house CMS for editable copy
  products.php                Category/product query helpers
  cart.php                     Session-based cart (no DB table)
  orders.php                    Transactional checkout (stock-locking),
                                  order lookups, idempotent payment marking
  paystack.php                   Paystack REST client (init/verify/webhook)
  mailer.php                      Order confirmation email
  software_clinic.php              Deployed-business portfolio lookups
  gaming.php                         Games, rooms, and booking-overlap check
  training.php                         Training course lookups
  showcase.php                          Homepage testimonials, stats, media_url()
  charts.php                             Server-rendered SVG charts (line, bars, donut)
  dashboard.php                           Numbers behind the admin dashboards
  views/
    partials/            header.php, nav.php, footer.php,
                           admin_header.php (sidebar shell), admin_nav.php
                           (menu + icons), admin_backdrop.php, admin_footer.php
    pages/                 One file per route
database/
  schema.sql              Full schema, every phase, safe to re-run
  seed.sql                 Starter content — run ONCE at fresh install only
public/
  index.php               Front controller / router (plain regex => file
                            array, no router dependency)
  .htaccess                 Clean URLs (Apache) + cache/compression headers
  assets/                    css/, js/ — no build step, edit and refresh
  uploads/                    Media uploads (gitignored)
```

## Build Status

- [x] **Phase 1 — Foundation**: DB schema covering every planned section
      (gadget catalog + orders, software clinic, gaming + room bookings,
      training, shared content/contact tables), app core
      (config/bootstrap/router/helpers/admin auth), design system CSS,
      Home/About/Contact, admin login + a starter dashboard.
- [x] **Phase 2 — Gadget sales**: Computers/Accessories catalog (shop
      landing, category, product detail), session cart, checkout with
      transactional stock-locking, Paystack payment (init/verify/webhook),
      order confirmation, admin product/category/order management.
- [x] **Phase 3 — Software Clinic**: request-a-build form (with
      honeypot), portfolio of deployed businesses, admin request triage
      and portfolio management.
- [x] **Phase 4 — Gaming**: video/board game lists, VIP + common room
      reservation system with overlap checking, admin games/rooms/bookings
      management.
- [x] **Phase 5 — Training & Internship**: course showcase (admin-managed),
      internship program section, Consulting nav/home links out to The
      Icon's site once `CONSULTING_URL` is set.
- [ ] **Phase 6 — Admin panel**: full CRUD across every section above,
      grown-out dashboard stats.
- [ ] **Phase 7 — Polish**: SEO, mobile pass, Telegram/email
      notifications, final end-to-end test, deploy.

### Phase 1 notes

Built the whole schema up front rather than growing it phase by phase —
unlike The Icon's site (which genuinely grew feature by feature over
time, so its schema grew the same way), this project's full scope was
known from the brief before writing a line of code, so laying out every
table now avoids repeated `ALTER TABLE` churn later. Each phase still
only *builds against* the tables it actually needs; the rest sit ready
and unused until their phase lands.

**A real bug caught while first bringing the site up, not a design
choice**: `config.php` originally returned its settings array via a
top-level `return [...]`, loaded with a plain `require __DIR__ . '/config.php'`
from several places (`bootstrap.php`'s `db()`, `helpers.php`'s
`base_path()`/`url()`). A plain `require` re-executes the file on every
call — the second call re-declared the `env()` function and fataled
("Cannot redeclare function env()"). Switching those calls to
`require_once` would have "fixed" the crash but introduced a quieter
second bug: PHP only returns a `require_once`'d file's actual return
value on the *first* inclusion — every call after that gets `true`
instead of the config array. Fixed properly by turning `config.php` into
a `config(): array` function with a cached static value, called
everywhere instead of re-required — no redeclare risk, no stale-`true`
risk, and it reads better at every call site besides.

Tested end-to-end: every Phase 1 route (`/`, `/about`, `/contact`,
`/admin/login`, `/admin`) returns the right status (200/302/404 as
appropriate); the contact form's honeypot-gated submission round-trips
into `contact_messages` and shows up correctly in the admin dashboard's
Recent Messages table with an unread badge; admin login rejects a wrong
password with a clear error and accepts the right one; an unauthenticated
visit to `/admin` redirects to `/admin/login`; the mobile nav toggle
opens/closes correctly at a 390px viewport. `schema.sql` and `seed.sql`
both verified idempotent (re-run cleanly with zero duplicate rows). No
PHP errors/warnings in the server log across any of this.

### Phase 2 notes

The cart is session-only (`$_SESSION['cart']`, no DB table) and never
trusts its own cached quantities — `cart_items()` re-reads live
price/stock on every request and silently clamps a line down (or drops
it) if stock changed since it was added, so what the customer sees in
their cart is never stale. The real stock check happens a second time,
independently, at checkout: `order_create_from_cart()` runs inside a
transaction with `SELECT ... FOR UPDATE` on every product row before
writing the order, so two customers checking out the last unit at the
same moment can't both succeed — whoever's transaction commits first
wins, the second gets a clear "no longer available" error with nothing
charged or decremented.

If Paystack's `initialize` call fails (not configured, or their API is
down) *after* the order and stock decrement already succeeded, the order
is not lost or silently abandoned — it sits as `pending`/`unpaid` under
its own `order_ref`, and `/checkout/pay/{ref}` re-attempts payment for
that same order on demand rather than forcing the customer back through
the cart. `order_mark_paid()` is idempotent (only transitions a row still
unpaid), so it's safe to call from both the webhook and the browser
return callback for the same payment without double-processing or
double-emailing — verified by replaying the same signed webhook payload
twice and confirming the second call was a no-op.

Tested end-to-end against a local MySQL/MariaDB instance: full shop →
product → add-to-cart → cart update/remove → checkout → order flow;
stock correctly decrements on order creation and clamps cart quantities
down when stock changes underneath an existing cart line; checkout
correctly rejects with no state change when requested quantity exceeds
stock; the Paystack webhook was exercised with a real HMAC-SHA512
signed payload (accepted and marks the order paid) and an invalid
signature (silently ignored, no state change); the browser return
callback and the webhook were both confirmed idempotent against the same
order; admin product create/edit/delete, category edit, and order
status updates all round-trip correctly with image upload; CSRF
rejection was confirmed on every POST form; bad product/category slugs
return a real 404. `send_mail()` failing (no local MTA in the dev
container) was confirmed to log and continue rather than break the
checkout/webhook flow it's called from — a broken mail server should
never undo a payment that already succeeded.

### Phase 3 notes

Straightforward by comparison to Phase 2 — a honeypot-gated request form
(same pattern as the Contact form) writing into `software_requests`, and
a read-only `deployed_businesses` portfolio next to it, both admin-managed.
Tested end-to-end: form submission round-trips into the DB and shows up
in the admin request list/detail with status transitions (`new` →
`quoted`, etc.); CSRF is rejected exactly like every other form here; a
business added in the admin panel shows up immediately on the public
page; a bad admin business ID returns a real 404; no PHP warnings/errors
across any of it.

### Phase 4 notes

**Updated after launch**: the catalog was originally "video games" +
"board games" per the brief's literal wording, then split into three
explicit types — PS5, VR, and board games — once the client clarified
scope with a reference design. `games.type` changed from
`ENUM('video','board')` to `ENUM('ps5','vr','board')`; since a column
can't hold a value outside its current enum, the migration in
`schema.sql` widens the enum first (union of old + new values), remaps
any existing `'video'` rows to `'ps5'`, then narrows to the final three
— verified safe to re-run against a database that already has games in
it (confirmed idempotent: a second run is a no-op). `game_type_label()`
in `app/gaming.php` renders the display label (`ucfirst()` alone can't
get "PS5" or "VR" capitalized right).

**Rebranded to SYSPOINT HUB**: the gaming catalog stays **PS5** (the
brand creatives say "PC Gaming", but the lounge runs PS5 — the website
is the source of truth here). A branch briefly renamed `games.type`
`'ps5'` to `'pc'`; the widen → remap → narrow migration in `schema.sql`
now also moves any `'pc'` rows back to `'ps5'`, so it's safe to run
against a database at any stage. The site also
picked up the brand palette (Midnight `#14143A`, Hub Indigo `#34348A`,
Signal Gold `#F7CB1E`, Lemon Flash `#FDE414`, Chrome `#E4E6E5`, Ink
`#0E0E24`) as CSS variables in `style.css`, the brand fonts (Nunito,
IBM Plex Sans, IBM Plex Mono via Google Fonts), and the logo at
`public/assets/img/logo.png` in the header, footer, admin bar and
favicon. If an admin already overrode the `gaming.hero_title` /
`gaming.hero_subtitle` content blocks with PS5 wording, edit those in
the admin panel — the stored text wins over the new defaults.

`room_bookings` is a reservation *request*, not a paid booking — a
customer's submission lands as `pending` and an admin confirms or
declines it from `/admin/bookings`, matching the brief (no payment
mentioned for room time, unlike the gadget shop). The one thing worth
enforcing at submission time regardless is double-booking: `room_booking_overlaps()`
runs the standard interval-overlap test (`start < otherEnd AND end >
otherStart`) against existing pending/confirmed bookings for that room
and date, backed by the `idx_room_bookings_date` index already in the
schema, and rejects a conflicting request with a clear error before it's
written. This doesn't fully close the race between two people submitting
overlapping requests in the same instant (both could still land as
`pending` before either is confirmed) — deliberately not solved with a
transaction/lock here, since a human reviews and confirms every booking
anyway and would simply decline the second one; that's a reasonable
trade for a request queue, unlike the gadget checkout's stock, which
*is* worth locking because there's no human in that loop.

Tested end-to-end: booking submission and its overlap rejection (a
genuinely conflicting time slot is blocked with a clear message, a
non-conflicting one on the same room/date succeeds); admin confirm/cancel
status transitions; game and room CRUD including image upload; a bad
room slug and a bad admin ID both 404; no PHP warnings/errors in the
server log.

### Phase 5 notes

The lightest phase so far — `training_courses` is a straightforward
admin-managed list (same CRUD shape as games/businesses), and the
Internship section is two `content_block()`-driven text blocks rather
than a dedicated table, since the brief just asks it to "showcase" the
concept, not manage structured internship listings.

Consulting isn't a page at all — Charles' own site is already a
consulting site, so the brief is just a link-out. That link depends on
a real URL I don't have, so it's wired through `config()['app']['consulting_url']`
(from `.env`'s `CONSULTING_URL`, empty by default) rather than
hardcoded: the nav item and the homepage's IT Consulting card only
render as links once that's set, and degrade to a plain (non-clickable)
card otherwise, so nothing 404s or links nowhere in the meantime.

Tested end-to-end: the training page renders courses and falls back
correctly when there are none; admin course CRUD with image upload;
`content_block()` defaults render correctly before any admin edit; a
bad admin course ID 404s; no PHP warnings/errors in the server log.

### Demo/preview content

`products.is_demo` and `games.is_demo` (both `TINYINT(1) DEFAULT 0`,
added via `schema.sql`) mark rows as placeholder/preview listings —
`demo_badge()` in `helpers.php` renders a small dashed "Demo" chip next
to the price wherever one of these renders (catalog grids, product
detail), so nobody mistakes a preview product for real inventory or
pricing. Real products added through the admin panel default to
`is_demo = 0` and never get the badge. The current catalog (18 products,
14 games) is placeholder content seeded directly into the local dev
database for client preview — deliberately **not** committed through
`seed.sql`, since that file is one-time real starter content per the
schema.sql/seed.sql discipline above, not a place for fake demo rows
that would need cleaning out of a real install.

**Sample laptop catalog** — `database/demo_products.sql` is an optional,
re-runnable file (INSERT IGNORE on the unique slug) that adds 11 sample
laptops/desktops to the Computers category, all `is_demo = 1`, with
images in `public/assets/img/shop/products/`. Run it only to preview
the shop; clear them later with `DELETE FROM products WHERE is_demo = 1`.
The images were cut from a reference mockup, so they're low resolution
(~100px source, upscaled) — replace with real product photos before
launch. The shop hero photo (`public/assets/img/shop/hero-laptops.*`)
came from the same mockup with its "25% OFF" badge painted out.

**Home hero** — modelled on the client's "Business Website Design"
reference: headline, a desk scene (`public/assets/img/home/hero-desk-*.*`,
graded to the brand with the keyboard glow turned gold) whose laptop
cross-fades between screenshots of our Gaming, Shop and Training heroes
(three pre-rendered images on a 15s CSS loop; static under reduced motion), five glass service cards (Gaming
featured; IT Consulting links out only when `CONSULTING_URL` is set), a
stats bar and a "Shop • Play • Learn • Build" tagline, over the brand
Swoosh. The headline is now two content blocks, `home.hero_title_prefix`
and `home.hero_title_highlight` (the old `home.hero_title` is unused). The
stats are placeholders (5 services / free Wi-Fi / opening hours) until the
client supplies real figures.

**Home sections** (below the hero, all driven by admin data): New in
the Shop (4 newest products), a dark Gaming Lounge band (room rates from
Admin → Rooms, Demo badge if flagged, three tilted game tiles), Training &
Internship (first 4 active courses + internship CTA), and Visit Us (Hub at Suite
C1, Gadget Store at Suite C20, directions, hours, email). The shop
section hides itself when there are no products.

**Training hero** — `public/assets/img/training/hero-student.*` is the
student-on-laptop photo from the client's reference poster, with the
poster's lettering painted out, colour-graded to the brand blues, and a
screenshot of our own home page warped onto the laptop screen. If the
home page changes a lot, regenerate it (or swap in a photo of a real
Syspoint student). The hero uses only the brand fonts (Nunito, IBM
Plex Sans, IBM Plex Mono). The strip under the hero lists the first six
active courses from Admin → Courses (fallback list when there are none).

Also swapped the shop/gaming hero sections' emoji-in-a-box placeholder
for actual inline SVG illustrations (a laptop + phone mockup with a
shopping-app screen for Shop; a monitor with a game HUD, a controller,
and a VR headset for Gaming) — self-hosted, no external image
dependency, matching the "no build step, nothing to fetch" approach
used everywhere else in this project.

### Homepage social proof

Carried over from the old WordPress site (staging.syspoint.com.ng): the
three headline stats, client logos and testimonials. Merged in from the
`home-social-proof` branch and fitted to the redesigned home page.

- **Stats** are three `content_blocks` (`home.stat_clients`,
  `home.stat_years`, `home.stat_employees`) edited at `/admin/home-stats`.
  Free text so "150+" works. They fill the hero's stats bar; until at
  least one is set, the bar shows placeholder facts (5 services / free
  Wi-Fi / opening hours) rather than invented numbers.
- **Client logos** are `deployed_businesses` rows (Admin → Clients) —
  one list behind the homepage "Organisations we've consulted with" strip
  (directly under the hero, so the 573-clients stat sits next to its proof)
  and the Software Clinic portfolio. There is no separate clients table.
  The team adds, renames, reorders (sort order, lowest first), hides and
  deletes organisations and uploads/removes logos there. Uploaded logos
  are always shown greyscale on the strip, and a white JPG background
  blends into it (`mix-blend-mode: multiply`). Without a logo, the name
  shows as a grey wordmark.
- **Testimonials** are an admin-managed table (`/admin/testimonials`),
  shown as a light band before Visit Us, hidden when there are none.
- **Content**: `database/social_proof.sql` holds the old site's five
  logos and two testimonials, the full list of institutions consulted for,
  and the stats (573 clients / 10 years / 38 employees — only filled in
  where still empty). Run it once on any install (fresh or live)
  after `schema.sql`. Rows are matched by name / person + quote, so it
  never duplicates or overwrites admin edits — but **don't re-run it once
  the team is managing clients in the admin**: a client deleted or renamed
  there would come back under its old name.
- Client logos ship as greyscale PNGs in `public/assets/img/clients/`
  and stay greyscale on the page by design. The two testimonial photos still
  point at the old site's media library (`media_url()` passes absolute
  URLs through): **re-upload them in the admin before the WordPress site
  goes away.** A testimonial without a photo, or one that fails to load,
  shows the person's initials.
- The old site's third logo (`images.png`) had no name, so it's added
  hidden as "Unnamed client (rename me)".

### Back office (admin) — roles and dashboards

The admin is a sidebar app (`admin_header.php` + `admin_nav.php`) on the
Midnight/swoosh backdrop with glass panels.

- **Staff & roles** (`/admin/staff`, administrators only): an
  *Administrator* sees everything; *Staff* see only the areas ticked for
  them — Sales & CRM, Store & Inventory, Gaming, Training, Website. Access
  is enforced in one place: `require_admin()` maps the URL to an area via
  `ADMIN_AREA_PATHS` in `app/admin.php` and shows a 403 page otherwise, so
  a new page only needs `require_admin()` plus one line in that map (an
  unmapped admin page is administrators-only by default). The sidebar hides
  what a person can't open. Deactivate staff rather than deleting them; a
  deactivated account is signed out on its next click. You can't change
  your own role or deactivate yourself, and the last active administrator
  can't be demoted.
- **My Account** (`/admin/account`): everyone changes their own name,
  email and password (current password required). **Change the seeded
  `admin@syspoint.example` login here before going live.**
- **Overview** (`/admin`, everyone): revenue over time by stream with a
  7-day / 30-day / 90-day / 12-month switch and change vs the previous
  period, revenue mix, KPI tiles, "needs attention" counts, upcoming room
  bookings, the Software Clinic funnel, bookings over time, a 6-month
  summary and recent messages. Each block only appears for staff whose
  areas cover it. Revenue = paid online orders + confirmed/completed room
  bookings (hours × room rate); walk-in POS sales and CRM invoices join
  when those modules land (`dashboard_revenue_streams()`).
- **Charts** are server-rendered SVG (`app/charts.php`) — no chart library
  or CDN. Hover shows a tooltip (`public/assets/js/admin.js`); every chart
  has a "View as table" or table alternative. Series colours are a fixed,
  colour-blind-checked order: Shop `#B48C00`, Gaming `#7A7AE6`,
  Software/CRM `#1E9E86`, Training `#D86A50`; a single series uses brand
  gold.

### Back office — Inventory (Store & Inventory area)

`app/inventory.php` + `admin_inventory*`, `admin_pos*`, `admin_purchase_order*`,
`admin_suppliers`/`admin_supplier_form`, `admin_serials`, `admin_equipment*`.

- **Stock ledger.** `products.stock_qty` is the live on-hand number, and
  every change to it goes through `inventory_move()` inside a transaction
  (row-locked), which also writes a `stock_movements` row: opening stock,
  received from supplier, online sale, walk-in sale, adjustment, return,
  damage, order cancelled, sale voided. The ledger always explains the
  number (Inventory → Stock Ledger, or per product). Existing stock got a
  one-off "opening" row when the schema ran. On the product form, stock is
  only entered for a *new* product; after that use **Adjust stock** (count
  / return / write-off, with a note) or a purchase order.
- **Online orders** take stock at checkout (as before) and now log it;
  cancelling an order puts the stock back (and frees any serials), and
  reopening it takes it again — or refuses if it has been sold since.
- **Suppliers & purchase orders.** Draft → Ordered → Part received →
  Received (or Cancelled). Receiving adds stock, sets the product's cost
  price to that purchase's unit cost, and — for serial-tracked products —
  requires exactly one serial/IMEI per unit received.
- **Point of Sale** (`/admin/pos`) for walk-in sales at the Gadget Store:
  search/tap products, basket, discount, cash/transfer/card/split, change
  due, optional customer (needed for warranty), printable receipt
  (`R-YYYY-NNNNN`). Serial-tracked items need the sold serials picked.
  Voiding a sale (with a reason) restocks it. Walk-in takings count as Shop
  revenue on the Overview.
- **Serials & warranty.** Tick "Track serial / IMEI numbers" and set a
  warranty (months) on a product. Units are received on purchase orders
  (or registered for stock that predates tracking), sold at the POS or
  assigned on an online order, and the warranty runs from the sale date.
  `/admin/serials` looks up any unit by serial, customer or phone; units
  can be marked returned or faulty (stock follows).
- **Hub equipment** (`/admin/equipment`, Gaming area): the Hub's own PS5s,
  controllers, VR headsets etc. with asset tags (`HUB-001`…), room,
  condition, status (in use / spare / in repair / retired), purchase cost,
  and a history log of repairs (with cost) and notes; moves and status
  changes are logged automatically.
- **Inventory dashboard** (`/admin/inventory`): stock value at cost and
  retail, potential margin, low/out-of-stock, walk-in takings, units sold
  online vs walk-in over time, top sellers, reorder list (with what's
  already on order), open purchase orders, warranties ending soon,
  equipment in repair, and the latest stock movements.

### Back office — CRM (Sales & CRM area)

`app/crm.php` + `admin_crm`, `admin_customer*`, `admin_deal*`, `admin_tasks`,
`admin_documents`/`admin_document*`, `admin_messages`/`admin_message`.

- **One customer record per person or organisation.** Checkout, room
  bookings, Software Clinic requests, contact messages and walk-in sales
  (when a name/phone/email is entered) link their row to a customer via
  `crm_link()` → `crm_customer_for()`: matched by email, else by phone
  (last 10 digits, so +234 803… = 0803…), else created. A Software Clinic
  request also creates/links the business as an organisation. Linking
  never blocks the public form (errors are logged). **Customers → Import
  past records** links everything captured before the CRM existed
  (safe to repeat).
- **Customer profile:** call / WhatsApp / email buttons, lifetime value
  (online + walk-in + invoice payments), outstanding invoices, open deals,
  bookings, and one timeline of orders, purchases, bookings, requests,
  messages, notes/calls and deal/quote/invoice/payment activity. An
  organisation's page includes its people.
- **Deals pipeline** (`/admin/deals`): Lead → Contacted → Proposal sent →
  Negotiation → Won / Lost, with value, service, owner and expected close.
  Drag cards between columns (or use the card's menu); losing a deal asks
  for a reason. Totals: open pipeline, weighted forecast (value × stage
  likelihood), won and win rate. A Software Clinic request can be turned
  into a deal in one click.
- **Tasks** — follow-ups with due date, priority and assignee, from any
  customer or deal or the Tasks page; completing one logs it on the timeline.
- **Quotes & invoices** (`Q-YYYY-NNNN`, `INV-YYYY-NNNN`): line items
  (product names suggest prices), discount, optional 7.5% VAT, notes and
  terms; printable / save-as-PDF letterhead; email to the customer;
  quote statuses (sent/accepted/declined/expired — accepting a quote wins
  its deal) and one-click convert to invoice; invoice payments (part or
  full, method, reference), overdue tracking, void. Company name, address,
  phone, email, **bank details** and default terms come from content
  blocks `company.*` (edited in Website → Settings).
- **Messages** inbox for contact-form messages (read/unread, reply,
  add to customers).
- **CRM dashboard** (`/admin/crm`): pipeline, won vs previous period, win
  rate, unpaid/overdue invoices, new customers, sales funnel by stage,
  invoice payments and new customers over time, customers by source, my
  tasks, deals closing soon, top customers and recent activity. Invoice
  payments also count as "Services" revenue on the Overview.

### Back office — Website editor (Website area)

`app/site_content.php` + `admin_website.php` (`/admin/website`).

- **Every piece of public copy is editable** — business details used
  site-wide (brand, tagline, email, phone, WhatsApp, hours, plaza address,
  both suites, Google Maps link, consulting link, social links, footer
  text), the letterhead for quotes/invoices/receipts (company details,
  **bank details**, default terms, receipt footer), and each page's
  headings, text, buttons, links and hero pictures (Home, About, Shop,
  Software Clinic, Gaming, Training, Contact). Lists (products, rooms,
  games, courses, clients, testimonials, home stats) keep their own admin
  pages, linked from the editor.
- `SITE_CONTENT` (in `site_content()`) is the single registry: key, label,
  default, type (text / textarea / html / url / email / image). Views read
  `site('key')`; images use `site_picture()` (built-in pictures keep their
  WebP twin; uploads go to `uploads/site/`). Values are stored in
  `content_blocks`; no row = the original text, so the site looks exactly
  as designed until something is edited. Each edited field shows
  "Edited", the original text, and a **Reset** button. A deliberately
  emptied field is stored as `[empty]` (e.g. clear the consulting link to
  hide "Consulting" from the menu).
- The About story is rich text (headings, bold, italic, lists, links) from
  a small built-in editor; it's sanitised on save and on output (no
  scripts, styles, event handlers or `javascript:` links).
- To add a new editable field: add it to the registry with its current
  text as the default and replace the literal in the view with
  `site('…')` — it appears in the editor automatically.

### Back office — Gaming and Training dashboards

- **Gaming** (`/admin/gaming`, Gaming area): room revenue (vs previous
  period), occupancy (booked room-hours ÷ rooms × days × opening hours,
  9am–10pm — `HUB_OPEN_HOUR`/`HUB_CLOSE_HOUR` in `admin_gaming.php`),
  sessions and average party size, bookings to confirm, revenue per room
  over time, today's timeline per room, a busiest-times heatmap (weekday ×
  hour), upcoming bookings, equipment status and regular customers.
- **Training** (`/admin/training`, Training area) is backed by a new
  student register: **Students & Interns** (`/admin/students`) —
  enquiries, enrolments and interns on a course or internship track, with
  intake dates, fee (filled from the course price), status (enquiry →
  enrolled → completed / dropped) and fee payments. Each student is a CRM
  customer (matched by phone/email). The dashboard shows active students,
  open enquiries, fees received and outstanding, the student journey,
  per-course numbers, enquiries to follow up, fees owed and upcoming
  intakes. Training fees count as **Training** revenue on the Overview
  (the fourth revenue colour), and enrolments appear on the customer
  timeline.

## Security Notes

- All DB queries use PDO prepared statements (`ATTR_EMULATE_PREPARES`
  off — positional `?` placeholders can repeat safely in one query,
  named placeholders cannot; keep that in mind in every domain file
  added from here on)
- CSRF tokens on all forms (`csrf_field()` / `verify_csrf()`)
- `.env` is gitignored; never commit real credentials
- `.htaccess` denies direct access to dotfiles
