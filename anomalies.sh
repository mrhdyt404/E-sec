#!/bin/bash

API="https://gbsuper.my.id/v1/traffic/collect"
TOKEN="MYSECRET123"
IP="100.168.190.10"
UA="MaliciousScanner/9.9"
REQ_PATH="/login"
COUNT=80
DELAY=0.1

echo "Starting anomaly traffic test..."
echo "--------------------------------"

for ((i=1;i<=COUNT;i++)); do
  CODE=$(curl -s -o /dev/null -w "%{http_code}" \
    --connect-timeout 2 \
    --max-time 5 \
    -X POST "$API" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d "{
      \"timestamp\": \"$(date '+%Y-%m-%d %H:%M:%S')\",
      \"ip\": \"$IP\",
      \"method\": \"POST\",
      \"path\": \"$REQ_PATH\",
      \"status\": 500,
      \"response_time_ms\": 2800,
      \"bytes\": 3000000,
      \"user_agent\": \"$UA\",
      \"referrer\": \"\",
      \"server\": \"web-01\"
    }")

  echo "[$i/$COUNT] sent → HTTP $CODE"
  sleep "$DELAY"
done

echo "Anomaly test finished."
