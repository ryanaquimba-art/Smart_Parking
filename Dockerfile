# 1. Use the PHP-FPM base image instead of Apache
FROM php:8.2-fpm

# 2. Install Nginx to act as the web server
RUN apt-get update && apt-get install -y nginx \
    && rm -rf /var/lib/apt/lists/*

# 3. Install your required PHP database extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 4. Create a basic Nginx configuration directly in the image
RUN echo 'server { \
    listen 80 default_server; \
    root /var/www/html; \
    index index.php index.html index.htm; \
    \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    \
    location ~ \\.php$ { \
        include fastcgi_params; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        fastcgi_pass 127.0.0.1:9000; \
    } \
}' > /etc/nginx/sites-available/default

# 5. Copy your application code into the container
COPY . /var/www/html/

# 6. Ensure the web server has the correct permissions to read your files
RUN chown -R www-data:www-data /var/www/html

# 7. Expose the port so Railway knows where to route traffic
EXPOSE 80

# 8. Start both PHP-FPM (in the background) and Nginx (in the foreground)
CMD php-fpm -D && nginx -g "daemon off;"