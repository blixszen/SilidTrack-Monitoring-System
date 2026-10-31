<?php
/**
 * SilidTrack - Dashboard calendar data
 *
 * GET ?from=YYYY-MM-DD&to=YYYY-MM-DD   (max 62 days)
 *
 * Student session -> their own record per day:
 *   { ok, role:"student", days: { "2026-10-29": { status, scan_time } } }
 * Admin session   -> class-wide counts per day:
 *   { ok, role:"admin", total_students, days: { "2026-10-29": { present, late, absent, excused } } }
 */
require_once __DIR__ . '/../includes/auth.php';

if (!is_student_logged_in() && !is_admin_logged_in()) {
    json_response(['ok' => false, 'error' => 'Not signed in.'], 401);
}

$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';
$validDate = static fn(string $d): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;

if (!$validDate($from) || !$validDate($to) || $from > $to) {
    json_response(['ok' => false, 'error' => 'Invalid date range.'], 400);
}
if ((strtotime($to) - strtotime($from)) / 86400 > 62) {
    json_response(['ok' => false, 'error' => 'Range too large.'], 400);
}

$db = get_db();

if (is_student_logged_in()) {
    $pk = current_student_pk();
    $stmt = $db->prepare('SELECT scan_date, scan_time, status FROM attendance WHERE student_pk = ? AND scan_date BETWEEN ? AND ?');
    $stmt->bind_param('iss', $pk, $from, $to);
    $stmt->execute();
    $days = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $days[$r['scan_date']] = ['status' => $r['status'], 'scan_time' => $r['scan_time']];
    }
    json_response(['ok' => true, 'role' => 'student', 'days' => $days]);
}

$stmt = $db->prepare("
    SELECT scan_date,
           SUM(status = 'Present') AS present,
           SUM(status = 'Late')    AS late,
           SUM(status = 'Absent')  AS absent,
           SUM(status = 'Excused') AS excused
    FROM attendance
    WHERE scan_date BETWEEN ? AND ?
    GROUP BY scan_date
");
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$days = [];
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
    $days[$r['scan_date']] = [
        'present' => (int) $r['present'],
        'late'    => (int) $r['late'],
        'absent'  => (int) $r['absent'],
        'excused' => (int) $r['excused'],
    ];
}
$total = (int) ($db->query('SELECT COUNT(*) AS c FROM students WHERE is_active = 1')->fetch_assoc()['c'] ?? 0);

json_response(['ok' => true, 'role' => 'admin', 'total_students' => $total, 'days' => $days]);
