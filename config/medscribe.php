<?php

return [
    'audio' => [
        'disk' => env('MEDSCRIBE_AUDIO_DISK', 'local'),
        'max_size_kb' => (int) env('MEDSCRIBE_AUDIO_MAX_SIZE_KB', 102400),
        'max_duration_seconds' => (int) env('MEDSCRIBE_AUDIO_MAX_DURATION_SECONDS', 3600),
        'ffprobe_path' => env('MEDSCRIBE_FFPROBE_PATH', 'ffprobe'),
        'retention_hours' => (int) env('MEDSCRIBE_AUDIO_RETENTION_HOURS', 24),
        'diarization_enabled' => (bool) env('MEDSCRIBE_DIARIZATION_ENABLED', false),
    ],

    'stt' => [
        'url' => env('MEDSCRIBE_STT_URL', 'http://127.0.0.1:8765'),
        'token' => env('MEDSCRIBE_STT_API_TOKEN'),
        'model' => env('MEDSCRIBE_STT_MODEL', 'openai/whisper-small'),
        'device' => env('MEDSCRIBE_STT_DEVICE', 'auto'),
        'dtype' => env('MEDSCRIBE_STT_DTYPE', 'auto'),
        'language' => env('MEDSCRIBE_STT_LANGUAGE', 'indonesian'),
        'beam_size' => (int) env('MEDSCRIBE_STT_BEAM_SIZE', 5),
        'timeout' => (int) env('MEDSCRIBE_STT_TIMEOUT_SECONDS', 7200),
        'queue' => env('MEDSCRIBE_STT_QUEUE', 'stt'),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'model' => env('OPENROUTER_MODEL', 'nvidia/nemotron-3-ultra-550b-a55b:free'),
        'timeout' => (int) env('OPENROUTER_TIMEOUT_SECONDS', 120),
        'verify_ssl' => env('OPENROUTER_VERIFY_SSL', true),
        'ca_bundle' => env('MEDSCRIBE_CA_BUNDLE'),
    ],

    'soap_driver' => env('MEDSCRIBE_SOAP_DRIVER', 'mock'),
];
