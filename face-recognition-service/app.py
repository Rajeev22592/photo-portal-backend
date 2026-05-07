"""
Photo Portal – Face Recognition microservice.
Laravel backend expects: GET /health, POST /detect, POST /compare, POST /search.
"""
import base64
import json
import os
from io import BytesIO

import face_recognition
import numpy as np
from flask import Flask, request, jsonify
from PIL import Image

app = Flask(__name__)
app.config["MAX_CONTENT_LENGTH"] = 50 * 1024 * 1024  # 50MB

# face_recognition uses 128-d encodings; distance 0 = identical, ~0.6 often used as threshold
SIMILARITY_THRESHOLD = 0.6


def load_image_from_file(storage_file) -> np.ndarray:
    """Load image from Werkzeug FileStorage into RGB numpy array."""
    if storage_file is None or not storage_file.filename:
        raise ValueError("No file provided")
    data = storage_file.read()
    if not data:
        raise ValueError("Empty file")
    img = Image.open(BytesIO(data)).convert("RGB")
    return np.array(img)


def face_encoding_to_json(enc: np.ndarray) -> str:
    """Encode 128-d vector as JSON list (for Laravel embedding storage)."""
    return json.dumps(enc.tolist())


def json_to_face_encoding(s: str) -> np.ndarray:
    """Decode JSON list to 128-d vector."""
    return np.array(json.loads(s))


@app.route("/health", methods=["GET"])
def health():
    return jsonify({
        "status": "ok",
        "service": "face-recognition",
    })


@app.route("/detect", methods=["POST"])
def detect():
    if "file" not in request.files:
        return jsonify({"message": "Missing file"}), 400
    file = request.files["file"]
    try:
        img = load_image_from_file(file)
    except Exception as e:
        return jsonify({"message": str(e)}), 400

    try:
        face_locations = face_recognition.face_locations(img)
        encodings = face_recognition.face_encodings(img, face_locations)
    except Exception as e:
        return jsonify({"message": f"Detection failed: {e}"}), 500

    faces = []
    for (top, right, bottom, left), encoding in zip(face_locations, encodings):
        width = right - left
        height = bottom - top
        # confidence placeholder; face_recognition doesn't provide it
        confidence = 1.0
        bounding_box = {"top": int(top), "right": int(right), "bottom": int(bottom), "left": int(left)}
        embedding = face_encoding_to_json(encoding)
        faces.append({
            "x": int(left),
            "y": int(top),
            "width": width,
            "height": height,
            "confidence": confidence,
            "bounding_box": bounding_box,
            "embedding": embedding,
        })

    return jsonify({"data": {"faces": faces}})


@app.route("/compare", methods=["POST"])
def compare():
    if "file1" not in request.files or "file2" not in request.files:
        return jsonify({"message": "Missing file1 or file2"}), 400
    try:
        img1 = load_image_from_file(request.files["file1"])
        img2 = load_image_from_file(request.files["file2"])
    except Exception as e:
        return jsonify({"message": str(e)}), 400

    try:
        enc1 = face_recognition.face_encodings(img1)
        enc2 = face_recognition.face_encodings(img2)
    except Exception as e:
        return jsonify({"message": f"Encoding failed: {e}"}), 500

    if not enc1:
        return jsonify({"message": "No face found in first image"}), 400
    if not enc2:
        return jsonify({"message": "No face found in second image"}), 400

    enc1, enc2 = enc1[0], enc2[0]
    distances = face_recognition.face_distance([enc1], enc2)
    distance = float(distances[0])
    # Convert distance to similarity: 0 distance -> 1, higher distance -> lower similarity
    similarity = max(0.0, 1.0 - distance)
    match = distance <= SIMILARITY_THRESHOLD

    return jsonify({
        "data": {
            "match": bool(match),
            "similarity": round(similarity, 4),
        }
    })


@app.route("/search", methods=["POST"])
def search():
    if "file" not in request.files:
        return jsonify({"message": "Missing file"}), 400
    query_file = request.files["file"]
    refs = []
    i = 0
    while True:
        ref = request.files.get(f"ref_{i}")
        if ref is None or not ref.filename:
            break
        refs.append(ref)
        i += 1

    if not refs:
        return jsonify({"data": {"matches": []}})

    try:
        query_img = load_image_from_file(query_file)
    except Exception as e:
        return jsonify({"message": str(e)}), 400

    try:
        query_encodings = face_recognition.face_encodings(query_img)
    except Exception as e:
        return jsonify({"message": f"Query encoding failed: {e}"}), 500

    if not query_encodings:
        return jsonify({"data": {"matches": []}})

    query_enc = query_encodings[0]
    ref_encodings = []
    for ref in refs:
        try:
            ref_img = load_image_from_file(ref)
            encs = face_recognition.face_encodings(ref_img)
            ref_encodings.append(encs[0] if encs else None)
        except Exception:
            ref_encodings.append(None)

    matches = []
    for idx, ref_enc in enumerate(ref_encodings):
        if ref_enc is None:
            continue
        distances = face_recognition.face_distance([query_enc], ref_enc)
        distance = float(distances[0])
        similarity = max(0.0, 1.0 - distance)
        if distance <= SIMILARITY_THRESHOLD:
            matches.append({
                "index": idx,
                "score": round(similarity, 4),
                "similarity": round(similarity, 4),
                "bounding_box": None,
            })

    # Sort by score descending
    matches.sort(key=lambda m: m["score"], reverse=True)

    return jsonify({"data": {"matches": matches}})


if __name__ == "__main__":
    host = os.environ.get("HOST", "0.0.0.0")
    port = int(os.environ.get("PORT", "5000"))
    app.run(host=host, port=port, debug=os.environ.get("FLASK_DEBUG", "0") == "1")
