# SilidTrack — Smart Classroom & Attendance System

A full PHP/MySQL web app (matching your UI spec + mockups) with an ESP32 + RFID
hardware scanner for automatic attendance logging, plus a Maya bank account
payment flow and a PayMongo checkout option for classroom fees.

## Design system
- **Typography:** Gotham Rounded (Medium for headers/buttons, Book for body).
  Since Gotham Rounded is a licensed font, `assets/css/style.css` loads
  **Nunito** from Google Fonts as the metric-similar stand-in behind the
  `'Gotham Rounded', 'Nunito'` stack — drop the licensed webfont into
  `assets/fonts/` and add an `@font-face` to use the real thing.
- **Canvas:** `#F4F6F9` with a faint isometric vector grid (inline SVG pattern)
  and white cards (`16–24px` radius, diffused shadows).
- **Primary:** Royal Blue `#2563EB` / `#3B82F6`.
- **Status badges:** Present `#D1FAE5`/`#059669`, Late `#FEF3C7`/`#D97706`,
  Absent `#FEE2E2`/`#DC2626`, Excused `#E0E7FF`/`#4F46E5`.
- **Icons:** every emoji was replaced by inline SVG served from
  `includes/icons.php` (`icon('users')`, `icon('clock')`, …).

## Stack
- **Frontend:** HTML5, CSS3 (custom design system in `assets/css/style.css`), vanilla JS/AJAX
- **Backend:** PHP (procedural, mysqli, prepared statements throughout)
- **Database:** MySQL (schema in `sql/schema.sql`)
- **Hardware:** ESP32 + RC522 RFID reader + buzzer + LCD/OLED (`esp32/main.py`, MicroPython)

## 1. Set up the database
1. Start Apache + MySQL in XAMPP.
2. Open phpMyAdmin → Import → select `sql/schema.sql`. This creates the
   `silidtrack` database, all tables, and two dashboard/report views.
3. Upgrading an older database? The migration statements at the bottom of
   `sql/schema.sql` (add the `Excused` status, create `app_settings`) are
   commented out there — run them once in phpMyAdmin.

## 2. Configure the app
Edit `config/db.php` if your MySQL credentials differ from the XAMPP defaults
(`root` / no password).

## 3. Create your real admin login
The schema ships a placeholder admin row. Set a real password immediately:

```bash
php reset_admin_password.php admin@silidtrack.edu YourStrongPassword123
```

(Or visit `reset_admin_password.php?email=...&password=...` in the browser once,
then **delete the file**.)

## 4. Deploy
Copy the whole `silidtrack/` folder into `C:\xampp\htdocs\silidtrack` (or your
web root) and visit `http://localhost/silidtrack/`.

The app auto-detects its own base path (`config/app.php`), so it works
correctly whether you deploy it as a subfolder (`http://localhost/silidtrack/`)
or as the web root itself (`http://localhost/`) — no manual URL editing
required. Just make sure the whole folder structure stays together (don't
flatten `admin/`, `student/`, `api/`, etc. into different locations).

## 5. Folder guide
```
silidtrack/
├── index.php                 Login (role toggle: Student / Admin)
├── admin/                    Admin-only pages (session-guarded)
│   ├── dashboard.php         Greeting, stat cards, PH clock, month calendar, announcements
│   ├── attendance.php        Student Management: all-student roster / scan history,
│   │                         clickable status badges (Present/Late/Absent/Excused)
│   ├── fees.php              Fee publishing + Admin Maya Bank Account (name, number, QR)
│   └── report.php            At-risk students + unpaid balances
├── student/                  Student-only pages (session-guarded)
│   ├── dashboard.php
│   ├── attendance.php
│   ├── fees.php              Unpaid fee ticket → Pay with PayMongo
│   ├── fees_checkout.php     PayMongo checkout redirect
│   ├── fees_receipt.php      Digital receipt (printable)
│   └── report.php            Personal attendance + payment history
├── api/                      JSON endpoints called by the JS/AJAX layer
│   ├── login.php / logout.php
│   ├── students.php          Create student, reset password
│   ├── attendance.php        Set/override status (incl. Excused), personal history
│   ├── attendance_scan.php   ★ Hardware endpoint hit by the ESP32
│   ├── fees.php              Create/update/delete fee, save Maya + PayMongo settings
│   ├── announcements.php
│   ├── payments.php          Creates PayMongo checkout session + verifies payment
│   ├── change_password.php
│   └── update_photo.php
├── includes/
│   ├── auth.php               Session guards, JSON helper
│   ├── icons.php              Inline SVG icon library (no emoji anywhere)
│   ├── settings.php           Key/value store + Maya & PayMongo accessors
│   └── navbar.php             Shared floating nav, role-aware
├── assets/css/style.css       Full design system (colors, cards, badges, modals)
├── assets/js/admin.js         Admin AJAX + live clock/calendar
├── assets/js/student.js       Student AJAX + live clock/calendar
├── esp32/main.py              MicroPython firmware for the RFID scanner
└── sql/schema.sql             Full DB schema + dashboard views
```

