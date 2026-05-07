# Python project create data (recreated after accidental delete)

Use this to recreate the **Face Recognition** Python microservice that the Laravel photo-portal backend calls.

## 1. Project layout

```
face-recognition-service/
├── app.py              # Flask app: /health, /detect, /compare, /search
├── requirements.txt    # Flask, face_recognition, numpy, Pillow
├── .env.example        # HOST, PORT, FLASK_DEBUG
├── README.md           # Setup and API summary
└── PROJECT_CREATE_DATA.md  # This file
```

## 2. requirements.txt

```
Flask==3.0.0
Werkzeug==3.0.1
face_recognition==1.3.0
numpy==1.26.2
Pillow==10.1.0
```

## 3. Create venv and install

```bash
cd face-recognition-service
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
```

## 4. Run

```bash
python app.py
```

Service runs at `http://127.0.0.1:5000` by default.

## 5. Laravel integration

- **Config:** `config/services.php` → `face_recognition.url` (env `FACE_RECOGNITION_URL`, default `http://127.0.0.1:5000`).
- **Client:** `App\Services\FaceRecognitionService` (HTTP calls to this Python service).
- **.env (Laravel):** `FACE_RECOGNITION_URL=http://127.0.0.1:5000`, `FACE_RECOGNITION_TIMEOUT=120`.

## 6. API contract (must match Laravel)

- **GET /health** → `{ "status": "ok", "service": "face-recognition" }`
- **POST /detect** (multipart `file`) → `{ "data": { "faces": [ { "x", "y", "width", "height", "confidence", "bounding_box", "embedding" } ] } }`
- **POST /compare** (multipart `file1`, `file2`) → `{ "data": { "match": bool, "similarity": number } }`
- **POST /search** (multipart `file`, `ref_0`, `ref_1`, ...) → `{ "data": { "matches": [ { "index", "score", "similarity", "bounding_box" } ] } }`
- Errors: JSON `{ "message": "..." }` with 4xx/5xx.

All of the above is already implemented in `app.py` in this folder.
