# Face Recognition microservice

Python Flask service used by the **photo-portal-backend** (Laravel) for face detection, compare, and search. It was recreated to match the API expected by `App\Services\FaceRecognitionService`.

## Requirements

- Python 3.10+ (3.11 or 3.12 recommended)
- `face_recognition` (dlib) – install may require CMake and a C++ compiler on Windows.

## Setup

```bash
cd face-recognition-service
python -m venv .venv
.venv\Scripts\activate   # Windows
# source .venv/bin/activate   # macOS/Linux
pip install -r requirements.txt
```

Copy `.env.example` to `.env` and set `HOST`, `PORT` if needed.

## Run

```bash
python app.py
```

By default the service listens on `http://127.0.0.1:5000`. The Laravel backend uses `config/services.php` and `FACE_RECOGNITION_URL` (default `http://127.0.0.1:5000`).

## API (expected by Laravel)

| Method | Path     | Request                    | Response |
|--------|----------|----------------------------|----------|
| GET    | /health  | -                          | `{ "status": "ok", "service": "face-recognition" }` |
| POST   | /detect  | multipart `file` (image)   | `{ "data": { "faces": [ { "x", "y", "width", "height", "confidence", "bounding_box", "embedding" } ] } }` |
| POST   | /compare | multipart `file1`, `file2` | `{ "data": { "match": bool, "similarity": number } }` |
| POST   | /search  | multipart `file` (query), `ref_0`, `ref_1`, ... (reference images) | `{ "data": { "matches": [ { "index", "score", "similarity", "bounding_box" } ] } }` |

Errors: respond with JSON `{ "message": "..." }` and status 4xx/5xx.

## Laravel .env

In the **photo-portal-backend** `.env` add (optional; defaults are shown):

```env
FACE_RECOGNITION_URL=http://127.0.0.1:5000
FACE_RECOGNITION_TIMEOUT=120
```
