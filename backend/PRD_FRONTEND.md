# Infinity Scrims — Frontend PRD (User Panel)

Yeh document kisi bhi AI code tool (DeepSeek, Claude, GPT, Cursor etc.) ko de kar poora
frontend accurately banwaya ja sakta hai. Backend (PHP + MySQL) already fully bana hua hai
aur is document ke API Reference section mein diya gaya hai — **frontend ko yeh APIs hi call
karni hain, naye backend ki zaroorat nahi.**

---

## 1. Project Overview

**Naam:** Infinity Scrims
**Kya hai:** Esports scrims (PUBG/BGMI/Valorant/CODM etc.) booking platform. Teams slot book
karti hain, JazzCash/Easypaisa se manual payment karti hain, owner/admin verify karta hai,
match se pehle group Room ID milti hai, match ke baad results→leaderboard publish hota hai.

**Iss PRD ka scope:** Sirf **User Panel** (Owner/Admin panel alag scope hai, is document mein nahi).

**Stack:**
- Backend: Core PHP 8 + MySQL (already built — mat chhedo)
- Frontend: Plain HTML + **ek hi CSS file** (`assets/css/style.css`) + Vanilla JS (`assets/js/app.js`)
- Koi framework nahi (no React/Vue/Bootstrap/Tailwind) — halka aur fast
- Mobile-first responsive (single CSS, media queries se)
- AJAX se saari API calls (`fetch()`), page reload minimum

---

## 2. Design System

### 2.1 Logo
Do versions available hain (`assets/img/logo-light-bg.jpeg` aur `logo-dark-bg.jpeg`) —
yeh **badge-style promo logo** hai (social media ke liye), sidebar mein iska nahi, balke
uska **line-art icon version** use hoga: ek infinity (∞) symbol jispar ek arrow guzarti hai,
sath mein "Infinity Scrims" text (serif/slab-serif font jaisa "Infinity Scrims" wordmark).

Sidebar mein: [icon] + "Infinity Scrims" text, dono light/dark mein color invert hote hain
(dark bg pe white icon+text, light bg pe black icon+text).

### 2.2 Colors (CSS variables — dono themes)

```css
:root[data-theme="light"] {
  --bg: #f7f7f8;
  --surface: #ffffff;
  --surface-alt: #fafafa;
  --border: #e5e5e7;
  --text: #111113;
  --text-muted: #6b6b70;
  --sidebar-bg: #0b0b0c;
  --sidebar-text: #ffffff;
  --sidebar-text-muted: #9a9aa0;
  --sidebar-active: #ffffff;
  --sidebar-active-text: #0b0b0c;
  --accent: #0b0b0c;         /* primary buttons = black */
  --accent-text: #ffffff;
  --success: #16a34a; --success-bg: #e8f7ee;
  --warning: #d97706; --warning-bg: #fef3e2;
  --danger:  #dc2626; --danger-bg:  #fdeaea;
  --info:    #2563eb; --info-bg:    #eaf1ff;
  --purple:  #7c3aed; --purple-bg:  #f2ebfe;
  --radius: 14px;
  --radius-sm: 8px;
  --shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}

:root[data-theme="dark"] {
  --bg: #0b0b0c;
  --surface: #ycolor-16171a;      /* card bg */
  --surface: #16171a;
  --surface-alt: #1c1d21;
  --border: #26272b;
  --text: #f5f5f6;
  --text-muted: #9a9aa0;
  --sidebar-bg: #0b0b0c;
  --sidebar-text: #ffffff;
  --sidebar-text-muted: #8b8b90;
  --sidebar-active: #ffffff;
  --sidebar-active-text: #0b0b0c;
  --accent: #ffffff;         /* primary buttons = white on dark */
  --accent-text: #0b0b0c;
  --success: #22c55e; --success-bg: rgba(34,197,94,.12);
  --warning: #f59e0b; --warning-bg: rgba(245,158,11,.12);
  --danger:  #ef4444; --danger-bg:  rgba(239,68,68,.12);
  --info:    #3b82f6; --info-bg:    rgba(59,130,246,.12);
  --purple:  #a78bfa; --purple-bg:  rgba(167,139,250,.12);
  --shadow: 0 1px 3px rgba(0,0,0,.4);
}
```

### 2.3 Typography
- Headings: bold slab/serif-ish for the wordmark only (logo); baaqi UI ke liye clean
  sans-serif: `Inter, "Segoe UI", system-ui, sans-serif`
