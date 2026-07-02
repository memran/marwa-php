#!/bin/sh
set -e

echo "Waiting for database to be ready..."
sleep 5

cd /var/www/html

echo "Running migrations..."
php marwa migrate --no-interaction || echo "Migrations failed, continuing..."

echo "Running module migrations..."
php marwa module:migrate --no-interaction || echo "Module migrations failed, continuing..."

echo "Running seeders..."
php marwa db:seed --no-interaction || echo "Seeders failed, continuing..."

echo "Running module seeders..."
php marwa module:seed --no-interaction || echo "Module seeders failed, continuing..."

echo "Starting queue worker in background..."
php marwa queue:work --daemon &
QUEUE_PID=$!

echo "Starting scheduler in background..."
php marwa schedule:run &
SCHEDULER_PID=$!

echo "Setting up storage permissions..."
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

echo "Starting PHP-FPM..."
exec php-fpm
