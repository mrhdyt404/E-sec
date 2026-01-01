for i in {1..10}; do
  curl -X POST https://gbsuper.my.id/v1/traffic/collect  \
    -H "Authorization: Bearer MYSECRET123"  \
    -H "Content-Type: application/json"  \
    -d '{"ip":"101.19.9.10","status":401,"path":"/login"}';
done

