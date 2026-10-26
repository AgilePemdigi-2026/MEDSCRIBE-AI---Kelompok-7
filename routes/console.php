<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\RekamanAudio;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('medscribe:purge-audio', function () {
    $cutoff = now()->subHours(config('medscribe.audio.retention_hours'));
    $count = 0;
    RekamanAudio::where('status', 'completed')->whereNotNull('processed_at')->where('processed_at', '<', $cutoff)->each(function (RekamanAudio $recording) use (&$count) {
        if ($recording->path_audio) {
            Storage::disk(config('medscribe.audio.disk'))->delete($recording->path_audio);
        }
        $recording->update(['path_audio' => null, 'status' => 'expired']);
        $count++;
    });
    $this->info("Purged {$count} audio file(s).");
})->purpose('Delete expired local interview audio files');
