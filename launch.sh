#!/bin/bash

ENV=$1

if [ "$ENV" == "prod" ]; then
  echo "🚀 Launching MarwaPHP in PRODUCTION mode..."
  docker-compose -f docker-compose.yml --env-file .env.prod up -d --build
elif [ "$ENV" == "dev" ]; then
  echo "🛠 Launching MarwaPHP in DEVELOPMENT mode..."
  docker-compose -f docker-compose.yml --env-file .env.dev up -d --build
else
  echo "❌ Please specify environment: dev or prod"
  echo "Usage: ./launch.sh dev OR ./launch.sh prod"
  exit 1
fi
