# TalkFellas — Custom Top-10 Affiliate Blog + CMS

Custom PHP site (no WordPress). Public site + admin panel at `/admin/`. Data in SQLite outside the web root.

## Deploy on CloudPanel
1. CloudPanel → **Add Site → Create a PHP Site** → domain `talkfellas.com`, PHP 8.2+, site user `talkfellas`.
2. Upload everything inside `app/` to `/home/talkfellas/htdocs/talkfellas.com/`.
3. Open `https://talkfellas.com/admin/` → create your admin account (first visit only).
4. Add posts: title, category, featured image, ranked products (name, image, score, badge, features, affiliate link).

Database is created automatically at `/home/talkfellas/htdocs/tf-data/site.db` — back this up plus `uploads/`.

Local test: `cd app && php -S localhost:8000 router-dev.php`
