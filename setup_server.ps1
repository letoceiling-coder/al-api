# PowerShell СЃРєСЂРёРїС‚ РґР»СЏ РЅР°СЃС‚СЂРѕР№РєРё СЃРµСЂРІРµСЂР° С‡РµСЂРµР· SSH
$server = "root@89.169.39.244"
$remotePath = "/var/www/AL"

Write-Host "рџ”§ РџРѕРґРєР»СЋС‡РµРЅРёРµ Рє СЃРµСЂРІРµСЂСѓ..." -ForegroundColor Cyan

# РћР±РЅРѕРІР»РµРЅРёРµ РєРѕРґР°
Write-Host "рџ“Ґ РћР±РЅРѕРІР»РµРЅРёРµ РєРѕРґР°..." -ForegroundColor Yellow
ssh $server "cd $remotePath && git pull"

# РќР°СЃС‚СЂРѕР№РєР° File Cache
Write-Host "рџ“ќ РќР°СЃС‚СЂРѕР№РєР° File Cache..." -ForegroundColor Yellow
ssh $server "cd $remotePath && bash setup_cache_file.sh || (cp .env .env.backup && echo 'CACHE_DRIVER=file' >> .env && echo 'SESSION_DRIVER=file' >> .env && echo 'QUEUE_CONNECTION=sync' >> .env)"

# РџСЂР°РІР° РґРѕСЃС‚СѓРїР°
Write-Host "рџ”ђ РќР°СЃС‚СЂРѕР№РєР° РїСЂР°РІ..." -ForegroundColor Yellow
ssh $server "cd $remotePath && mkdir -p storage/framework/cache && chown -R www-data:www-data storage/framework/cache && chmod -R 775 storage/framework/cache"

# РћС‡РёСЃС‚РєР° РєРµС€Р°
Write-Host "рџ§№ РћС‡РёСЃС‚РєР° РєРµС€Р°..." -ForegroundColor Yellow
ssh $server "cd $remotePath && php artisan config:clear && php artisan cache:clear"

Write-Host "вњ… Р“РѕС‚РѕРІРѕ!" -ForegroundColor Green
