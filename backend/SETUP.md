# Infinity Scrims — Backend Setup

Core PHP + MySQL. Koi framework, koi composer nahi.

## 1. Install

1. XAMPP / Laragon install karein (PHP 8.0+, MySQL 8.0+)
2. Poora `infinity-scrims` folder `htdocs` mein rakhein
3. phpMyAdmin → Import → `sql/schema.sql` chalayein

   Ya terminal se:
   ```bash
   mysql -u root -p < sql/schema.sql
   ```

4. `config/config.php` mein DB credentials check karein (XAMPP default: root / khali password)
5. Browser: `http://localhost/infinity-scrims/api/scrims/list.php` — JSON aana chahiye

## 2. Pehla Owner banayein

Register API se normal user banayein, phir phpMyAdmin mein:
```sql
UPDATE users SET role = 'owner' WHERE email = 'aapka@email.com';
```

## 3. AI Support enable karna (Groq)

`.env` mein (ya seedha `config/config.php` mein):
```
AI_API_KEY=gsk_xxxxx           # console.groq.com/keys se
AI_MODEL=llama-3.3-70b-versatile              # support chat
AI_VISION_MODEL=meta-llama/llama-4-scout-17b-16e-instruct   # result screenshot reading
WHATSAPP_NUMBER=923001234567   # aapka support number
```
Key na ho to widget khud-ba-khud WhatsApp fallback pe chala jayega — kuch break nahi hoga.

## 4. Cron (slot hold cleanup)

Har 5 minute:
```
*/5 * * * * php /path/to/infinity-scrims/cron/cleanup.php
```
Windows pe Task Scheduler. Cron na bhi ho to har API request pe halka cleanup chal jata hai.

---

# API Reference

Sab responses is shape mein:
```json
{ "success": true, "message": "...", "data": { } }
```

POST requests mein `csrf_token` bhejna zaroori hai (`/api/auth/me.php` se milta hai).

## Auth
| Endpoint | Method | Body |
|---|---|---|
| `api/auth/register.php` | POST | username, email, password, phone? — creates account (unverified), emails a 6-digit code, does **not** log in yet |
| `api/auth/verify-email.php` | POST | email, otp — verifies the code and logs the user in |
| `api/auth/resend-verification.php` | POST | email — resends a fresh code (30s cooldown) |
| `api/auth/login.php` | POST | identity (email/username), password — returns `{requires_verification:true, email}` with HTTP 403 if the account hasn't verified its email yet |
| `api/auth/logout.php` | GET | — |
| `api/auth/me.php` | GET | — (user + csrf_token + unread) |
| `api/auth/theme.php` | POST | theme = light\|dark |

Needs `RESEND_API_KEY` in `.env` (console: resend.com/api-keys) — without it, accounts still get created and a code is generated, but the email won't send (`email_sent:false` in the response), so surface a "resend" option in the UI regardless.

## Teams
| Endpoint | Method | Body |
|---|---|---|
| `api/teams/create.php` | POST | name, tag?, in_game_name?, in_game_id?, phone?, whatsapp?, discord_id?, logo? (file) |
| `api/teams/update.php` | POST | team_id, name?, tag?, phone?, whatsapp?, discord_id?, logo? (file) — captain only |
| `api/teams/join.php` | POST | join_code |
| `api/teams/my.php` | GET | — |

> Team banane wala hi **captain** hai — sirf wahi slot book aur pay kar sakta hai.

## Scrims
| Endpoint | Method | Body |
|---|---|---|
| `api/scrims/list.php` | GET | game?, status?, search?, page? |
| `api/scrims/detail.php` | GET | id — scrim + poora slot grid |
| `api/scrims/create.php` | POST | **owner** — title, game_id, match_at, total_slots, price_per_slot, banner? |
| `api/scrims/publish_room.php` | POST | **owner** — scrim_id, room_id, room_password |

Slot status: `available` / `booked` / `locked`. (`held` user ko `booked` dikhta hai.)

