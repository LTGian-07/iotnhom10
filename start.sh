#!/usr/bin/env bash

# Cài đặt Composer (nếu chưa có thư mục vendor)
if [ ! -d "vendor" ]; then
    echo "--- Installing Composer dependencies ---"
    composer install --no-dev --prefer-dist
fi

# KHỞI ĐỘNG SERVER NGINX VÀ PHP-FPM
# Render sử dụng một Buildpack để cài đặt PHP và các extension.
# Lệnh dưới đây sẽ khởi động máy chủ web và PHP-FPM
# Lệnh này giả định rằng bạn sử dụng Buildpack PHP của Render, 
# nếu Render không hỗ trợ PHP Buildpack, bạn sẽ cần dùng Dockerfile (xem lưu ý dưới).

# Lệnh phổ biến nhất để khởi động ứng dụng PHP trên PaaS (nếu Render hỗ trợ PHP Buildpack)
/usr/sbin/nginx -g 'daemon off;' &
/usr/sbin/php-fpm -F