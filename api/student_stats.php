<?php
/**
 * SilidTrack - Student Personal Attendance Stats Endpoint
 * Returns the current student's attendance summary for the CURRENT MONTH
 * (the dashboard cards are labelled "This month").
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/dashboard_helpers.php';
require_student();

$db = get_db();
$pk = current_student_pk();

$stmt = $db->prepare("
    SELECT
      SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present_count,
      SUM(CASE WHEN status='Late' THEN 1 ELSE 0 END) AS late_count,
      SUM(CASE WHEN status='Absent' THEN 1 ELSE 0 END) AS absent_count,
      SUM(CASE WHEN status='Excused' THEN 1 ELSE 0 END) AS excused_count
    FROM attendance WHERE student_pk = ? AND scan_date BETWEEN ? AND ?
");
[$monthStart, $monthEnd] = current_month_range();
$stmt->bind_param('iss', $pk, $monthStart, $monthEnd);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$present = (int)($summary['present_count'] ?? 0);
$late = (int)($summary['late_count'] ?? 0);
$absent = (int)($summary['absent_count'] ?? 0);
$excused = (int)($summary['excused_count'] ?? 0);
$total = $present + $late + $absent + $excused;

json_response([
    'ok' => true,
    'total' => $total,
    'present' => $present,
    'late' => $late,
    'absent' => $absent,
    'excused' => $excused
]);