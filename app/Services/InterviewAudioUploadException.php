<?php

namespace App\Services;

use RuntimeException;

class InterviewAudioUploadException extends RuntimeException
{
    public function __construct(string $message, public readonly int $statusCode = 409)
    {
        parent::__construct($message);
    }
}
