<?php

return [
    'audio' => [
        'disk' => env('MEDSCRIBE_AUDIO_DISK', 'local'),
        'max_size_kb' => (int) env('MEDSCRIBE_AUDIO_MAX_SIZE_KB', 102400),
        'max_duration_seconds' => (int) env('MEDSCRIBE_AUDIO_MAX_DURATION_SECONDS', 3600),
        'retention_hours' => (int) env('MEDSCRIBE_AUDIO_RETENTION_HOURS', 24),
        'python' => env('MEDSCRIBE_PYTHON_BIN', 'python'),
        'worker' => env('MEDSCRIBE_AUDIO_WORKER', base_path('services/audio_processor/process_audio.py')),
        'model_size' => env('MEDSCRIBE_WHISPER_MODEL', 'small'),
        'device' => env('MEDSCRIBE_WHISPER_DEVICE', 'cpu'),
        'compute_type' => env('MEDSCRIBE_WHISPER_COMPUTE_TYPE', 'int8'),
        'diarization_enabled' => (bool) env('MEDSCRIBE_DIARIZATION_ENABLED', true),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'model' => env('OPENROUTER_MODEL', 'nvidia/nemotron-3-ultra-550b-a55b:free'),
        'timeout' => (int) env('OPENROUTER_TIMEOUT_SECONDS', 120),
    ],

    'soap_driver' => env('MEDSCRIBE_SOAP_DRIVER', 'mock'),
];
