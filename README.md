# Talk Fellas — Custom PHP Blog + CMS (no WordPress)

Plain PHP 8 + SQLite. Public site + admin at `/admin/`.

## Deploy on CloudPanel
1. CloudPanel → Add Site → **Create a PHP Site** → `talkfellas.com` (PHP 8.2+), user `talkfellas`.
2. Put the contents of `app/` into `/home/talkfellas/htdocs/talkfellas.com/`.
3. Open `https://talkfellas.com/admin/` and create your admin account.

Database: `/home/talkfellas/htdocs/tf-data/site.db` (outside web root). Back up that folder + `uploads/`.

## What the admin controls
- **Settings**: every homepage section (hero, category bar, trending, latest, reviews banner, explore, picks, how-to, newsletter, stats), header menu, footer columns, social, colours, logo, and SEO (titles, descriptions, OG image, Search Console/Bing verification, Google Analytics, head/footer code, robots.txt).
- **Posts**: rich editor, ranked affiliate products, featured image + alt, category, tags, schedule, hero slider / editor's pick flags, SEO title/description/focus keyword with Google preview & checks, canonical, noindex.
- **Categories**: icon, image, homepage visibility, order, SEO.
- **Pages, Subscribers (CSV), 301 Redirects, Profile**.

Automatic SEO: sitemap.xml, robots.txt, RSS feed, canonical, Open Graph/Twitter, JSON-LD (Article, Breadcrumb, ItemList, Organization, WebSite search), table of contents, 301 on URL change.

Local test: `cd app && php -S localhost:8000 router-dev.php`
