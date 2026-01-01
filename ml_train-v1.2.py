#!/usr/bin/env python3
"""
Train IsolationForest model for traffic anomaly detection
"""

import os
import sys
import logging
import pandas as pd
import joblib
from datetime import datetime
from sklearn.ensemble import IsolationForest
from sklearn.preprocessing import StandardScaler
from sqlalchemy import create_engine, text

# ================= CONFIG =================

DB_URI = os.getenv("TRAFFIC_DB_URI", "mysql+pymysql://root:@localhost/trafreqs")
MODEL_PATH = os.getenv("MODEL_PATH", "traffic_iforest.pkl")
LOOKBACK_DAYS = int(os.getenv("LOOKBACK_DAYS", 7))
CONTAMINATION = float(os.getenv("CONTAMINATION", 0.08))
N_ESTIMATORS = int(os.getenv("N_ESTIMATORS", 200))
RANDOM_STATE = 42

FEATURES = ["status", "response_time_ms", "bytes"]

# ================= LOGGING =================

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[logging.StreamHandler(sys.stdout)]
)

log = logging.getLogger("trainer")

# ================= MAIN =================

def main():
    log.info("Starting model training")

    engine = create_engine(DB_URI)

    query = text(f"""
        SELECT {", ".join(FEATURES)}
        FROM traffic_logs
        WHERE ts > NOW() - INTERVAL :days DAY
    """)

    try:
        df = pd.read_sql(query, engine, params={"days": LOOKBACK_DAYS})
    except Exception as e:
        log.error(f"Failed to fetch data: {e}")
        sys.exit(1)

    if df.empty:
        log.error("No data found — aborting training")
        sys.exit(1)

    log.info(f"Loaded {len(df)} rows")

    # ================= CLEANING =================

    X = df[FEATURES].apply(pd.to_numeric, errors="coerce")

    before = len(X)
    X = X.fillna(0)
    after = len(X)

    if before != after:
        log.warning(f"Dropped {before-after} invalid rows")

    # ================= SCALING =================

    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)

    # ================= TRAIN =================

    model = IsolationForest(
        n_estimators=N_ESTIMATORS,
        contamination=CONTAMINATION,
        random_state=RANDOM_STATE,
        n_jobs=-1
    )

    model.fit(X_scaled)

    # ================= SAVE =================

    artifact = {
        "model": model,
        "scaler": scaler,
        "features": FEATURES,
        "trained_at": datetime.utcnow().isoformat(),
        "rows": len(X),
        "lookback_days": LOOKBACK_DAYS,
        "contamination": CONTAMINATION,
    }

    joblib.dump(artifact, MODEL_PATH)

    log.info(f"Model saved to {MODEL_PATH}")
    log.info("Training complete")

if __name__ == "__main__":
    main()
