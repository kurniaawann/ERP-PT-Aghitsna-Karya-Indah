npm run build
php artisan optimize:clear
php artisan serve --host=0.0.0.0 --port=8000
ngrok http 8000