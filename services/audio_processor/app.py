#!/usr/bin/env python3
"""Local, hardware-agnostic speech-to-text API for MedScribe."""

import os
import logging
import tempfile
import time
from contextlib import asynccontextmanager
from pathlib import Path
from typing import Any

import torch
from fastapi import FastAPI, File, Form, Header, HTTPException, UploadFile
from transformers import AutoModelForSpeechSeq2Seq, AutoProcessor, pipeline


MODEL_NAME = os.getenv("MEDSCRIBE_STT_MODEL", "openai/whisper-small")
REQUESTED_DEVICE = os.getenv("MEDSCRIBE_STT_DEVICE", "auto").lower()
REQUESTED_DTYPE = os.getenv("MEDSCRIBE_STT_DTYPE", "auto").lower()
DEFAULT_LANGUAGE = os.getenv("MEDSCRIBE_STT_LANGUAGE", "indonesian")
BEAM_SIZE = int(os.getenv("MEDSCRIBE_STT_BEAM_SIZE", "5"))
MAX_BYTES = int(os.getenv("MEDSCRIBE_AUDIO_MAX_SIZE_KB", "102400")) * 1024
MODEL_CACHE_DIR = os.getenv("MEDSCRIBE_STT_CACHE_DIR") or None
API_TOKEN = os.getenv("MEDSCRIBE_STT_API_TOKEN")
DIARIZATION_ENABLED = os.getenv("MEDSCRIBE_DIARIZATION_ENABLED", "false").lower() in {"1", "true", "yes"}
HF_TOKEN = os.getenv("HF_TOKEN") or os.getenv("HUGGINGFACE_TOKEN")

stt_pipeline: Any = None
diarization_pipeline: Any = None
diarization_error: str | None = None
runtime: dict[str, str] = {}
logger = logging.getLogger("medscribe.stt")


def resolve_device() -> str:
    if REQUESTED_DEVICE == "auto":
        if torch.cuda.is_available():
            return "cuda"
        if hasattr(torch.backends, "mps") and torch.backends.mps.is_available():
            return "mps"
        return "cpu"

    if REQUESTED_DEVICE == "cuda" and not torch.cuda.is_available():
        raise RuntimeError("Device cuda diminta tetapi CUDA tidak tersedia pada runtime ini.")
    if REQUESTED_DEVICE == "mps" and (
        not hasattr(torch.backends, "mps") or not torch.backends.mps.is_available()
    ):
        raise RuntimeError("Device mps diminta tetapi backend MPS tidak tersedia pada runtime ini.")
    if REQUESTED_DEVICE not in {"cpu", "cuda", "mps"}:
        raise RuntimeError("MEDSCRIBE_STT_DEVICE harus auto, cpu, cuda, atau mps.")
    return REQUESTED_DEVICE


def resolve_dtype(device: str) -> torch.dtype:
    if REQUESTED_DTYPE == "auto":
        return torch.float16 if device == "cuda" else torch.float32
    dtypes = {"float16": torch.float16, "float32": torch.float32, "bfloat16": torch.bfloat16}
    if REQUESTED_DTYPE not in dtypes:
        raise RuntimeError("MEDSCRIBE_STT_DTYPE harus auto, float16, float32, atau bfloat16.")
    return dtypes[REQUESTED_DTYPE]


def load_model() -> None:
    global diarization_error, diarization_pipeline, stt_pipeline, runtime

    device = resolve_device()
    dtype = resolve_dtype(device)
    device_object = torch.device(device)
    model_kwargs = {
        "torch_dtype": dtype,
        "low_cpu_mem_usage": True,
        "use_safetensors": True,
    }
    if MODEL_CACHE_DIR:
        model_kwargs["cache_dir"] = MODEL_CACHE_DIR

    model = AutoModelForSpeechSeq2Seq.from_pretrained(MODEL_NAME, **model_kwargs)
    model.to(device_object)
    processor_kwargs = {"cache_dir": MODEL_CACHE_DIR} if MODEL_CACHE_DIR else {}
    processor = AutoProcessor.from_pretrained(MODEL_NAME, **processor_kwargs)
    stt_pipeline = pipeline(
        "automatic-speech-recognition",
        model=model,
        tokenizer=processor.tokenizer,
        feature_extractor=processor.feature_extractor,
        torch_dtype=dtype,
        device=device_object,
    )
    if DIARIZATION_ENABLED:
        try:
            from pyannote.audio import Pipeline

            if not HF_TOKEN:
                raise RuntimeError("HF_TOKEN diperlukan untuk diarization opsional.")
            diarization_pipeline = Pipeline.from_pretrained(
                "pyannote/speaker-diarization-3.1",
                use_auth_token=HF_TOKEN,
            )
        except Exception as exc:
            diarization_error = str(exc)[:300]
    runtime = {"model": MODEL_NAME, "device": device, "dtype": str(dtype).split(".")[-1]}


