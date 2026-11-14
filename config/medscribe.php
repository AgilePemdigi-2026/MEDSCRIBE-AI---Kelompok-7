<?php

return [
    'audio' => [
        'disk' => env('MEDSCRIBE_AUDIO_DISK', 'local'),
        'max_size_kb' => (int) env('MEDSCRIBE_AUDIO_MAX_SIZE_KB', 102400),
        'max_duration_seconds' => (int) env('MEDSCRIBE_AUDIO_MAX_DURATION_SECONDS', 3600),
        'ffprobe_path' => env('MEDSCRIBE_FFPROBE_PATH', 'ffprobe'),
        'retention_hours' => (int) env('MEDSCRIBE_AUDIO_RETENTION_HOURS', 24),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'model' => env('OPENROUTER_SOAP_MODEL', env('OPENROUTER_MODEL', 'nvidia/nemotron-3-ultra-550b-a55b:free')),
        'timeout' => (int) env('OPENROUTER_TIMEOUT_SECONDS', 120),
        'verify_ssl' => env('OPENROUTER_VERIFY_SSL', false),
        'ca_bundle' => env('MEDSCRIBE_CA_BUNDLE'),
    ],

    'soap_driver' => env('MEDSCRIBE_SOAP_DRIVER', 'mock'),

    'dummy_accounts' => [
        'enabled' => (bool) env('SEED_DUMMY_ACCOUNTS', false),
        'password' => env('DUMMY_ACCOUNT_PASSWORD'),
        'dokter_email' => env('DUMMY_DOKTER_EMAIL', 'dummy.dokter@medscribe.id'),
        'perawat_email' => env('DUMMY_PERAWAT_EMAIL', 'dummy.perawat@medscribe.id'),
    ],
];
