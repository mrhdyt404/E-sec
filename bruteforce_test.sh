#!/bin/bash
API="https://gbsuper.my.id/v1/traffic/collect"
TOKEN="MYSECRET123"
IP="203.0.113.45"

echo "Starting brute-force HTTPS simulation..."

for i in $(seq 1 100); do
  curl -s -o /dev/null -w "[$i] sent\n" -X POST "$API" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d "{
      \"ip\":\"$IP\",
      \"method\":\"POST\",
      \"path\":\"/login\",
      \"status\":401,
      \"response_time_ms\":50,
      \"bytes\":512,
      \"user_agent\":\"MaliciousBot/1.0\",
      \"server\":\"web-01\"
    }"
done

echo "Simulation finished."
