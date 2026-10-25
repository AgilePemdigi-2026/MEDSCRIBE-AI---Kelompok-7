<?php

namespace App\Contracts;

interface TranscriptionService
{
    /** @param array<string, string|null> $answers */
    public function transcribe(array $answers): string;
}
