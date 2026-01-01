#!/bin/bash

API="https://gbsuper.my.id/v1/traffic/collect"
TOKEN="MYSECRET123"
IP="170.168.120.20"
UA="MaliciousScanner/9.9"
REQ_PATH="/login"
COUNT=100
DELAY=0.1

echo "Starting malicious stress-test..."
echo "Target : $API"
echo "IP     : $IP"
echo "Path   : $REQ_PATH"
echo "-------------------------------"

i=1
while [ $i -le $COUNT ]; do
  CODE=$(curl -s -o /dev/null -w "%{http_code}" \
    --connect-timeout 2 \
    --max-time 5 \
    -X POST "$API" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d "{
      \"timestamp\": \"$(date '+%Y-%m-%d %H:%M:%S')\",
      \"ip\": \"$IP\",
      \"method\": \"GET\",
      \"path\": \"$REQ_PATH\",
      \"status\": 500,
      \"response_time_ms\": 2500,
      \"bytes\": 3000000,
      \"user_agent\": \"$UA\",
      \"referrer\": \"\",
      \"server\": \"web-01\"
    }")

  echo "[$i/$COUNT] sent → HTTP $CODE"
  sleep "$DELAY"
  i=$((i+1))
done

echo "-------------------------------"
echo "Stress test finished."
