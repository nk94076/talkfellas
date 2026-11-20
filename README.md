# TalkFellas — Affiliate Blog

Fast static blog (Astro) with SEO, sitemap, RSS, schema markup, affiliate disclosure and product boxes.

## Naya post kaise likhein
`src/content/blog/` me ek `.md` file banao (example: `best-wireless-earbuds-under-2000.md` copy karo).
Product box ke liye wahi `<div class="product">` HTML block copy karke apna affiliate link daalo.

## CloudPanel par deploy (one-time)
1. CloudPanel → **Add Site → Create a Static HTML Site** → domain `talkfellas.com`, site user `talkfellas`.
2. Site → **SSL/TLS → New Let's Encrypt Certificate** (domain ka DNS A record `187.127.149.83` par hona chahiye).
3. SSH as root, Node install (agar nahi hai):
   curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt-get install -y nodejs git rsync
4. Site user ke roop me code clone karo:
   su - talkfellas
   git clone https://github.com/nk94076/talkfellas.git app
   cd app && git checkout naveen/cool-archimedes-hqwj7e && ./deploy.sh

## Update
Server par: `su - talkfellas -c "cd app && ./deploy.sh"`
