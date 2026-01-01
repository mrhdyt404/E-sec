#!/usr/bin/env python3
"""
Score traffic anomaly using trained IsolationForest model
Reads JSON from stdin, outputs JSON to stdout
"""

import sys
import json
import joblib
import pandas as pd
import logging
from pathlib import Path

# ================= CONFIG =================

MODEL_PATH = Path("/opt/ml/traffic_iforest.pkl")
FEATURES = ["status", "response_time_ms", "bytes"]

# ================= LOGGING =================

logging.basicConfig(
    level=logging.ERROR,  # change to INFO for debugging
    format="%(asctime)s [%(levelname)s] %(message)s"
)
log = logging.getLogger("ml_score")

# ================= HELPERS =================

def safe_float(v, default=0.0):
    try:
        return float(v)
    except Exception:
        return default

# ================= LOAD MODEL =================

if not MODEL_PATH.exists():
    log.error("Model file not found")
    print(json.dumps({"error": "model_not_found"}))
    sys.exit(1)

artifact = joblib.load(MODEL_PATH)

model = artifact.get("model")
scaler = artifact.get("scaler")
features = artifact.get("features", FEATURES)

if model is None or scaler is None:
    log.error("Invalid model artifact")
    print(json.dumps({"error": "invalid_model"}))
    sys.exit(1)

# ================= READ INPUT =================

try:
    raw = sys.stdin.read()
    data = json.loads(raw)
except Exception as e:
    log.error(f"Invalid input: {e}")
    print(json.dumps({"error": "invalid_json"}))
    sys.exit(1)

# ================= FEATURE EXTRACTION =================

row = {}
for f in features:
    row[f] = safe_float(data.get(f, 0))

X = pd.DataFrame([row])

# ================= SCALING =================

try:
    Xs = scaler.transform(X)
except Exception as e:
    log.error(f"Scaling failed: {e}")
    print(json.dumps({"error": "scaling_failed"}))
    sys.exit(1)

# ================= ML SCORE =================

try:
    pred = int(model.predict(Xs)[0])      # -1 anomaly, 1 normal
    ml_score = float(model.decision_function(Xs)[0])
except Exception as e:
    log.error(f"Model inference failed: {e}")
    print(json.dumps({"error": "model_inference_failed"}))
    sys.exit(1)

# ================= RULE SCORE =================

rule_score = 0.0

status = row.get("status", 0)
rt = row.get("response_time_ms", 0)
bytes_ = row.get("bytes", 0)

if status >= 400:
    rule_score += 0.4
if rt > 1500:
    rule_score += 0.3
if bytes_ > 1_000_000:
    rule_score += 0.3

# ================= FINAL SCORE =================

final_score = round(rule_score + (0.5 if pred == -1 else 0.0), 2)

# ================= OUTPUT =================

out = {
    "ml_pred": pred,
    "ml_score": round(ml_score, 4),
    "rule_score": round(rule_score, 2),
    "final_score": final_score,
}

print(json.dumps(out, ensure_ascii=False))
