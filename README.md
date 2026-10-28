# EventPro: Online Event Management System

A database-driven event management web app built with **PHP 8, MySQL (PDO), sessions, and vanilla JavaScript**. No frameworks, no CMS: everything is hand-written.

> Built for *Internet Programming 622*.

## Features

- Registration and login with optional profile picture
- Password reset flow (demo mode shows the reset link on screen, so no mail server is needed)
- Event dashboard with create/edit/delete, ordered soonest-first with past events last
- Live search for people and events (results update as you type)
- Read-only user profiles and private messaging between users

## Security

| Threat | Protection |

| SQL injection - Every query uses PDO prepared statements (emulated prepares off) |
| XSS - All output escaped via `e()` (`htmlspecialchars`); live search uses `textContent`, never `innerHTML` |
| CSRF - Random token on every form, verified with `hash_equals()` |
| Weak auth - `password_hash()` / `password_verify()`, identical error for unknown email and wrong password, 60-second lock-out after 5 failed attempts |
| Password reset - 256-bit random token, only its SHA-256 hash stored, 30-minute expiry, single use |
| Bad input - Validated in JavaScript (convenience) **and** PHP (real protection) |
| Malicious uploads - 2 MB limit, `finfo` MIME check, `getimagesize()`, random file names, `.htaccess` blocks PHP execution in `uploads/` |
| Broken access control - `require_login()` on private pages; events editable only by their owner (`WHERE id = ? AND user_id = ?`); messages always use the session user id |
| Session hijacking - HttpOnly + SameSite cookies, ID regenerated on login, 30-minute idle timeout |

## Requirements

| Software & Version |
| PHP - 8.0+ with `pdo_mysql`, `mbstring`, `fileinfo` (all on by default in XAMPP/WAMP) |
| MySQL / MariaDB - 5.7+ / 10.3+ |
| Web server - Apache (XAMPP / WAMP / MAMP) |

## Installation (XAMPP)

1. Copy the `eventpro` folder into `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open <http://localhost/phpmyadmin> → **Import** → choose `database.sql` → **Go**. This creates the `eventpro` database and its four tables.
4. Check the database settings in `includes/config.php` (XAMPP defaults: user `root`, empty password).
5. Browse to <http://localhost/eventpro/> and **register your own account**.

> `uploads/avatars/` must be writable by the web server. On Windows/XAMPP this works automatically; on Linux run `chmod 775 uploads/avatars`.

## Project structure

```
eventpro/
├── index.php                 redirects to login or dashboard
├── register.php  login.php  logout.php
├── forgot_password.php  reset_password.php
├── dashboard.php
├── event_create.php  event_edit.php  event_delete.php
├── profile.php  user_view.php
├── search.php  search_live.php     live search JSON endpoint
├── messages.php  conversation.php
├── database.sql              schema (and any seed data)
├── includes/
│   ├── config.php            settings (database, limits)
│   ├── db.php                PDO connection
│   ├── auth.php              sessions, login helpers, access control
│   ├── functions.php         escaping, CSRF, validation, uploads, search
│   └── header.php  footer.php  event_form.php
├── css/style.css
├── js/app.js
└── uploads/avatars/          profile pictures (PHP execution blocked)
```

## Database

- `users`: id, full_name, email (unique), password_hash, profile_picture, created_at
- `events`: id, user_id → users, title, description, location, category, event_date, created_at, updated_at
- `messages`: id, sender_id → users, receiver_id → users, body, is_read, created_at
- `password_resets`: id, user_id → users, token_hash (unique), expires_at

## Troubleshooting

| Problem - The Solution |
|
| "Database connection failed" -  Start MySQL, then check `includes/config.php` |
| Picture won't upload - Make `uploads/avatars/` writable; use a JPG/PNG/WEBP under 2 MB |
| Times are a few hours off - Change `date_default_timezone_set` in `includes/config.php` |
| Blank white page - Check the Apache/PHP error log (errors are logged, not displayed) |

## 📄 Licence

Student project, shared for learning and portfolio purposes.
