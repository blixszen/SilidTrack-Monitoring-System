<?php
/**
 * SilidTrack - Authentication Helpers
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_admin_logged_in(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_student_logged_in(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function require_admin(): void {
    if (!is_admin_logged_in()) {
        header('Location: ' . url('/index.php'));
        exit;
    }
}

function require_student(): void {
    if (!is_student_logged_in()) {
        header('Location: ' . url('/index.php'));
        exit;
    }
}

function current_student_pk(): ?int {
    return $_SESSION['student_pk'] ?? null;
}

function current_admin_id(): ?int {
    return $_SESSION['admin_id'] ?? null;
}

function json_response($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function ph_time_now(): DateTime {
    return new DateTime('now', new DateTimeZone('Asia/Manila'));
}