@asynccontextmanager
async def lifespan(_: FastAPI):
    load_model()
    yield


app = FastAPI(title="MedScribe Local STT", version="1.0", lifespan=lifespan)


def authorize(api_key: str | None) -> None:
    if API_TOKEN and api_key != API_TOKEN:
        raise HTTPException(status_code=401, detail="Token service STT tidak valid.")


def overlap(start: float, end: float, diar_start: float, diar_end: float) -> float:
    return max(0.0, min(end, diar_end) - max(start, diar_start))


@app.get("/health")
def health() -> dict[str, Any]:
    return {"status": "ready" if stt_pipeline else "loading", **runtime, "language": DEFAULT_LANGUAGE}


@app.post("/transcriptions")
async def transcribe(
    audio: UploadFile = File(...),
    language: str = Form(default=DEFAULT_LANGUAGE),
    x_stt_api_key: str | None = Header(default=None),
    x_stt_request_id: str | None = Header(default=None),
) -> dict[str, Any]:
    authorize(x_stt_api_key)
    if stt_pipeline is None:
        raise HTTPException(status_code=503, detail="Model STT belum siap.")
    if not audio.filename:
        raise HTTPException(status_code=422, detail="Nama file audio tidak tersedia.")

    data = await audio.read(MAX_BYTES + 1)
    if not data:
        raise HTTPException(status_code=422, detail="Audio kosong.")
    if len(data) > MAX_BYTES:
        raise HTTPException(status_code=413, detail="Ukuran audio melebihi batas konfigurasi.")

    suffix = Path(audio.filename).suffix.lower() or ".audio"
    temporary_path = None
    started_at = time.perf_counter()
    try:
        with tempfile.NamedTemporaryFile(prefix="medscribe-stt-", suffix=suffix, delete=False) as temporary:
            temporary.write(data)
            temporary_path = temporary.name

        result = stt_pipeline(
            temporary_path,
            return_timestamps=True,
            generate_kwargs={
                "language": language or DEFAULT_LANGUAGE,
                "task": "transcribe",
                "num_beams": BEAM_SIZE,
            },
            chunk_length_s=30,
            batch_size=1,
        )
        segments = []
        for index, chunk in enumerate(result.get("chunks", [])):
            start, end = chunk.get("timestamp", (None, None))
            text = (chunk.get("text") or "").strip()
            if not text:
                continue
            segments.append({
                "id": f"segment_{index}",
                "startTime": float(start or 0),
                "endTime": float(end if end is not None else start or 0),
                "text": text,
                "speakerId": None,
                "speakerRole": "unknown",
                "reviewStatus": "ambiguous",
            })
        if not segments:
            raise HTTPException(status_code=422, detail="Audio tidak menghasilkan teks.")

        logger.info(
            "STT inference completed request_id=%s bytes=%d segments=%d duration_ms=%d",
            x_stt_request_id or "unknown",
            len(data),
            len(segments),
            round((time.perf_counter() - started_at) * 1000),
        )

        if diarization_pipeline:
            turns = [
                (float(turn.start), float(turn.end), str(speaker))
                for turn, _, speaker in diarization_pipeline(temporary_path).itertracks(yield_label=True)
            ]
            speaker_names: dict[str, str] = {}
            for segment in segments:
                candidates: dict[str, float] = {}
                for start, end, speaker in turns:
                    score = overlap(segment["startTime"], segment["endTime"], start, end)
                    if score:
                        candidates[speaker] = candidates.get(speaker, 0.0) + score
                if candidates:
                    raw_speaker = max(candidates, key=candidates.get)
                    speaker_names.setdefault(raw_speaker, f"speaker_{len(speaker_names)}")
                    segment["speakerId"] = speaker_names[raw_speaker]
                    segment["reviewStatus"] = "confirmed"

        return {
            "language": language or DEFAULT_LANGUAGE,
            "segments": segments,
            "diarizationError": diarization_error,
            **runtime,
        }
    except HTTPException:
        raise
    except Exception as exc:
        raise HTTPException(status_code=422, detail=f"Inferensi STT gagal: {str(exc)[:300]}") from exc
    finally:
        if temporary_path:
            try:
                os.unlink(temporary_path)
            except OSError:
                pass


if __name__ == "__main__":
    import uvicorn

    uvicorn.run(app, host=os.getenv("MEDSCRIBE_STT_HOST", "127.0.0.1"), port=int(os.getenv("MEDSCRIBE_STT_PORT", "8765")))
