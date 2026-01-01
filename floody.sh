for i in {1..200}; 
do   IP=$((RANDOM%255)).$((RANDOM%255)).$((RANDOM%255)).$((RANDOM%255));   
STATUS=$((RANDOM%10>7?401:200));   
curl -s -X POST https://www.gbsuper.my.id/v1/traffic/collect     -H "Authorization: Bearer MYSECRET123"     -H "Content-Type: application/json"     -d "{\"ip\":\"$IP\",\"method\":\"GET\",\"path\":\"/test\",\"status\":$STATUS}"; done
