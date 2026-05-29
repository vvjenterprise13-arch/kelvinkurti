FROM php:8.2-fpm-alpine

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN apk add --no-cache nginx

RUN mkdir -p /run/nginx

COPY nginx.conf /etc/nginx/http.d/default.conf

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/cache \
    && chmod 777 /var/www/html/cache

COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
