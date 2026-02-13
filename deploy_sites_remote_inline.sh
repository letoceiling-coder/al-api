#!/bin/bash
# Одной командой: создаёт конфиги на сервере и включает сайты. Передать по SSH: bash -s < deploy_sites_remote_inline.sh

set -e

create_auto() {
cat > /etc/nginx/sites-available/auto.siteaccess.ru << 'NGINX_AUTO_EOF'
server {
    listen 80;
    listen [::]:80;
    server_name auto.siteaccess.ru;
    root /var/www/AL/public;
    index index.html index.php;
    client_max_body_size 100M;
    location /api { try_files $uri $uri/ /index.php?$query_string; }
    location = /trendagent/swagger { try_files $uri $uri/ /index.php?$query_string; }
    location = /trendagent/swagger.json { try_files $uri $uri/ /index.php?$query_string; add_header Access-Control-Allow-Origin *; }
    location = /trendagent/db { try_files $uri $uri/ /index.php?$query_string; }
    location = /trendagent-db { try_files $uri $uri/ /index.php?$query_string; }
    location /trendagent/ { alias /var/www/AL/public/trendagent/; try_files $uri $uri/ /trendagent/index.html; }
    location /docs { try_files $uri $uri/ /index.php?$query_string; }
    location /frontend/ { alias /var/www/AL/public/frontend/; try_files $uri $uri/ /frontend/index.html; index index.html; }
    location / { try_files $uri $uri/ /index.html /index.php?$query_string; }
    location /react { alias /var/www/AL/public/react; try_files $uri $uri/ /react/index.html; index index.html; }
    location ~ ^/api/v1/ai/stream { proxy_buffering off; fastcgi_pass unix:/var/run/php/php8.3-fpm.sock; fastcgi_index index.php; fastcgi_param SCRIPT_FILENAME $document_root/index.php; include fastcgi_params; fastcgi_buffering off; }
    location ~ \.php$ { fastcgi_pass unix:/var/run/php/php8.3-fpm.sock; fastcgi_index index.php; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; include fastcgi_params; }
}
NGINX_AUTO_EOF
}

create_pdab() {
cat > /etc/nginx/sites-available/p-d-a-b.neeklo.ru << 'NGINX_PDAB_EOF'
server {
    listen 80;
    listen [::]:80;
    server_name p-d-a-b.neeklo.ru;
    root /var/www/AL/public;
    index index.html index.php;
    client_max_body_size 100M;
    location /api { try_files $uri $uri/ /index.php?$query_string; }
    location = /trendagent/swagger { try_files $uri $uri/ /index.php?$query_string; }
    location = /trendagent/swagger.json { try_files $uri $uri/ /index.php?$query_string; add_header Access-Control-Allow-Origin *; }
    location = /trendagent/db { try_files $uri $uri/ /index.php?$query_string; }
    location = /trendagent-db { try_files $uri $uri/ /index.php?$query_string; }
    location /trendagent/ { alias /var/www/AL/public/trendagent/; try_files $uri $uri/ /trendagent/index.html; }
    location /docs { try_files $uri $uri/ /index.php?$query_string; }
    location /frontend/ { alias /var/www/AL/public/frontend/; try_files $uri $uri/ /frontend/index.html; index index.html; }
    location / { try_files $uri $uri/ /index.html /index.php?$query_string; }
    location /react { alias /var/www/AL/public/react; try_files $uri $uri/ /react/index.html; index index.html; }
    location ~ ^/api/v1/ai/stream { proxy_buffering off; fastcgi_pass unix:/var/run/php/php8.3-fpm.sock; fastcgi_index index.php; fastcgi_param SCRIPT_FILENAME $document_root/index.php; include fastcgi_params; fastcgi_buffering off; }
    location ~ \.php$ { fastcgi_pass unix:/var/run/php/php8.3-fpm.sock; fastcgi_index index.php; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; include fastcgi_params; }
}
NGINX_PDAB_EOF
}

echo "=== Создание конфигов Nginx ==="
create_auto
create_pdab
echo "Конфиги созданы."

for d in auto.siteaccess.ru p-d-a-b.neeklo.ru; do
  [ -L /etc/nginx/sites-enabled/$d ] || ln -sf /etc/nginx/sites-available/$d /etc/nginx/sites-enabled/$d
  echo "Включён: $d"
done

echo "--- Проверка nginx -t ---"
nginx -t
systemctl reload nginx
echo "--- Nginx перезагружен ---"

for domain in auto.siteaccess.ru p-d-a-b.neeklo.ru; do
  echo "Certbot для $domain..."
  certbot --nginx -d "$domain" --non-interactive --agree-tos --redirect --register-unsafely-without-email 2>/dev/null && echo "  OK $domain" || echo "  Пропуск/ошибка $domain"
done

nginx -t && systemctl reload nginx
echo "=== Готово: https://auto.siteaccess.ru/ https://p-d-a-b.neeklo.ru/ ==="
