<?php
/**
 * SilidTrack - Inline SVG icon library
 *
 * Usage: echo icon('users', 'icon stat-svg');
 * Every icon is a 24x24 stroked SVG so it inherits color and stays crisp.
 */
function icon(string $name, string $class = 'icon', int $size = 24, float $stroke = 2): string
{
    static $paths = [
        // Brand
        'logo' => '<path d="M12 2 3 7v10l9 5 9-5V7l-9-5Z" fill="currentColor" fill-opacity="0.15"/><path d="M12 2 3 7v10l9 5 9-5V7l-9-5Z"/><path d="M8.5 10.5 12 14l3.5-3.5"/><path d="M12 14v4.5"/>',
        'shield' => '<path d="M12 2 3 7v10l9 5 9-5V7l-9-5Z"/>',
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/>',

        // Navigation / chrome
        'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.14.5.6.9 1.51 1H21a2 2 0 1 1 0 4h-.09c-.91.1-1.37.5-1.51 1Z"/>',
        'bell' => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/>',
        'arrow-left' => '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'close' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 9 5-5 5 5"/><path d="M12 4v12"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 11 5 5 5-5"/><path d="M12 16V4"/>',

        // Stat card icons
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'user-check' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'user-x' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 8 5 5"/><path d="m22 8-5 5"/>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',

        // Dashboard overview card icons (call with $stroke = 0 for the filled one)
        'dash-users' => '<circle cx="12" cy="7.6" r="3.6"/><path d="M5.4 20.4c0-3.6 2.9-6 6.6-6s6.6 2.4 6.6 6v.6H5.4Z"/><circle cx="4.4" cy="9" r="2.4"/><path d="M.4 17.6c0-2.3 1.7-4 4-4 .8 0 1.5.2 2.1.5-1.3 1-2.1 2.5-2.3 4.3-.2.1-.4.1-.6.1H.4Z"/><circle cx="19.6" cy="9" r="2.4"/><path d="M23.6 17.6c0-2.3-1.7-4-4-4-.8 0-1.5.2-2.1.5 1.3 1 2.1 2.5 2.3 4.3.2.1.4.1.6.1h3.2Z"/>',
        'dash-late' => '<circle cx="12" cy="12" r="9.6" stroke-width="2.6" stroke-dasharray="0.1 3.35"/><path d="M12 2.4a9.6 9.6 0 0 1 9.6 9.6" stroke-width="2.6"/><path d="M12 7v5h3.8" stroke-width="2.4"/>',
        'dash-present' => '<path d="M21 8.9a9.6 9.6 0 1 1-4.6-5" stroke-width="2.4"/><path d="m7.9 12.4 3.1 3.1 5.7-6.1" stroke-width="2.4"/>',
        'dash-absent' => '<circle cx="12" cy="5" r="3.2" stroke-width="2.4"/><path d="M3.6 21.2a8.4 8.4 0 0 1 16.8 0" stroke-width="2.4"/><path d="m9.9 15.6 4.2 4.2" stroke-width="2.4"/><path d="m14.1 15.6-4.2 4.2" stroke-width="2.4"/>',

        // Actions
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'trash' => '<path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M10 11v6"/><path d="M14 11v6"/>',
        'key' => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.5 12.5 8-8"/><path d="m17 6 2 2"/><path d="m14.5 8.5 2 2"/>',
        'lock' => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'mail' => '<rect x="2.5" y="4.5" width="19" height="15" rx="2.5"/><path d="m3 7 9 6 9-6"/>',
        'wifi' => '<path d="M5 12.5a10 10 0 0 1 14 0"/><path d="M8.5 16a5.5 5.5 0 0 1 7 0"/><path d="M12 19.5h.01"/><path d="M2 9a15 15 0 0 1 20 0"/>',

        // Content
        'megaphone' => '<path d="m3 11 15-6v14L3 13v-2Z"/><path d="M3 11H2.5A1.5 1.5 0 0 0 1 12.5v0A1.5 1.5 0 0 0 2.5 14H3"/><path d="M7 13.5V19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-4.2"/><path d="M21 9a3 3 0 0 1 0 6"/>',
        'wallet' => '<path d="M20 7V6a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h15a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6"/><path d="M17 13h.01"/>',
        'bank' => '<path d="M3 10h18"/><path d="M12 3 2.5 8h19L12 3Z"/><path d="M5 10v8"/><path d="M10 10v8"/><path d="M14 10v8"/><path d="M19 10v8"/><path d="M3 18h18"/>',
        'chart' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M7 15v3"/><path d="M12 9v9"/><path d="M17 5v13"/>',
        'calendar' => '<rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18"/><path d="M8 3v3"/><path d="M16 3v3"/>',
        'clipboard' => '<rect x="5" y="4.5" width="14" height="17" rx="2.5"/><path d="M9 4.5a3 3 0 0 1 6 0"/><path d="M9 11h6"/><path d="M9 15h4"/>',
        'qr' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><path d="M14 14h3v3h-3z"/><path d="M20 14v3"/><path d="M14 20h3"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m4.9 19.1 1.4-1.4"/><path d="m17.7 6.3 1.4-1.4"/>',
        'id-card' => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><circle cx="8.5" cy="11" r="2.2"/><path d="M5 16c.6-1.6 1.9-2.4 3.5-2.4S11.4 14.4 12 16"/><path d="M15 10h4"/><path d="M15 13.5h4"/>',

        // Redesign additions (nav + dashboards)
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
        'attendance' => '<circle cx="9" cy="7.5" r="3.2"/><path d="M3 20v-1.2A4.8 4.8 0 0 1 7.8 14h2.4a4.8 4.8 0 0 1 4.8 4.8V20"/><path d="M16.5 4.6a3.2 3.2 0 0 1 0 5.8"/><path d="M18.5 14.3A4.8 4.8 0 0 1 21 18.8V20"/>',
        'receipt' => '<path d="M5 3.5h14a1 1 0 0 1 1 1V21l-3-1.8-2.5 1.8-2.5-1.8-2.5 1.8L7 19.2 4 21V4.5a1 1 0 0 1 1-1Z" transform="translate(0 -.5)"/><path d="M8.5 9h7"/><path d="M8.5 12.5h4"/>',
        'report' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5"/><path d="M9 17v-3"/><path d="M12 17v-5"/><path d="M15 17v-2"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-left' => '<path d="m15 18-6-6 6-6"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'scan' => '<path d="M4 8V6a2 2 0 0 1 2-2h2"/><path d="M16 4h2a2 2 0 0 1 2 2v2"/><path d="M20 16v2a2 2 0 0 1-2 2h-2"/><path d="M8 20H6a2 2 0 0 1-2-2v-2"/><path d="M8 12h8"/><path d="M12 8v8"/>',
        'graduation' => '<path d="m2 9.5 10-5 10 5-10 5-10-5Z"/><path d="M6 11.8V16c0 1.4 2.7 3 6 3s6-1.6 6-3v-4.2"/><path d="M22 9.5V15"/>',
        'shield-check' => '<path d="M12 3 4.5 6v5.5c0 4.4 3.1 8.2 7.5 9.5 4.4-1.3 7.5-5.1 7.5-9.5V6L12 3Z"/><path d="m9 12 2.2 2.2L15.5 10"/>',
        'user-slash' => '<circle cx="12" cy="7.5" r="3.5"/><path d="M5 20.5a7 7 0 0 1 11-5.7"/><circle cx="18" cy="18" r="3.6"/><path d="m15.6 20.4 4.8-4.8"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9.5"/><path d="m8 12.3 2.8 2.8L16.2 9.4"/>',
        'x-circle' => '<circle cx="12" cy="12" r="9.5"/><path d="m9 9 6 6"/><path d="m15 9-6 6"/>',
        'info-circle' => '<circle cx="12" cy="12" r="9.5"/><path d="M12 11v5"/><path d="M12 7.6h.01"/>',
        'file-empty' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5"/><path d="M9 13h6"/><path d="M9 17h4"/>',
    ];

    $body = $paths[$name] ?? $paths['shield'];
    $style = $stroke <= 0
        ? 'fill="currentColor" stroke="none"'
        : 'fill="none" stroke="currentColor" stroke-width="' . rtrim(rtrim(number_format($stroke, 2, '.', ''), '0'), '.') . '" stroke-linecap="round" stroke-linejoin="round"';

    return '<svg class="' . htmlspecialchars($class) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" '
        . $style . ' aria-hidden="true" focusable="false">' . $body . '</svg>';
}

/**
 * Initials for the avatar fallback: first letter of first + last name.
 */
function initials_of(string $name): string
{
    $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (!$parts) return 'S';
    $first = mb_substr($parts[0], 0, 1);
    $last  = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';
    return mb_strtoupper($first . $last);
}
