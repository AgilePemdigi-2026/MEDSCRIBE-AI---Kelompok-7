FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    MEDSCRIBE_STT_HOST=0.0.0.0 \
    MEDSCRIBE_STT_PORT=8765 \
    MEDSCRIBE_STT_DEVICE=cpu \
    MEDSCRIBE_STT_DTYPE=float32 \
    MEDSCRIBE_STT_CACHE_DIR=/cache/huggingface \
    HF_HOME=/cache/huggingface

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends ffmpeg \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /cache/huggingface

COPY services/audio_processor/requirements.txt ./requirements.txt
RUN pip install --no-cache-dir --upgrade pip \
    && pip install --no-cache-dir -r requirements.txt

COPY services/audio_processor/app.py ./app.py

RUN useradd --create-home --uid 10001 appuser \
    && chown -R appuser:appuser /app /cache
USER appuser

HEALTHCHECK --interval=15s --timeout=10s --start-period=300s --retries=20 \
    CMD python -c "import json,urllib.request; response=json.load(urllib.request.urlopen('http://127.0.0.1:8765/health', timeout=5)); raise SystemExit(0 if response.get('status') == 'ready' else 1)"

EXPOSE 8765

CMD ["uvicorn", "app:app", "--host", "0.0.0.0", "--port", "8765"]
