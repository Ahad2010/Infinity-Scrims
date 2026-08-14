# Infinity Scrims — User Panel Frontend

Pure HTML + CSS + Vanilla JS (same stack as the Owner panel). Sidebar is dark,
the rest of the UI is a soft light theme (dark mode also available via the
theme toggle).

## Pages
- `login.html`, `register.html`, `index.html` — auth (three-panel layout: brand
  panel, form, benefits panel) + redirect
- `dashboard.html` — stats, upcoming bookings, recent activity, explore scrims
- `browse.html` — search/filters, game chips, scrim grid, pagination
- `scrim.html` — scrim details, tabs (Slots/Details/Rules/Map Pool/Leaderboard),
  slot picker, booking summary
- `payment.html` — countdown timer, JazzCash/Easypaisa toggle, screenshot upload
- `my-bookings.html` — status tabs, booking cards with actions
- `my-teams.html` — team stats, team cards, team menu, tips
- `payments.html` — payment history table + stats
- `notifications.html` — filterable notification feed
- `profile.html` — hero banner, tabs, about me, performance, favorite games, achievements
- `settings.html` — Profile / Security / Notifications / Appearance tabs
- `help-center.html` — supporting page linked from the sidebar

## Backend wiring
`assets/js/app.js` has the same `Api` helper and `API_BASE` as the Owner panel —
point it at your live backend:
```js
const API_BASE = 'http://localhost/infinity-scrims/api';
```
Pages that fetch real data (`dashboard.html`) try the real API first and fall
back to demo data matching the reference images if the backend isn't reachable.

## Demo mode (no backend needed)
`assets/js/demo-seed.js` seeds a demo session (`Ahad Plays`) in `localStorage`
so every protected page opens directly without needing a live login — useful
for design review. As soon as a real `/auth/login.php` call succeeds, it
overwrites the demo session automatically. Remove `demo-seed.js` from the
pages once your backend is live if you don't want this fallback.

## Structure
`partials/sidebar.html`, `partials/header.html`, `partials/bottom-nav.html` are
injected by `app.js` into the `#sidebar-mount` / `#header-mount` /
`#bottomnav-mount` divs on every protected page.

Every page also gets a short, generic skeleton-loading overlay on load (see
`initPageSkeleton()` in `app.js`) for a more polished feel. `dashboard.html`
opts out of the generic overlay (`data-skeleton="off"` on `.page-content`)
since it renders its own granular skeletons per section.

A subtle animated gradient sits behind the app (`body::before` / `body::after`
in `style.css`) for a bit of premium ambience — it respects
`prefers-reduced-motion` and is dialed back further in dark mode.

## Known simplifications
- Booking/payment/team data on most pages is hardcoded to match the reference
  images exactly (visual review / demo purposes). `dashboard.html` is the only
  page currently wired to attempt a real API call first.
- "Create Team", "Edit Team", social logins (Google/Discord/Phone) etc. show a
  toast placeholder — wire these to your real endpoints when ready.
