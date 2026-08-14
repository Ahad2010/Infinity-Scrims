<?php
/**
 * Har 5 minute chalayein (cPanel cron / Task Scheduler):
 *   php /path/to/infinity-scrims/cron/cleanup.php
 */
require_once dirname(__DIR__) . '/includes/functions.php';

// 1. Expire ho chuke slot holds free karo (jinhon ne payment nahi bheji)
$freed = DB::run("UPDATE slots SET status='available', team_id=NULL, held_until=NULL
                  WHERE status='held' AND held_until < NOW()")->rowCount();

// 2. Un bookings ko cancel karo jinka slot free ho gaya aur payment bhi nahi hai
DB::run("UPDATE bookings b
         JOIN slots sl ON sl.id = b.slot_id
         SET b.status='cancelled'
         WHERE b.status='pending' AND sl.status='available'
           AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.booking_id=b.id AND p.status='pending')");

// 3. Scrims jinka match time guzar gaya → live/completed
DB::run("UPDATE scrims SET status='live' WHERE status IN ('open','full') AND match_at <= NOW()");

// 4. Full/open status sync
DB::run("UPDATE scrims s SET s.status='open'
         WHERE s.status='full'
           AND (SELECT COUNT(*) FROM slots WHERE scrim_id=s.id AND status='available') > 0");

echo "Cleanup done. Freed slots: $freed\n";
