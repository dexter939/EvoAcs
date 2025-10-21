#!/bin/bash
set -e

php artisan serve --host=0.0.0.0 --port=5000 &
php artisan queue:work --sleep=3 --tries=3 --timeout=120 &
prosody --config prosody.cfg.lua &

wait
