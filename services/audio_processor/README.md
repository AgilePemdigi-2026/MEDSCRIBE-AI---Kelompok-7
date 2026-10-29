# Local STT Service

This service exposes a localhost-only FastAPI endpoint for Indonesian transcription. It loads the configured Transformers/PyTorch model once during startup and never sends patient audio to a cloud inference API.

## Setup

Python 3.10-3.12 is recommended for initial deployment validation. Create an isolated environment from the repository root:

```powershell
python -m venv .venv
.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
pip install -r services/audio_processor/requirements.txt
```

Linux/macOS:

```bash
python3 -m venv .venv
. .venv/bin/activate
pip install --upgrade pip
pip install -r services/audio_processor/requirements.txt
```

Install FFmpeg and verify `ffmpeg -version` works. Windows can use `winget install Gyan.FFmpeg`; Ubuntu/Debian can use `sudo apt install ffmpeg`; macOS can use `brew install ffmpeg`.

## Configuration

The default model is `openai/whisper-small`. Large models are not selected or downloaded automatically.

```dotenv
MEDSCRIBE_STT_MODEL=openai/whisper-small
MEDSCRIBE_STT_DEVICE=auto
MEDSCRIBE_STT_DTYPE=auto
MEDSCRIBE_STT_LANGUAGE=indonesian
MEDSCRIBE_STT_BEAM_SIZE=5
MEDSCRIBE_STT_CACHE_DIR=
MEDSCRIBE_STT_HOST=127.0.0.1
MEDSCRIBE_STT_PORT=8765
MEDSCRIBE_STT_API_TOKEN=
MEDSCRIBE_AUDIO_MAX_SIZE_KB=102400
```

`auto` selects CUDA only when PyTorch reports it is usable, then Apple MPS when available, otherwise CPU. CPU is the baseline, but CPU speed and memory requirements must be benchmarked on the target hospital laptop. A GPU is optional and NVIDIA is not required by the service contract.

## Run

From the repository root:

```powershell
python services/audio_processor/app.py
```

Check readiness without exposing credentials:

```powershell
curl http://127.0.0.1:8765/health
```

Laravel sends `POST /transcriptions` as multipart field `audio`. The response contains language, model/device metadata, and timestamped transcript segments. If `MEDSCRIBE_STT_API_TOKEN` is configured, Laravel sends it as `X-STT-API-Key`.

The first start downloads the selected model into the Hugging Face cache if it is not already present. Model size, RAM, CPU/GPU support, and inference time vary by device; record these metrics before choosing a production model. Audio files are temporary inputs and are deleted by the service after inference.

Diarization is optional and is not required for the base transcript. Speaker IDs remain unknown unless an approved diarization component is configured separately. Doctors must review and assign speaker roles; the first speaker is never assumed to be the doctor.
