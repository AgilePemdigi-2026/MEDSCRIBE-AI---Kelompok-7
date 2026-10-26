# Local Audio Worker

Install Python 3.10+ and FFmpeg, then run:

```bash
python -m venv .venv
.venv\Scripts\activate
pip install -r services/audio_processor/requirements.txt
```

The worker uses `faster-whisper` locally. Diarization uses `pyannote/speaker-diarization-3.1` locally and requires a Hugging Face account, an access token in `HF_TOKEN`, and acceptance of the model terms. The token is used only to download/access the model; audio is not sent to Hugging Face inference.
