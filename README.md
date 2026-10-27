# EventPro – Online Event Management System

Internet Programming 622 · PHP + MySQL (PDO) · HTML5 · CSS3 · JavaScript
No CSS frameworks, no CMS – everything is hand-written.

---

## 1. Requirements

| Software | Version |
|----------|---------|
| PHP      | 8.0 or newer (with the `pdo_mysql`, `mbstring` and `fileinfo` extensions – all on by default in XAMPP/WAMP) |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Web server | Apache (XAMPP / WAMP / MAMP) |

## 2. Installation (XAMPP)

1. Copy the **`eventpro`** folder into `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open **http://localhost/phpmyadmin** → **Import** → choose `database.sql` → **Go**.
   This creates the `eventpro` database, four tables and two demo accounts.
4. Open `includes/config.php` and check the database details
   (XAMPP default: user `root`, empty password – already set).
5. Browse to **http://localhost/eventpro/**

> The `uploads/avatars/` folder must be writable by the web server
> (on Windows/XAMPP this works automatically; on Linux run `chmod 775 uploads/avatars`).


| Name           | Email                |Password
|----------------|----------------------|-------------
| Tilaya Reddy   |reddytilaya@gmail.com |tilaya22345
| Thashen Naidoo |thash27@gmail.com     |thash2627

You can also register your own accounts.

---

## 3. How to use the system

| Task | Where |
|------|-------|
| Register (name, email, password, optional picture) | **Register** button |
| Log in / log out | **Login** / **Logout** buttons |
| Forgot password | **Login → Forgot Password**. Demo mode: the reset link appears on-screen (no mail server needed) |
| See upcoming events, and manage your own | **Dashboard** |
| Create / edit / delete an event | **Create Event**, then **Edit** / **Delete** on the dashboard |
| Change your name or picture | Click your name in the top bar |
| Find people and events | **Search** (results update as you type) |
| View another user (read-only) | Search → **View** |
| Send a private message | Search → View → **Message**, or open one from **Messages** |

---

## 4. Requirement checklist

| # | Requirement | Where it is implemented |
|---|-------------|-------------------------|
| 1 | Registration & authentication | `register.php`, `login.php`, `logout.php`, `forgot_password.php`, `reset_password.php`, `includes/auth.php` |
| 1b | Unique email | Checked in PHP **and** a `UNIQUE` index in `database.sql` |
| 1c | `password_hash()` | `register.php`, `reset_password.php` (verified with `password_verify()` in `login.php`) |
| 1f | `$_SESSION` handling | `includes/auth.php` (secure cookie flags, `session_regenerate_id()`, 30-minute idle timeout) |
| 2 | Dashboard & events (CRUD) | `dashboard.php`, `event_create.php`, `event_edit.php`, `event_delete.php` |
| 2c/d | Timestamps & ordering | `created_at`/`updated_at` columns; events ordered soonest-first (`ORDER BY event_date ASC`), past events last |
| 3 | Profile & search | `profile.php`, `search.php`, `search_live.php`, `user_view.php` (view-only) |
| 4 | Private messaging | `messages.php`, `conversation.php`, separate `messages` table |
| 5 | Security | See section 5 below |
| 6 | Front-end interactivity | `js/app.js`: validation, image preview, live search, confirm dialogs |
| 7 | Code quality | Folders `includes/ css/ js/ uploads/`, commented code, `database.sql`, this README |

## 5. Security measures

* **SQL injection** – every query uses PDO prepared statements (`ATTR_EMULATE_PREPARES` off).
* **XSS** – all output goes through `e()` (`htmlspecialchars`); the live-search JavaScript uses `textContent`, never `innerHTML`.
* **CSRF** – every form carries a random token checked with `hash_equals()`.
* **Passwords** – hashed with `password_hash()`; login uses `password_verify()`, the same error for "unknown email" and "wrong password", and a 60-second lock-out after 5 failed attempts.
* **Password reset** – random 256-bit token, only its SHA-256 hash is stored, expires in 30 minutes, single use.
* **Input validation** – checked in JavaScript (convenience) **and** again in PHP (real protection): lengths, email format, password strength, category whitelist, date format.
* **Secure file upload** – size limit (2 MB), real MIME type check with `finfo`, `getimagesize()` proves it is an image, random file name, `.htaccess` in `uploads/` stops PHP execution.
* **Access control** – `require_login()` on every private page; events can only be edited/deleted by their owner (`WHERE id = ? AND user_id = ?`); messages always use the user id from `$_SESSION`, so nobody can read or send as someone else.
* **Sessions** – HttpOnly + SameSite cookies, session id regenerated on login, destroyed on logout.

## 6. Folder structure

```
eventpro/
├── index.php               redirects to login or dashboard
├── register.php  login.php  logout.php
├── forgot_password.php  reset_password.php
├── dashboard.php
├── event_create.php  event_edit.php  event_delete.php
├── profile.php  user_view.php
├── search.php  search_live.php      (live search JSON endpoint)
├── messages.php  conversation.php
├── database.sql            schema + demo data
├── README.md
├── includes/
│   ├── config.php          settings (database, limits)
│   ├── db.php              PDO connection
│   ├── auth.php            sessions, login helpers, access control
│   ├── functions.php       escaping, CSRF, validation, uploads, search
│   ├── header.php  footer.php  event_form.php
├── css/style.css
├── js/app.js
└── uploads/avatars/        profile pictures (PHP execution blocked)
```

## 7. Database tables

* `users` – id, full_name, email (unique), password_hash, profile_picture, created_at
* `events` – id, user_id → users, title, description, location, category, event_date, created_at, updated_at
* `messages` – id, sender_id → users, receiver_id → users, body, is_read, created_at
* `password_resets` – id, user_id → users, token_hash (unique), expires_at

## 8. Troubleshooting

| Problem | Fix |
|---------|-----|
| "Database connection failed" | Start MySQL, then check the values in `includes/config.php` |
| Picture will not upload | Make sure `uploads/avatars/` is writable and the file is a JPG/PNG/WEBP under 2 MB |
| Times are a few hours out | Change the timezone in `includes/config.php` (`date_default_timezone_set`) |
| Blank white page | Check the Apache/PHP error log – errors are logged, not displayed |
