<?php
/**
 * SilidTrack - Helpers shared by the student + admin dashboards.
 */

/** "Good Morning" / "Good Afternoon" / "Good Evening" (Philippine time; JS keeps it in sync with the viewer's clock). */
function dash_greeting(): string
{
    $hour = (int) ph_time_now()->format('G');
    return $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
}

/**
 * "1st Year", "2nd Year"… guessed from the first digit in a section code such as
 * "BSIT-2A" or "2-B". Returns null when the section has no digit, so the
 * dashboard simply hides the year instead of showing something wrong.
 */
function year_label_from_section(?string $section): ?string
{
    if ($section === null || !preg_match('/([1-9])/', $section, $m)) {
        return null;
    }
    $n = (int) $m[1];
    $suffix = [1 => 'st', 2 => 'nd', 3 => 'rd'][$n] ?? 'th';
    return $n . $suffix . ' Year';
}

/** First and last day (Y-m-d) of the current Philippine-time month. */
function current_month_range(): array
{
    $now = ph_time_now();
    return [$now->format('Y-m-01'), $now->format('Y-m-t')];
}

/** Long date used in the dashboard header, e.g. "Wednesday, October 29, 2026". */
function dash_today_label(): string
{
    return ph_time_now()->format('l, F j, Y');
}
