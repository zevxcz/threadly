# Threadly — PHP + MySQL social app

A Threads-inspired starter app (not affiliated with Meta). Features: sign up/login, public feed, following feed, 500-character posts, likes, replies, follow/unfollow, user search, profile editing, post deletion, dark responsive interface.

## Requirements
- PHP 8.1+ with PDO MySQL and mbstring extensions
- MySQL 8+ or compatible MariaDB
- Apache, Nginx, or PHP's built-in development server

## Quick setup
1. Create/import database: `mysql -u root -p < schema.sql`
2. Edit `config.php` for your MySQL credentials, or set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` environment variables.
3. From this folder run `php -S localhost:8000`.
4. Open `http://localhost:8000/auth.php?mode=register` and create your first account.

For XAMPP, copy the folder into `htdocs`, start Apache and MySQL, import `schema.sql` in phpMyAdmin, update `config.php`, then open `http://localhost/threadly/`.

## Security and production notes
- Prepared SQL queries, hashed passwords, HTML escaping, CSRF protection, and session ID rotation are included.
- Before public deployment: enable HTTPS, configure a dedicated least-privilege DB user, add rate limits/anti-spam and email verification, secure session settings, moderation/reporting, pagination, and robust server logging.
- This is a working MVP rather than an exact Threads clone. No image uploads, notifications, or DMs yet.
