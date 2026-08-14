# Infinity Scrims — Combined (3 folders)

```
infinity-scrims/
├── backend/    → PHP + MySQL API — deploy this on Railway
├── User/       → user-facing frontend — deploy on Vercel (or Netlify)
├── Owner/      → owner dashboard frontend — deploy on Vercel (or Netlify)
└── vercel.json → routes User (root) + Owner (/Owner) as static sites
```

Backend and frontends go on **different platforms**: Railway runs PHP
natively (no hacks, uploads persist), Vercel/Netlify are great for static
HTML. They talk to each other over HTTPS.

## 1. Deploy the backend on Railway

1. [railway.app](https://railway.app) → **New Project** → **Deploy from GitHub repo**
   → pick `Ahad2010/Infinity-Scrims`.
2. When adding the service, set **Root Directory** to `backend`. Railway
   will find the `Dockerfile` there automatically and build it (PHP 8.2 +
   Apache, mysqli/pdo_mysql enabled).
3. **Add MySQL**: in the same Railway project, **New → Database → MySQL**.
   Railway creates it and shows connection credentials in its Variables tab.
4. Import the schema: connect to that MySQL (Railway gives you a connection
   string, or use its built-in **Data** tab / any MySQL client) and run
   `backend/sql/schema.sql` against it.
5. On the **backend service** → **Variables**, add (matching `backend/.env.example`):

   | Key | Value |
   |---|---|
   | `DB_HOST` | from the MySQL service's variables |
   | `DB_NAME` | from the MySQL service's variables |
   | `DB_USER` | from the MySQL service's variables |
   | `DB_PASS` | from the MySQL service's variables |
   | `JWT_SECRET` | random string (`openssl rand -hex 32`) |
   | `AI_API_KEY` | your **new** (rotated) Groq key |
   | `RESEND_API_KEY` | if you want email verification |
   | `APP_DEBUG` | `false` |
   | `CORS_ORIGIN` | your Vercel frontend URL, once you have it (e.g. `https://infinityscrims.vercel.app`) — needed since backend and frontend are different domains |

6. **Settings → Networking → Generate Domain** — Railway gives you a public
   URL like `https://infinity-scrims-backend-production.up.railway.app`.
   Copy it.
7. (Optional but recommended) For persistent uploads, add a **Volume**
   mounted at `/var/www/html/uploads` in the service's Settings — otherwise
   uploaded avatars/results/payment screenshots are lost on every redeploy.
8. (Optional) Cron cleanup: `backend/api/cron-cleanup.php` is an HTTP
   endpoint (same logic as `backend/cron/cleanup.php`). Either add a Railway
   **Cron Job** that curls it every 5 min, or use a free pinger like
   cron-job.org pointed at `https://YOUR-RAILWAY-URL/api/cron-cleanup.php`.

Test it: open `https://YOUR-RAILWAY-URL/api/scrims/list.php` — should return JSON.

## 2. Point the frontends at that URL

Open both:
- `User/assets/js/app.js`
- `Owner/assets/js/app.js`

and replace this line near the top:
```js
const RAILWAY_BACKEND_URL = 'PASTE_YOUR_RAILWAY_URL_HERE';
```
with your real Railway URL, e.g.:
```js
const RAILWAY_BACKEND_URL = 'https://infinity-scrims-backend-production.up.railway.app';
```
Commit and push — this line is ignored automatically when testing locally
under XAMPP/Laragon (it detects the local folder path instead).

## 3. Deploy the frontends on Vercel

1. [vercel.com](https://vercel.com) → **Add New → Project** → import the same repo.
2. Project name must be lowercase. Root Directory stays `./`.
3. Deploy — `vercel.json` routes `/` to `User/` and `/Owner` to `Owner/`.
4. Once you have the Vercel URL, go back to Railway → backend service →
   Variables → set `CORS_ORIGIN` to that URL, then redeploy the backend.

## Note on your API key

Your original `.env` had a live Groq key in it — it's not included in this
package (only `.env.example`, blank). Rotate it at console.groq.com/keys
since it was shared in chat, then use the new key in Railway's Variables.