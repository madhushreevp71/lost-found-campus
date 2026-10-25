# Lost & Found Campus Portal

A campus website where students report lost and found items, search, get rule-based match suggestions, and claim belongings. Core PHP 8 + MySQL, no frameworks, no external services.

## Folder structure

```
lost-found-campus/
  index.php  login.php  register.php  logout.php  dashboard.php
  report-item.php  edit-item.php  search-items.php  item-details.php
  submit-claim.php  my-reports.php  my-claims.php
  config/database.php          DB settings + PDO connection
  includes/                    header, footer, auth, functions, shared item form
  admin/                       login, dashboard, claims, items, users
  tools/create_admin.php       command-line admin creator
  assets/css/style.css  assets/js/script.js  assets/images/
  uploads/.htaccess            blocks script execution in uploads
  database.sql                 schema + demo data
```

## Database design

| Table | Key columns |
|---|---|
| `users` | id, full_name, email (unique), password_hash, role (`student`/`admin`), created_at |
| `items` | id, user_id -> users, type (`lost`/`found`), title, category, location, item_date, description, image, status (`open`/`returned`/`resolved`), created_at, updated_at |
| `claims` | id, item_id -> items, claimant_id -> users, message, status (`pending`/`approved`/`rejected`), admin_note, created_at, reviewed_at. Unique (item_id, claimant_id): one claim per person per item |

Deleting a user or item cascades to its claims.

## Setup on Windows (XAMPP + VS Code)

1. Install XAMPP (PHP 8.1 or newer). Open the **XAMPP Control Panel** and **Start** Apache and MySQL.
2. Copy the `lost-found-campus` folder into `C:\xampp\htdocs\`.
3. Open `http://localhost/phpmyadmin` -> **Import** tab -> choose `database.sql` -> **Import**. This creates the `lost_found_portal` database with tables and demo data. Re-importing resets everything.
4. Check `config/database.php`. XAMPP defaults (`root`, empty password, `localhost`) work as they are. If you set a MySQL password, change `DB_PASS`. If you rename the folder, change `BASE_URL` to match, e.g. `/my-folder-name`.
5. Open VS Code -> **File > Open Folder** -> `C:\xampp\htdocs\lost-found-campus` to edit the code.
6. Visit **http://localhost/lost-found-campus/**.

If image upload fails, confirm the `uploads` folder is writable (it normally is under XAMPP on Windows).

## Demo accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@campus.test | Admin@123 |
| Student | aarav@campus.test | Student@123 |
| Student | meera@campus.test | Student@123 |
| Student | rohan@campus.test | Student@123 |

Admin login page: `http://localhost/lost-found-campus/admin/login.php`

**Before any real use, delete the demo accounts or change their passwords.**

## Creating your own first admin (safe method)

Admins cannot be created from the website, so nobody can promote themselves. Use the command line. Open a terminal in the project folder (in VS Code: Terminal > New Terminal) and run:

```
C:\xampp\php\php.exe tools\create_admin.php
```

Enter a name, email and password. The password is hashed with `password_hash()`. If the email already exists, that account is promoted to admin. The script refuses to run from a browser, and `tools/` is blocked by `.htaccess`.

## How the Matching Assistant works

It is plain rule-based scoring, not AI or machine learning. When a student views their dashboard or their own report, open items of the opposite type (reported by other people) are compared and scored out of 100:

- **Item name (up to 50):** share of common words in the two titles (ignoring words like "the", "with").
- **Category (30):** same category.
- **Location (20):** identical location; 10 if they share a word, e.g. "Library".

Items scoring 40 or more are listed with the exact reasons. Try it: log in as `aarav@campus.test` and look at the dashboard. "Black laptop bag" matches the found "Black laptop bag with red zip" at 100/100.

## Testing the main features

1. **Register:** Create account -> try a weak password and a mismatched confirmation to see error messages -> log in.
2. **Report:** Report lost item and Report found item. Upload a JPG/PNG/WEBP under 2 MB. A `.txt` renamed to `.jpg`, or a file over 2 MB, is rejected.
3. **Match:** Report a found "Blue water bottle" at "Sports Complex" as one student; log in as `meera@campus.test` and see it on her dashboard as a match for her lost bottle.
4. **Search:** use each filter (keyword, type, category, location, date range, status) on Search items.
5. **Claim:** as Rohan, open a found item reported by someone else, submit a claim of 20+ characters. Check My claims; try Withdraw.
6. **Rules:** open your own found item (no claim form); try claiming twice (blocked); edit or delete someone else's report (blocked).
7. **My reports:** edit, mark resolved, delete your own reports.
8. **Admin:** log in at `/admin/login.php`. Check the dashboard counts, approve or reject a claim on Claims (approving marks the item returned and rejects rival claims), change status or delete a report on Reports, and view Users.
9. **Logout:** Log out in the sidebar, then try opening `dashboard.php` (you are sent to login).

## Security features

PDO prepared statements everywhere; `password_hash` / `password_verify`; session ID regenerated at login; CSRF token on every POST form; output escaped with `htmlspecialchars`; image uploads checked by real MIME type, size (2 MB) and given random filenames, with script execution disabled in `uploads/`; role checks on all admin pages; ownership checks on edit, delete and claim actions; login throttling after 5 failures; admins cannot review their own claims.

Note: `.htaccess` rules need Apache (included in XAMPP). The PHP built-in server ignores them.