- Page title (h1): 24-28px bold
- Card title: 15-16px semi-bold
- Body: 14px regular
- Muted/meta text: 12-13px, `--text-muted`

### 2.4 Components (sab ek hi CSS file mein banayein)
- **Sidebar** — fixed left, 260px, dark bg hamesha (light theme mein bhi sidebar black
  rehta hai — screenshots yehi dikhate hain), logo top, nav links with icons (lucide-icons
  ya simple SVG), active link = white pill bg + black text, bottom mein "Need Help?" card
  + Help Center button
- **Topbar** — hamburger (mobile sidebar toggle), notification bell with unread badge +
  dropdown, user avatar+name+role dropdown (Profile, Settings, Appearance toggle, Logout)
- **Stat cards** — icon + label + big number + small muted subtext, grid 4 across desktop,
  2 across tablet, 1 across mobile
- **Data table** — header row muted, rows with hover, status pills (colored bg+text per
  status), action buttons (icon or text), pagination footer
- **Status pill/badge** — rounded-full, small, colored per status (pending=amber,
  confirmed/success=green, rejected/failed=red, info=blue)
- **Slot grid** — CSS grid (10 columns desktop, 5 columns mobile), each slot = square-ish
  button: available (light green outline), your-selection (amber fill), booked (light red,
  disabled), locked (gray, disabled). Legend row above grid.
- **Booking summary card** — sticky sidebar-style card on desktop (right column), stacks
  below on mobile
- **Countdown timer** — big colored (green→amber→red as time drops) mm:ss, updates every
  second via JS `setInterval`
- **File upload dropzone** — dashed border box, click or drag-drop, preview thumbnail +
  filename + size + remove (X) button after selection
- **Chat bubble list** — own messages right-aligned dark bubble, others left-aligned light
  bubble with avatar+name, system messages centered small pill, timestamp under each
- **AI Support widget** — floating round button bottom-right (all pages), opens a chat
  panel (fixed bottom-right, 360px wide, 500px tall on desktop; full-screen sheet on mobile)
  with message list + input; shows "WhatsApp Support" button when AI escalates
- **Toast notifications** — top-right stack, auto-dismiss 4s, success/error/info variants
- **Modal** — center overlay, used for confirmations (cancel booking, remove member, etc.)
- **Bottom nav (mobile only, <768px)** — fixed bottom bar, 5 icons
  (Dashboard/Browse/My Bookings/Notifications/Profile), replaces sidebar; sidebar becomes
  a slide-in drawer via hamburger for extra links (My Teams, Payments, Settings)

### 2.5 Animations (subtle, premium feel)
- Page content fade+slide-up on load (150ms)
- Card hover: slight lift (`translateY(-2px)`) + shadow increase, 150ms ease
- Button press: scale(0.97) on `:active`
- Slot selection: scale bounce (100ms) on click
- Theme toggle: smooth color-transition on `html` (`transition: background-color .25s, color .25s` on key elements — NOT on everything, keeps it cheap)
- Notification bell: shake/pulse animation when new unread arrives
- Toast: slide-in from right + fade
- Skeleton loaders (shimmer gray blocks) while data fetches — use on dashboard cards, tables, slot grid

---

## 3. File Structure (frontend part only — backend already exists, don't touch)

```
infinity-scrims/
  assets/
    css/style.css        <- single stylesheet, all pages + both themes
    js/app.js             <- shared: theme toggle, toast, api() fetch wrapper, nav
    js/slots.js            <- scrim detail page: slot grid + booking + countdown
    js/chat.js              <- chat room: polling + send
    js/support-widget.js     <- floating AI widget, included on every page
    img/logo-icon.svg          <- simplified line-art logo for sidebar (light+dark aware)
  includes/
    header.php     (topbar: hamburger, bell, user dropdown)
    sidebar.php    (desktop sidebar nav)
    bottom-nav.php (mobile bottom bar)
    footer.php
    support-widget.php  (floating chat button + panel HTML, JS hooks into support/ask.php)
  login.php
  register.php
  dashboard.php
  browse.php              <- Browse Scrims (grid + filters)
  scrim.php?id=X          <- Scrim Detail (tabs: Slots / Details / Rules / Leaderboard)
  booking-payment.php?booking_id=X   <- Payment screen (method, QR, upload proof)
  my-bookings.php
  my-teams.php
  team.php?id=X           <- Single team profile / manage members
  payments.php            <- Payment/transaction history
  notifications.php
  profile.php
  chat.php?room_id=X      <- Group chat (or make it a slide-over panel instead of full page)
  leaderboard.php         <- Today / 7 Day / All-time tabs
  logout.php              <- calls api/auth/logout.php then redirects to login.php
```

