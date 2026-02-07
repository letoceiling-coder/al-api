@echo off
echo Deploying to server...
ssh root@89.169.39.244 "cd /var/www/AL && git pull origin main && php artisan route:clear && php artisan config:clear && php artisan config:cache && echo Deploy completed!"
echo Done!
