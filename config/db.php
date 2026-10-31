<?php
/**
 * SilidTrack - Database Connection
 * Update these constants to match your XAMPP / MySQL setup.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'silidtrack');
define('DB_USER', 'root');
define('DB_PASS', '');

function get_db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}
