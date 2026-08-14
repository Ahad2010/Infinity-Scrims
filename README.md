# Infinity Scrims — Combined (3 folders)

```
infinity-scrims/
├── backend/    → PHP + MySQL API (api, config, includes, cron, sql — untouched original structure)
├── User/       → user-facing frontend
├── Owner/      → owner dashboard frontend
└── vercel.json → routes everything onto one domain
```

With `vercel.json` as-is, on Vercel this becomes:
- `yourdomain.com/` → **User** frontend
- `yourdomain.com/Owner/` → **Owner** frontend
- `yourdomain.com/api/...` → **backend** (`backend/api/...`)

Both frontends' `assets/js/app.js` now call `API_BASE = '/api'` (relative)
instead of the old `http://localhost/...` — same domain, no CORS needed.

## ⚠️ Before you deploy

1. **PHP isn't a Vercel-native runtime.** `vercel.json` uses the community
   `vercel-php` runtime for `backend/api/**/*.php`. Works for simple
   request/response APIs like this, but it's not officially maintained by
   Vercel — treat it as "should work," test after deploying.
2. **MySQL needs an external host** (Vercel doesn't host MySQL) — e.g.
   PlanetScale, Aiven, Railway. Import `backend/sql/schema.sql` there, then
   set `DB_HOST/DB_NAME/DB_USER/DB_PASS` as Vercel env vars.
3. **File uploads won't persist** — `backend/uploads/...` is written to
   local disk in the PHP code, but serverless filesystems are read-only.
   Avatars/results/payment screenshots will vanish after upload. Fixing
   this needs the upload code moved to Vercel Blob or S3 — say the word if
   you want that done.
4. **Cron:** added `backend/api/cron-cleanup.php` (same logic as
   `backend/cron/cleanup.php`, but callable over HTTP) and wired it into
   `vercel.json`'s `"crons"` to run every 5 minutes.

If the PHP runtime gives you deploy trouble, the sturdier setup is: host
`backend/` on an actual PHP host (Railway, Render, Hostinger, a VPS), and
keep only `User/` + `Owner/` on Vercel, pointing their `API_BASE` at that
backend's real URL instead of `/api`.

## Deploy steps

1. Push this folder to GitHub, import into Vercel.
2. Vercel → Settings → Environment Variables: fill in everything from
   `backend/.env.example` (DB_*, JWT_SECRET, AI_API_KEY, RESEND_API_KEY,
   BASE_URL = your Vercel URL, etc.)
3. Run `backend/sql/schema.sql` on your external MySQL DB.
4. Deploy, then test `yourdomain.com/api/scrims/list.php` — should return JSON.
5. Register a user, then in the DB: `UPDATE users SET role='owner' WHERE email='you@example.com';`

## Note on your API key

Your uploaded backend's real `.env` had a live Groq key in it. It's **not**
included in this package (only `.env.example` with blanks) — worth rotating
that key at console.groq.com/keys since it was shared in this chat.
