# Sử dụng Base Image PHP-FPM (FastCGI Process Manager)
FROM php:8.1-fpm-alpine

# Cài đặt các Dependencies hệ thống cần thiết (ví dụ: libzip-dev)
RUN apk update && apk add --no-cache libzip-dev

# Cài đặt MongoDB PHP Extension
# pecl là công cụ quản lý package cho PHP, dùng để cài MongoDB Driver
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apk del libzip-dev \
    && rm -rf /var/cache/apk/*

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Thiết lập thư mục làm việc và sao chép mã nguồn
WORKDIR /var/www/html
COPY . /var/www/html

# Cài đặt PHP dependencies (từ Composer)
RUN composer install --no-dev --optimize-autoloader
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb
# Khởi tạo Nginx và PHP-FPM (Xem ghi chú bên dưới)
# Vì Render chạy Docker, chúng ta cần một máy chủ web (Nginx) để phục vụ PHP-FPM.
# Để đơn giản, ta sẽ chỉ chạy PHP-FPM, và dựa vào Render để xử lý tiếp.
# Render thường yêu cầu bạn định nghĩa ENTRYPOINT/CMD. 

# Cổng mặc định cho PHP-FPM
EXPOSE 9000

# Lệnh khởi động chính
CMD ["php-fpm"]