#!/usr/bin/env python3
"""Local-only transcription and diarization worker for MedScribe."""
import argparse
import json
import os
import sys
from pathlib import Path


def overlap(start, end, diar_start, diar_end):
    return max(0.0, min(end, diar_end) - max(start, diar_start))


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--input', required=True)
    parser.add_argument('--output', required=True)
    parser.add_argument('--model-size', default='small')
    parser.add_argument('--device', default='cpu')
    parser.add_argument('--compute-type', default='int8')
    parser.add_argument('--no-diarization', action='store_true')
    args = parser.parse_args()

    try:
        from faster_whisper import WhisperModel
    except ImportError as exc:
        raise RuntimeError('Dependensi faster-whisper belum terpasang. Jalankan pip install -r services/audio_processor/requirements.txt.') from exc

    print('Loading faster-whisper model', file=sys.stderr)
    model = WhisperModel(args.model_size, device=args.device, compute_type=args.compute_type)
    segments, info = model.transcribe(args.input, language='id', vad_filter=True, beam_size=5)
    transcript = []
    for index, segment in enumerate(segments):
        text = segment.text.strip()
        if text:
            transcript.append({'id': f'segment_{index}', 'startTime': float(segment.start), 'endTime': float(segment.end), 'text': text})
    if not transcript:
        raise RuntimeError('Audio tidak menghasilkan teks. Pastikan file berisi suara yang dapat didengar.')

    diarization = []
    diarization_error = None
    if not args.no_diarization:
        try:
            from pyannote.audio import Pipeline
            token = os.environ.get('HF_TOKEN') or os.environ.get('HUGGINGFACE_TOKEN')
            if not token:
                raise RuntimeError('HF_TOKEN diperlukan untuk mengunduh model diarization pyannote yang memerlukan persetujuan lisensi.')
            pipeline = Pipeline.from_pretrained('pyannote/speaker-diarization-3.1', use_auth_token=token)
            for turn, _, speaker in pipeline(args.input).itertracks(yield_label=True):
                diarization.append((float(turn.start), float(turn.end), str(speaker)))
        except Exception as exc:
            diarization_error = str(exc)
            print(f'Diarization tidak tersedia: {diarization_error}', file=sys.stderr)

    for item in transcript:
        candidates = {}
        for start, end, speaker in diarization:
            score = overlap(item['startTime'], item['endTime'], start, end)
            if score:
                candidates[speaker] = candidates.get(speaker, 0.0) + score
        if candidates:
            ordered = sorted(candidates.items(), key=lambda pair: pair[1], reverse=True)
            item['speakerId'] = ordered[0][0] if len(ordered) == 1 or ordered[0][1] > ordered[1][1] else None
            item['reviewStatus'] = 'confirmed' if item['speakerId'] else 'ambiguous'
        else:
            item['speakerId'] = None
            item['reviewStatus'] = 'ambiguous'
        item['speakerRole'] = 'unknown'

    speaker_ids = {}
    for item in transcript:
        raw_id = item.get('speakerId')
        if raw_id is not None:
            speaker_ids.setdefault(raw_id, f'speaker_{len(speaker_ids)}')
            item['speakerId'] = speaker_ids[raw_id]

    Path(args.output).write_text(json.dumps({'language': getattr(info, 'language', None), 'segments': transcript, 'diarizationError': diarization_error}, ensure_ascii=False), encoding='utf-8')


if __name__ == '__main__':
    try:
        main()
    except Exception as exc:
        print(str(exc), file=sys.stderr)
        sys.exit(1)
