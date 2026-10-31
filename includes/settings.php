<?php
/**
 * SilidTrack - Lightweight key/value settings store.
 *
 * Used as the fallback store for values that older installations may not have
 * columns for (e.g. PayMongo API keys). The table is created on demand so
 * existing installations keep working without a manual migration.
 */
function ensure_settings_table(mysqli $db): void
{
    static $ready = false;
    if ($ready) return;
    $ready = true;
    $db->query("CREATE TABLE IF NOT EXISTS app_settings (
        setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
        setting_value TEXT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function get_setting(mysqli $db, string $key, string $default = ''): string
{
    try {
        ensure_settings_table($db);
        $stmt = $db->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ?');
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row && $row['setting_value'] !== null ? (string)$row['setting_value'] : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function set_setting(mysqli $db, string $key, string $value): void
{
    ensure_settings_table($db);
    $stmt = $db->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)
                          ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $stmt->bind_param('ss', $key, $value);
    $stmt->execute();
}

function column_exists(mysqli $db, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) return $cache[$key];

    $found = false;
    try {
        $stmt = $db->prepare('SELECT COUNT(*) AS n FROM information_schema.COLUMNS
                              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        $found = ((int)($stmt->get_result()->fetch_assoc()['n'] ?? 0)) > 0;
    } catch (Throwable $e) {
        $found = false;
    }

    return $cache[$key] = $found;
}

/**
 * Admin Maya bank account details. Prefers the admins.* columns, then falls
 * back to the settings store for installations without them.
 */
function maya_account(mysqli $db, ?int $adminId = null): array
{
    $name = $number = $qr = '';

    if ($adminId === null) {
        $row = $db->query('SELECT MIN(admin_id) AS id FROM admins')->fetch_assoc() ?: [];
        $adminId = (int)($row['id'] ?? 0) ?: null;
    }

    if ($adminId && column_exists($db, 'admins', 'maya_account_name')) {
        $cols = ['maya_account_name', 'maya_account_number'];
        if (column_exists($db, 'admins', 'maya_qr_path')) $cols[] = 'maya_qr_path';

        $select = implode(', ', array_map(fn($c) => "$c AS " . substr($c, 5), $cols));
        $stmt = $db->prepare("SELECT $select FROM admins WHERE admin_id = ?");
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $name = trim((string)($row['account_name'] ?? ''));
        $number = trim((string)($row['account_number'] ?? ''));
        $qr = trim((string)($row['qr_path'] ?? ''));
    }

    return [
        'name'   => $name   ?: get_setting($db, 'maya_account_name'),
        'number' => $number ?: get_setting($db, 'maya_account_number'),
        'qr'     => $qr     ?: get_setting($db, 'maya_qr_path'),
    ];
}

function save_maya_account(mysqli $db, int $adminId, string $name, string $number, ?string $qrPath = null): void
{
    if (column_exists($db, 'admins', 'maya_account_name')) {
        $hasQr = $qrPath !== null && column_exists($db, 'admins', 'maya_qr_path');
        $sql = 'UPDATE admins SET maya_account_name = ?, maya_account_number = ?'
            . ($hasQr ? ', maya_qr_path = ?' : '')
            . ' WHERE admin_id = ?';
        $stmt = $db->prepare($sql);
        if ($hasQr) {
            $stmt->bind_param('sssi', $name, $number, $qrPath, $adminId);
        } else {
            $stmt->bind_param('ssi', $name, $number, $adminId);
        }
        $stmt->execute();
    }

    set_setting($db, 'maya_account_name', $name);
    set_setting($db, 'maya_account_number', $number);
    if ($qrPath !== null) set_setting($db, 'maya_qr_path', $qrPath);
}

/**
 * PayMongo keys. Reads the admins.* columns when present, otherwise the
 * settings store, so both old and new schemas work.
 */
function paymongo_keys(mysqli $db): array
{
    $secret = $public = '';

    if (column_exists($db, 'admins', 'paymongo_secret_key')) {
        $row = $db->query('SELECT paymongo_secret_key, paymongo_public_key FROM admins LIMIT 1')->fetch_assoc() ?: [];
        $secret = trim((string)($row['paymongo_secret_key'] ?? ''));
        $public = trim((string)($row['paymongo_public_key'] ?? ''));
    }

    $secret = $secret ?: get_setting($db, 'paymongo_secret_key');
    $public = $public ?: get_setting($db, 'paymongo_public_key');

    // Fall back to environment variables (useful for local development).
    // Supports .env files loaded by the bootstrap loader in config/app.php
    // or system-level environment variables set in Apache / shell.
    if ($secret === '') {
        $envSecret = getenv('PAYMONGO_SECRET_KEY');
        if (is_string($envSecret) && $envSecret !== '') {
            $secret = $envSecret;
        }
    }
    if ($public === '') {
        $envPublic = getenv('PAYMONGO_PUBLIC_KEY');
        if (is_string($envPublic) && $envPublic !== '') {
            $public = $envPublic;
        }
    }

    return [
        'secret' => $secret,
        'public' => $public,
    ];
}

function save_paymongo_keys(mysqli $db, int $adminId, string $secret, string $public): void
{
    if (column_exists($db, 'admins', 'paymongo_secret_key')) {
        $stmt = $db->prepare('UPDATE admins SET paymongo_secret_key = ?, paymongo_public_key = ? WHERE admin_id = ?');
        $stmt->bind_param('ssi', $secret, $public, $adminId);
        $stmt->execute();
    }

    set_setting($db, 'paymongo_secret_key', $secret);
    set_setting($db, 'paymongo_public_key', $public);
}
