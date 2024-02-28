#!/bin/bash
php artisan cache:clear &
php artisan config:clear &
php artisan config:cache &
wait
