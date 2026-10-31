<?php
/**
 * SilidTrack - Today's Attendance Stats Endpoint
 * Returns current day's attendance summary for dashboard updates.
 */
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$db = get_db();

$stats = $db->query('SELECT * FROM v_today_attendance')->fetch_assoc();
$total = (int)($stats['total_students'] ?? 0);
$present = (int)($stats['present_count'] ?? 0);
$late = (int)($stats['late_count'] ?? 0);
$absent = (int)($stats['absent_count'] ?? 0);

json_response([
    'ok' => true,
    'total' => $total,
    'present' => $present,
    'late' => $late,
    'absent' => $absent
]);