## Booking flow
1. `api/bookings/create.php` — POST scrim_id, slot_number, team_id
   → slot **15 min hold** ho jata hai, `expires_in` seconds return hota hai (frontend countdown)
2. `api/payments/submit.php` — POST booking_id, method, sender_number, txn_id?, screenshot (file)
3. Owner `api/payments/review.php` — POST payment_id, action=approve\|reject, reason?
   → approve = slot `booked` + booking `confirmed` + team ko notification
   → reject = slot wapas `available`

Baaqi: `api/bookings/my.php` (filter: all/pending/confirmed/past), `api/bookings/cancel.php`

**Race condition safe:** booking `SELECT ... FOR UPDATE` row lock use karta hai — do banday ek hi slot kabhi nahi le sakte.

## Payments (Owner)
| Endpoint | Method |
|---|---|
| `api/payments/pending.php?status=pending` | GET — approval queue + stats |
| `api/payments/review.php` | POST — approve/reject |
| `api/payments/earnings.php` | GET — total / today / month / per-scrim |
| `api/payments/my.php` | GET — logged-in user's own transaction history + stats (total/successful/pending/rejected) |

## Profile
| Endpoint | Method | Body |
|---|---|---|
| `api/profile/update.php` | POST | username?, phone?, avatar? (file), current_password?+new_password? — sirf jo fields bhejein wahi update honge |

## Group Chat
| Endpoint | Method | Body |
|---|---|---|
| `api/chat/rooms.php` | GET | — (team rooms + confirmed scrim rooms) |
| `api/chat/fetch.php` | GET | room_id, after (last message id — polling) |
| `api/chat/send.php` | POST | room_id, message |

Har team ka apna room + har scrim ka apna room auto ban jata hai. Room ID publish hone pe system message khud aa jata hai.

## Results → Leaderboard
1. `api/results/upload.php` — POST **owner** — scrim_id, screenshot (file)
   → AI screenshot padh ke har team ka position + kills nikalta hai, teams ko slot number/naam se match karta hai, points calculate karta hai
2. `api/results/update_entry.php` — POST entry_id, team_id?, position?, kills? — AI ki ghalti theek karne ke liye
3. `api/results/add_entry.php` — POST result_id, position, team_id?, kills? — AI se miss hui team manually add karne ke liye
4. `api/results/publish.php` — POST result_id (force=1 agar koi team unmatched ho)
   → publish hote hi sab teams ko notification + leaderboard update

`api/results/leaderboard.php?range=today|week|all` — rank, points, kills, matches, wins

Points formula `settings` table se aata hai:
- `kill_point` = 1
- `placement_points` = `{"1":10,"2":6,"3":5,...}`

## Support (AI + WhatsApp fallback)
`api/support/ask.php` — POST message, ticket_id?

AI FAQ knowledge base se jawab deta hai. Agar jawab na de sake (payment/refund/ban jaise issues) to:
```json
{ "escalated": true, "whatsapp_link": "https://wa.me/92300...?text=..." }
```
Frontend widget seedha WhatsApp button dikha dega. FAQs `faqs` table mein add/edit karein.

## Baqi
- `api/dashboard.php` — user ya owner ke hisaab se stats + upcoming + activity
- `api/notifications.php` — GET list, POST (id? = ek, ya sab read)

---

## Security checklist (ho chuka hai)
- Saare queries prepared statements (PDO) — SQL injection safe
- `password_hash()` / `password_verify()`
- CSRF token har POST pe
- Uploads folder mein PHP execution band (`.htaccess`)
- Upload MIME + size validation (5MB, sirf jpg/png/webp)
- `config/`, `includes/`, `cron/` browser se blocked
- Room ID sirf confirmed booking wale ko dikhti hai
- Chat access control (room ka member hi padh/likh sakta hai)

Production pe jaate waqt `config/config.php` mein `DEBUG` ko `false` karein.