Har page ka **same skeleton**:
```php
<?php require 'includes/auth-guard.php'; // redirect to login if not logged in ?>
<!DOCTYPE html>
<html data-theme="light"> <!-- JS sets this from localStorage/user pref before paint -->
<head>...css link...</head>
<body>
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/header.php'; ?>
    <div class="page-content">
      <!-- page-specific HTML, populated via JS fetch() calls to APIs below -->
    </div>
  </div>
  <?php include 'includes/bottom-nav.php'; ?>
  <?php include 'includes/support-widget.php'; ?>
  <script src="assets/js/app.js"></script>
</body>
</html>
```

---

## 4. Pages — exact content + API mapping

For har API: base URL `http://localhost/infinity-scrims/api/`. Response shape hamesha:
`{ success, message, data }`. POST requests mein header `X-CSRF-Token` ya body field
`csrf_token` bhejna hai (login response se milta hai, `app.js` mein globally store karein).

### 4.1 `login.php` / `register.php`
Simple centered card form, logo top, dark/light toggle bhi yahan available honi chahiye.
- Login → POST `api/auth/login.php` `{identity, password}`
- Register → POST `api/auth/register.php` `{username, email, password, phone}`
- Success par: response se `csrf_token` aur `user` localStorage/sessionStorage mein save
  karein, `dashboard.php` pe redirect

### 4.2 `dashboard.php` (Welcome back screen — screenshot #2)
On load: GET `api/dashboard.php`
- 5 stat cards: Upcoming Bookings, Confirmed Bookings, Pending Payments, Total Played,
  Total Spent (`data.stats`)
- "Upcoming Bookings" list (left col) — `data.upcoming`, each row clickable → `scrim.php?id=`
- "Recent Activity" list (right col) — `data.activity`
- "Explore Upcoming Scrims" card grid (4 cards) — `data.explore`, each has banner, badge
  (Open/Full), title, mode/map, date, slots-remaining, price, "View Details" button

### 4.3 `browse.php` (Browse Scrims)
- Filter bar: game dropdown, search box, status tabs — GET `api/scrims/list.php?game=&status=&search=&page=`
- Card grid, same card style as dashboard's explore section
- Pagination at bottom (`data.page`, `data.pages`)

