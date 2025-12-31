import pandas as pd
import joblib
from sklearn.ensemble import IsolationForest
from sqlalchemy import create_engine

engine = create_engine("mysql+pymysql://root:@localhost/trafreqs")

df = pd.read_sql("""
SELECT status, response_time_ms, bytes
FROM traffic_logs
WHERE ts > NOW() - INTERVAL 7 DAY
""", engine)

X = df[['status','response_time_ms','bytes']].apply(pd.to_numeric, errors='coerce').fillna(0)

model = IsolationForest(
    n_estimators=150,
    contamination=0.02,
    random_state=42
)

model.fit(X)

joblib.dump(model, "traffic_iforest.pkl")

print("✅ Model retrained and saved")
