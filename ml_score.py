import sys, json, joblib, pandas as pd

model = joblib.load("/opt/ml/traffic_iforest.pkl")

data = json.loads(sys.stdin.read())

X = pd.DataFrame([{
    "status": data.get("status",0),
    "response_time_ms": data.get("response_time_ms",0),
    "bytes": data.get("bytes",0)
}])

pred = model.predict(X)[0]
score = model.decision_function(X)[0]

rule_score = 0
if data.get("status",0) >= 400: rule_score += 0.4
if data.get("response_time_ms",0) > 1500: rule_score += 0.3
if data.get("bytes",0) > 1000000: rule_score += 0.3

final = round(rule_score + (0.5 if pred == -1 else 0),2)

print(json.dumps({
    "ml_score": round(float(score),3),
    "ml_pred": int(pred),
    "rule_score": round(rule_score,2),
    "final_score": final
}))
