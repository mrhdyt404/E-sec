#!/bin/bash
API="https://gbsuper.my.id/v1/traffic/collect"
TOKEN="MYSECRET123"
COUNT=50

random_ip() {
  echo "$((RANDOM%223+1)).$((RANDOM%256)).$((RANDOM%256)).$((RANDOM%256))"
}

for i in $(seq 1 $COUNT); do
  IP=$(random_ip)

  # Random status: 200, 401, atau 500
  STATUS=$((RANDOM % 3))
  if [ $STATUS -eq 0 ]; then STATUS=200; fi
  if [ $STATUS -eq 1 ]; then STATUS=401; fi
  if [ $STATUS -eq 2 ]; then STATUS=500; fi

  curl -s -X POST "$API" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d "{
      \"timestamp\": \"$(date '+%Y-%m-%d %H:%M:%S')\",
      \"ip\": \"$IP\",
      \"method\": \"POST\",
      \"path\": \"/login\",
      \"status\": $STATUS,
      \"response_time_ms\": $((1000 + RANDOM % 4000)),
      \"bytes\": $((100000 + RANDOM % 5000000)),
      \"user_agent\": \"MaliciousScanner/10.0\",
      \"referrer\": \"\",
      \"server\": \"web-01\"
    }"
  sleep 0.05
done
