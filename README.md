# Membership App — Services Officers Mess Jhelum

Plain PHP app for member sign-up and two-sided PDF membership card generation,
with admin-managed categories (each with its own color and back-of-card layout).

## Stack
- PHP 7.3+ (works on 8.x too)
- MySQL
- TCPDF (PDF + QR code generation)

## Setup

1. **Install dependencies**
   ```bash
   composer install
   ```

2. **Create the database**
   ```bash
   mysql -u root -p < sql/schema.sql
   ```
   This seeds two categories to match your reference design: **Civilian** (white,
   family-details back) and **Serving Officer** (green, facilities/discounts back).

3. **Configure DB credentials** (environment variables, or edit `config/database.php` for local dev)
   ```
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=membership_app
   DB_USER=root
   DB_PASS=yourpassword
   ADMIN_PASSWORD=choose-a-real-password
   ```

4. **Run locally**
   ```bash
   php -S localhost:8000 -t public
   ```
   Visit `http://localhost:8000` for the sign-up form, or
   `http://localhost:8000/../admin/login.php` (i.e. serve the whole project root,
   not just `public/`, if you want `/admin` reachable — see note below).

## Card design

Cards are landscape, standard credit-card size (85.6 × 54mm), generated as a
**2-page PDF**: page 1 is the front, page 2 is the back.

- **Front**: QR code (encodes the member code), org monogram, photo, and
  Name / Parents-Spouse / CNIC / Membership No / Category rows, colored per category.
- **Back**: depends on the member's category —
  - `family` layout → "Details of Family Members" table
  - `facilities` layout → Instructions + a text-based facilities/discounts list

**Note on icons & logos**: the icons on the front are simple colored-circle
monograms (N, P, C, M, ST) rather than pixel-exact copies of the line icons in
your reference image — easy to restyle in `includes/CardRenderer.php` if you want
to swap in an icon font or SVGs later. The restaurant/partner logos (KFC, Subway,
etc.) from your reference image are trademarked and aren't reproduced — the
facilities list is text-only (name + discount). If your mess has permission to
use specific partner logos, add a `logo_path` per facility and render it as an
image in `renderCardBack()`.

## Admin panel

`/admin` (password-protected via `ADMIN_PASSWORD`):
- **Categories** — add/remove categories, set color and back-of-card layout
- **Instructions** — manage the bullet list shown on `facilities`-layout cards
- **Facilities** — manage the facility/discount text list
- Dashboard also lists recent members with a direct card-download link

⚠️ The current admin auth is a single shared password for simplicity. Before
going to production, consider per-user accounts and rate-limiting the login form.

## Project structure
```
membership-app/
├── admin/                   # category/instruction/facility management
├── config/
│   ├── database.php         # PDO connection
│   └── app.php               # org name/settings
├── includes/
│   ├── Member.php            # validation, creation, lookup, photo upload
│   ├── Category.php           # category/instruction/facility CRUD helpers
│   └── CardRenderer.php       # front/back PDF drawing logic
├── public/                  # web root
│   ├── index.php              # sign-up form
│   ├── submit.php              # form handler
│   ├── success.php             # confirmation + download link
│   └── card.php                 # streams the 2-page PDF card
├── uploads/photos/           # member photo uploads
└── sql/schema.sql
```

## Next steps / ideas
- Per-user admin accounts instead of a shared password
- Editable org name/logo image from the admin panel
- A public "verify member" page that looks up a member by scanned QR code
- Auto-expire memberships past `valid_upto` (a daily cron updating `status`)
