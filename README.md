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
  admin.php                 Session-based admin auth
  content_blocks.php         Lightweight in-house CMS for editable copy
  views/
    partials/            header.php, nav.php, footer.php,
                           admin_header.php, admin_footer.php
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
- [ ] **Phase 2 — Gadget sales**: Computers/Accessories catalog, cart,
      Paystack checkout, stock tracking, order management.
- [ ] **Phase 3 — Software Clinic**: request-a-build form, portfolio of
      businesses served.
- [ ] **Phase 4 — Gaming**: video/board game lists, VIP + common room
      showcase and booking/reservation system.
- [ ] **Phase 5 — Training & Internship**: course showcase, internship
      program page; Consulting section links out to The Icon's site.
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

## Security Notes

- All DB queries use PDO prepared statements (`ATTR_EMULATE_PREPARES`
  off — positional `?` placeholders can repeat safely in one query,
  named placeholders cannot; keep that in mind in every domain file
  added from here on)
- CSRF tokens on all forms (`csrf_field()` / `verify_csrf()`)
- `.env` is gitignored; never commit real credentials
- `.htaccess` denies direct access to dotfiles
