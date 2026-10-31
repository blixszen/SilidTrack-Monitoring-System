<?php
require_once __DIR__ . '/../includes/auth.php';
$db = get_db();
$action = $_GET['action'] ?? '';

$VALID_STATUSES = ['Present', 'Late', 'Absent', 'Excused'];

// ---- Admin-only actions ----
if ($action === 'set_status' || $action === 'override') {
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false], 405);

    $attendanceId = (int)($_POST['attendance_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (!in_array($status, $VALID_STATUSES, true)) {
        json_response(['ok' => false, 'error' => 'Invalid status.'], 400);
    }

    $admin_id = current_admin_id();

    // Existing record: override it in place.
    if ($attendanceId > 0) {
        $stmt = $db->prepare('UPDATE attendance SET status = ?, is_override = 1, overridden_by = ? WHERE attendance_id = ?');
        $stmt->bind_param('sii', $status, $admin_id, $attendanceId);
        $stmt->execute();
        json_response(['ok' => true, 'attendance_id' => $attendanceId]);
    }

    // No record yet (e.g. All-Student view): create one for that day.
    $studentId = trim($_POST['student_id'] ?? '');
    $scanDate = trim($_POST['scan_date'] ?? '') ?: ph_time_now()->format('Y-m-d');
    if ($studentId === '') json_response(['ok' => false, 'error' => 'Missing student reference.'], 400);

    $stmt = $db->prepare('SELECT student_pk FROM students WHERE student_id = ?');
    $stmt->bind_param('s', $studentId);
    $stmt->execute();
    $pk = (int)($stmt->get_result()->fetch_assoc()['student_pk'] ?? 0);
    if (!$pk) json_response(['ok' => false, 'error' => 'Student not found.'], 404);

    $stmt = $db->prepare('SELECT attendance_id FROM attendance WHERE student_pk = ? AND scan_date = ?');
    $stmt->bind_param('is', $pk, $scanDate);
    $stmt->execute();
    $existing = (int)($stmt->get_result()->fetch_assoc()['attendance_id'] ?? 0);

    if ($existing > 0) {
        $stmt = $db->prepare('UPDATE attendance SET status = ?, is_override = 1, overridden_by = ? WHERE attendance_id = ?');
        $stmt->bind_param('sii', $status, $admin_id, $existing);
        $stmt->execute();
        json_response(['ok' => true, 'attendance_id' => $existing]);
    }

    $stmt = $db->prepare("INSERT INTO attendance (student_pk, scan_date, scan_time, status, id_detected, is_override, overridden_by)
                         VALUES (?, ?, NULL, ?, 'No', 1, ?)
                         ON DUPLICATE KEY UPDATE status = VALUES(status), is_override = 1, overridden_by = VALUES(overridden_by)");
    $adminParam = $admin_id ?? 0;
    $stmt->bind_param('issi', $pk, $scanDate, $status, $adminParam);
    $stmt->execute();

    $stmt = $db->prepare('SELECT attendance_id FROM attendance WHERE student_pk = ? AND scan_date = ?');
    $stmt->bind_param('is', $pk, $scanDate);
    $stmt->execute();
    json_response(['ok' => true, 'attendance_id' => (int)($stmt->get_result()->fetch_assoc()['attendance_id'] ?? 0)]);
}

if ($action === 'latest') {
    require_admin();
    $rows = $db->query("
        SELECT a.attendance_id, s.student_id, s.full_name, a.scan_time, a.status, a.id_detected
        FROM attendance a JOIN students s ON s.student_pk = a.student_pk
        WHERE a.scan_date = CURDATE()
        ORDER BY a.scan_time DESC LIMIT 20
    ")->fetch_all(MYSQLI_ASSOC);
    json_response(['ok' => true, 'rows' => $rows]);
}

// ---- Student-only: personal attendance history ----
if ($action === 'mine') {
    require_student();
    $pk = current_student_pk();
    $stmt = $db->prepare("
        SELECT scan_date, scan_time, status, id_detected
        FROM attendance WHERE student_pk = ? ORDER BY scan_date DESC LIMIT 60
    ");
    $stmt->bind_param('i', $pk);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    json_response(['ok' => true, 'rows' => $rows]);
}

json_response(['ok' => false, 'error' => 'Unknown action.'], 400);
