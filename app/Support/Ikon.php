<?php

namespace App\Support;

/**
 * Registri ikon Lucide untuk komponen <x-icon>.
 *
 * Komponen lama (empty-state, dialog, auth-status, role-picker) masih menerima path SVG gaya heroicons;
 * namaDariPath() menerjemahkannya ke nama Lucide agar semua ikon seragam dan beranimasi.
 */
class Ikon
{
    private static ?array $data = null;

    /** Path heroicons lama => nama ikon Lucide. */
    private const PATH_LAMA = [
        'M5 13l4 4L19 7' => 'check',
        'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' => 'lock',
        'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' => 'info',
        'M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z' => 'triangle-alert',
        'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' => 'triangle-alert',
        'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' => 'circle-alert',
        'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' => 'circle-check',
        'M6 18L18 6M6 6l12 12' => 'x',
        'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' => 'trash-2',
        'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' => 'refresh-cw',
        'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z' => 'search',
        'M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206' => 'at-sign',
        'M19 9l-7 7-7-7' => 'chevron-down',
        'M9 5l7 7-7 7' => 'chevron-right',
        'M15 19l-7-7 7-7' => 'chevron-left',
        'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' => 'building-2',
        'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z' => 'mic',
        'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1' => 'log-out',
        'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' => 'user',
        'M12 4v16m8-8H4' => 'plus',
        'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' => 'shield-check',
        'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' => 'file-text',
        'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' => 'calendar',
        'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' => 'mail',
        'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z' => 'user-plus',
        'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z' => 'key-round',
        'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18' => 'eye-off',
        'M10 19l-7-7m0 0l7-7m-7 7h18' => 'arrow-left',
        'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' => 'clock',
        'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' => 'clipboard-list',
        'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' => 'users',
        'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z' => 'circle-x',
        'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' => 'circle-x',
    ];

    public static function semua(): array
    {
        return self::$data ??= require resource_path('icons/lucide.php');
    }

    public static function ada(string $nama): bool
    {
        return isset(self::semua()[$nama]);
    }

    public static function get(string $nama): ?array
    {
        return self::semua()[$nama] ?? null;
    }

    /** Nama Lucide untuk path heroicons lama, atau null bila belum dipetakan. */
    public static function namaDariPath(?string $d): ?string
    {
        return $d === null ? null : (self::PATH_LAMA[trim($d)] ?? null);
    }
}