## 6. How a card tap becomes an attendance row
1. Student taps their card on the RC522 reader.
2. ESP32 (`esp32/main.py`) reads the UID and `POST`s JSON to
   `api/attendance_scan.php` with a shared `device_key`.
3. The endpoint looks up the student by `rfid_uid`, decides **Present** vs
   **Late** against a configurable cutoff (`09:00:00` by default), and
   upserts one row per student per day into `attendance`.
4. It replies with `{ ok, status, student_name, beep }`; the ESP32 beeps once
   for success (twice for an error) and prints the result to the LCD/OLED.
5. The web dashboard polls `api/attendance.php?action=latest` every few
   seconds — swap this for a WebSocket push if you want true real-time
   updates without polling.

## 7. Configure PayMongo (required for fee payments)
The PayMongo integration needs a **Secret Key** to create checkout sessions and
verify payments on the server. Without it, clicking "Proceed to PayMongo
Checkout" shows the error: *"PayMongo not configured. Please contact admin."*

There are **two ways** to provide the keys:

### Option A — Admin UI (recommended, persists across restarts)
1. Sign up at [dashboard.paymongo.com](https://dashboard.paymongo.com) and copy
   your **Test Mode** keys (Secret starts with `sk_test_…`, Public with `pk_test_…`).
2. Log in to SilidTrack as admin → **Fees & Payments** page.
3. Scroll to the **"PayMongo API Keys"** card, paste both keys, and click
   *Save PayMongo Settings*.

### Option B — Environment variables (local development only)
1. Copy `.env.example` to `.env` in the project root:
   ```bash
   copy .env.example .env
   ```
2. Fill in your PayMongo test keys in the `.env` file.
3. The bootstrap loader in `config/app.php` reads `.env` automatically and
   makes the values available via `getenv()`. Database keys (Option A) take
   precedence if both are set.

> **Note:** For production, always use Option A or set real environment
> variables in your web server / process manager. Never commit real API keys
> to version control — add `.env` to your `.gitignore`.

## 8. Security notes before going live
- Change `DEVICE_KEY` in both `api/attendance_scan.php` and `esp32/main.py`.
- Delete `reset_admin_password.php` after first use.
- Serve over HTTPS once deployed off `localhost`.
- Configure PayMongo webhook endpoint for production payment verification.

## 9. Dashboard redesign (student + admin)
- `student/dashboard.php`, `admin/dashboard.php` — new layout (hero, 4 tinted stat cards, announcement card, week attendance calendar).
- `assets/css/dashboard.css` — dashboard-only styles (loaded after `style.css`). The nav bar restyle lives in `style.css`.
- `assets/js/dashboard.js` — calendar, "View All" announcements modal, live date.
- `api/calendar.php` — day-by-day attendance for the calendar (own record for students, class counts for admin).
- `includes/dashboard_helpers.php` — greeting, year label (guessed from the section digit, e.g. `BSIT-2A` → 2nd Year).
- Student cards now show **this month** (as in the design); admin cards show **today**.
- Card arrows link to `attendance.php?status=Late|Present|Absent`.