### 4.4 `scrim.php?id=X` (Scrim Detail — screenshot #3, core page)
On load: GET `api/scrims/detail.php?id=X`
- Left: banner image, title, badges (game/mode/map), status pill, description
- Right sticky card: price/total slots/remaining, host info
- Tabs: **Slots** (default) / **Details** / **Rules** / **Leaderboard**
- **Slots tab**: legend (Available/Your Selection/Booked/Locked), grid of
  `data.slots` (numbered buttons colored by `status`), click available slot → highlight +
  show Booking Summary card (right) with team selector dropdown (from `api/teams/my.php`,
  only teams where I'm captain), "Book Slot" button
  → POST `api/bookings/create.php {scrim_id, slot_number, team_id}`
  → on success, redirect to `booking-payment.php?booking_id=<id>` (id from response `data.booking_id`)
- **Leaderboard tab**: embed same UI as `leaderboard.php` but scoped — actually simplest:
  just link to `leaderboard.php` filtered, or show top 5 for this scrim (optional, skip if
  short on time — not critical since global leaderboard.php covers it)
- Room ID box: agar `data.scrim.room_id` non-null (sirf confirmed booking walon ko milta
  hai, API already isay handle karti hai) → highlighted card showing Room ID + Password
  + copy buttons

### 4.5 `booking-payment.php?booking_id=X` (Payment screen — screenshot #4)
Booking create response se `expires_in` (seconds) milta hai — countdown timer chalayein.
1. Payment method radio (JazzCash/Easypaisa) — accounts list `api/scrims/detail.php` ke
   `payout_accounts` se ya alag call
2. Account number + copy button + QR (agar QR image nahi hai to sirf number+copy kaafi hai,
   QR skip kar sakte hain ya simple placeholder)
3. Screenshot dropzone (file input) + sender number + optional txn id
4. "I Have Paid & Uploaded Screenshot" button →
   POST `api/payments/submit.php` (multipart/form-data: booking_id, method, sender_number,
   txn_id, screenshot file)
5. Success → status card changes to "Pending Approval", redirect to `my-bookings.php` after
   short delay ya inline update

### 4.6 `my-bookings.php` (screenshot #5)
- Tabs: All / Upcoming / Pending / Confirmed / Completed / Cancelled (client-side filter ya
  `api/bookings/my.php?filter=`)
- Cards list (not table on mobile-friendly; table-like rows on desktop) each showing status
  pill, slot#, price breakdown, booked-on date, and CTA button depending on status:
  - `pending` + no payment yet → "Complete Payment" → `booking-payment.php?booking_id=`
  - `pending` + payment submitted → "View Payment" (show status + screenshot)
  - `confirmed` → "View Details" + "Cancel Booking" (POST `api/bookings/cancel.php`)
  - completed → "Match Details" (readonly)

### 4.7 `my-teams.php` (screenshot #6 — SIMPLIFIED per client: no pending-invite state)
- Stat cards: Total Teams, Active Teams, Total Players — GET `api/teams/my.php`
- "+ Create Team" button → modal: name, tag, in_game_name, in_game_id →
  POST `api/teams/create.php` → shows `join_code` in a success modal (copy button) — captain
  shares this code with friends
- "Join Team" button (add this — was missing from screenshot but needed) → modal: join_code
  input → POST `api/teams/join.php`
- Team cards: avatar-stack of members, "You"/"Leader" badge, member count, upcoming scrim
  info if any, "⋮" menu → Team Profile / Manage Players / **Remove Member** (captain only,
  POST `api/teams/remove_member.php {team_id, user_id}`) / Delete Team (optional, not built
  in backend yet — skip or add later)

### 4.8 `payments.php` (Payment history — screenshot #7)
- Stat cards: Total Spent, Successful, Pending, Failed
- Filter tabs + search
- Table: Transaction(scrim+slot), Amount, Status pill, Method, Date, Action (View/Receipt)
- Data: combine `api/bookings/my.php` + payment info, or add filter param — **note:** koi
  dedicated `payments/my-history.php` API nahi hai abhi, `bookings/my.php` se
  `payment_status` field milta hai, wahi use karein (already returns needs_payment flag).
  Agar detailed transaction list chahiye to bata dena, main ek `api/payments/my.php`
  endpoint bana dunga.

### 4.9 `notifications.php` (screenshot #8)
GET `api/notifications.php` → list with icon per `type`, title, body, time-ago, unread dot
- Filter tabs: All/Unread/Bookings/Payments/Teams/System (client-side filter by `type`/`is_read`)
- "Mark all as read" → POST `api/notifications.php` (no id = mark all)
- Click a row → POST with `id` (mark that one read) + navigate to `link` field

### 4.10 `profile.php` (screenshot #9)
- Cover + avatar, name, "Verified Player" badge (optional, cosmetic only — no backend field
  for this yet, hide or hardcode false)
- Stats row: Bookings, Tournaments(=played), Teams, Win Rate — win-rate/tournament data
  needs a stats API; for now show Bookings/Teams count from `api/dashboard.php` +
  `api/teams/my.php`; Win Rate/K-D/etc are **not in backend** (no per-player match stats
  table) — either hide these fields or treat as "coming soon". Flag this to me if needed,
  I'll add a `player_stats` table.
- Tabs: Overview (About Me info-list: username/email/phone editable via form → need a
  `profile/update.php` API — **not built yet, tell me and I'll add it**), Game Stats,
  Teams (reuse my-teams data), Achievements (not in backend — skip/hide), Activity (reuse
  notifications)
- User dropdown in topbar also has "Appearance" toggle (Light/Dark) → POST `api/auth/theme.php`

### 4.11 `chat.php?room_id=X` (not shown in screenshots but required per spec)
- GET `api/chat/rooms.php` for a sidebar list of my rooms (team + scrim groups)
- GET `api/chat/fetch.php?room_id=&after=` polled every 3s (`setInterval`) for new messages
- POST `api/chat/send.php {room_id, message}` on submit
- System messages (like Room ID reveal) render centered, differently styled

### 4.12 `leaderboard.php`
GET `api/results/leaderboard.php?range=today|week|all`
- 3 tabs: Today / 7 Day / All Time
- Table: Rank, Team (logo+name+tag), Matches, Kills, Points, Wins — highlight top 3 with
  gold/silver/bronze accents

### 4.13 AI Support Widget (floating, every page)
- Button bottom-right, opens panel
- POST `api/support/ask.php {message, ticket_id?}`
- Response `escalated:true` → show message + a prominent "Chat on WhatsApp" button linking
  to `data.whatsapp_link` (opens in new tab)
- Response `escalated:false` → show `data.answer` as AI bubble, keep chatting (reuse
  returned `ticket_id` for follow-ups in same session)

---

## 5. Key User Flows (for the AI tool building this, follow exactly)

**Flow A — Book & Pay:**
Browse → Scrim Detail → pick team → click available slot → Book Slot (slot held 15 min) →
redirected to Payment page with live countdown → pick method → upload screenshot → submit →
status = Pending → user sees it in My Bookings as "Awaiting Approval" → owner approves
(happens on owner panel, out of scope here) → user gets notification "Booking Confirmed" →
booking now shows "Confirmed", Room ID appears once owner publishes it closer to match time.

**Flow B — Team:**
Create Team (get join_code) → share code manually (WhatsApp etc, outside app) → friend
enters code in "Join Team" → becomes member instantly (no approval step) → captain can
remove any member anytime except themselves.

**Flow C — Results:**
(Owner side, not this PRD's scope, but user-facing effect:) once owner publishes results,
confirmed teams get a notification with their rank; `leaderboard.php` updates automatically.

**Flow D — Support:**
User opens widget → asks question → AI tries FAQ-based answer → if it can't resolve
(payment/refund/ban-type issues), shows WhatsApp button pre-filled with the question →
user taps → opens WhatsApp chat with support number.

---

## 6. API Quick Reference

(Full details already in `SETUP.md` at project root — read that file too.)

```
POST  api/auth/register.php     {username,email,password,phone?}
POST  api/auth/login.php        {identity,password}
GET   api/auth/logout.php
GET   api/auth/me.php
POST  api/auth/theme.php        {theme}

POST  api/teams/create.php      {name,tag?,in_game_name?,in_game_id?}
POST  api/teams/join.php        {join_code}
POST  api/teams/remove_member.php {team_id,user_id}   <- captain only
GET   api/teams/my.php

GET   api/scrims/list.php       ?game=&status=&search=&page=
GET   api/scrims/detail.php     ?id=

POST  api/bookings/create.php   {scrim_id,slot_number,team_id}
GET   api/bookings/my.php       ?filter=all|pending|confirmed|past
POST  api/bookings/cancel.php   {booking_id}

POST  api/payments/submit.php   (multipart) booking_id,method,sender_number,txn_id?,screenshot

GET   api/chat/rooms.php
GET   api/chat/fetch.php        ?room_id=&after=
POST  api/chat/send.php         {room_id,message}

GET   api/results/leaderboard.php ?range=today|week|all

POST  api/support/ask.php       {message,ticket_id?}
GET   api/support/history.php   ?ticket_id=

GET   api/notifications.php
POST  api/notifications.php     {id?}   <- omit id to mark all read

GET   api/dashboard.php
```

---

## 7. Missing pieces to flag back to me (backend gaps found while writing this PRD)

**Update:** #1 aur #2 ab ban chuke hain — `api/payments/my.php` aur
`api/profile/update.php` add ho gaye (dekhein SETUP.md). Neeche list history ke
liye rakhi hai.

1. ~~`api/payments/my.php`~~ — ✅ done
2. ~~`api/profile/update.php`~~ — ✅ done
3. Player stats (K/D, win-rate, matches played) — needs a `player_match_stats` table,
   currently not tracked
4. "Verified Player" / "Level" / "Achievements" — cosmetic gamification, no backend table yet

**Also fixed:** `sql/schema.sql`'s `scrims` table was missing 8 columns that
`api/scrims/create.php` already writes to (`images`, `team_size`, `slot_type`,
`max_players_per_slot`, `map_pool`, `platform`, `visibility`, `access_password`)
— creating a scrim would have failed with a SQL error. Schema updated to match.

Baaqi sab kuch is document mein diye APIs se ban jayega.
