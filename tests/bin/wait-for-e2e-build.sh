#!/usr/bin/env bash
# Max amount of time to wait for the Docker container to be built
# Allowing 30 polling attempts, 10 seconds delay between each attempt
MAX_ATTEMPTS=30

# Delay (in seconds) between each polling attempt
DELAY_SEC=10

# Counter for the loop that checks if the Docker container had been built
count=0
WP_BASE_URL=$(node ./tests/bin/get-base-url.js)
printf "Testing URL: $WP_BASE_URL\n\n"
ready_page=$(curl -s ${WP_BASE_URL}/?pagename=ready)

while [[ "$(curl -s -o /dev/null -w ''%{http_code}'' ${WP_BASE_URL}/?pagename=ready)" != "200" || $ready_page != *"E2E-tests"* ]]
do
  echo "$(date) - Docker container is still being built"
  sleep ${DELAY_SEC}

  ((count++))
  ready_page=$(curl -s ${WP_BASE_URL}/?pagename=ready)

  if [[ $count -gt ${MAX_ATTEMPTS} ]]; then
  	echo "$(date) - Docker container couldn't be built"
  	exit 1
  fi
done

if [[ $count -ge 0 ]]; then
  echo "$(date) - Docker container had been built successfully"
fi
