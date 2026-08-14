# Infinity Scrims — Owner Panel Frontend

Same stack as User Panel: pure HTML + CSS + Vanilla JS. Same backend, same `API_BASE`.

## Setup
1. Put the whole `infinity-scrims-owner-frontend` folder in `htdocs` (alongside the backend).
2. Check `API_BASE` in `assets/js/app.js` (same as the User panel).
3. Open: `http://localhost/infinity-scrims-owner-frontend/login.html`
4. Login is a single hardcoded owner account, checked entirely client-side (no backend
   call, no roles/register) — see `login.html` for the email/password.

## UI/UX
Reskinned to match the User panel's design system 1:1 — same CSS file, sidebar/topbar
partials, logo, fonts, colors, collapsible sidebar rail, and mobile drawer. No AI features
are wired into this frontend (the old screenshot → AI scoreboard-detection flow on
`results.html` has been replaced with a plain manual entry table).

## Pages
- `login.html` — owner-only login (role check)
- `dashboard.html` — stat cards, Scrim Status donut (pure CSS), Earnings trend (7-day bar
  chart, pure CSS), Payment Approval Queue preview, Recent Activity, Upcoming Scrims,
  Recent Scrims table
- `scrims.html` — sab created scrims, status filter, Room ID publish modal, Participants link
- `create-scrim.html` — poora 6-section form (Basic Info, Date&Time, Slots&Pricing,
  Additional Details, Media, Publish Settings) — Draft ya Publish Now
- `payment-approvals.html` — queue, approve/reject (reject reason modal), status tabs
- `participants.html` — scrim select karke uske saare bookings + payment status
- `earnings.html` — total/today/month + per-scrim revenue table
- `results.html` — manually add each team's rank/kills/points → publish (this is what
  updates the leaderboard shown in the User Panel)
- `notifications.html`, `settings.html`

## Backend additions is session mein hui
- `api/scrims/create.php` — ab advanced fields accept karta hai (team_size, slot_type,
  max_players_per_slot, platform, map_pool, visibility, access_password, multiple images)
- `api/scrims/my.php` — owner ke apne scrims (naya)
- `api/scrims/participants.php` — per-scrim participant list (naya)
- `api/dashboard.php` (owner branch) — status breakdown, earnings trend, upcoming/recent
  scrims, recent activity (extended)
- **DB migration zaroori hai** — `sql/migration_owner_panel.sql` chalayein agar pehle se
  DB bana rakha hai:
  ```
  mysql -u root -p infinity_scrims < sql/migration_owner_panel.sql
  ```
  (Fresh install ke liye `sql/schema.sql` already updated hai, migration ki zaroorat nahi)

## Known simplification
- `settings.html` abhi sirf read-only account info dikhata hai — payout accounts
  (JazzCash/Easypaisa numbers), platform fee, WhatsApp number edit karne ka UI nahi hai
  (backend `settings` table / `.env` se manually change karne parenge). Bata dein agar
  isko bhi UI se editable banana hai — chhota sa `api/settings/update.php` add ho jayega.
