# Deploy aplikasi PHP klasik ke Vercel Container Runtime.
FROM php:8.4-apache
RUN docker-php-ext-install pdo_mysql mysqli \
    && a2enmod headers rewrite
WORKDIR /var/www/html
COPY . /var/www/html/
# Hindari kebocoran arsip dan dokumen pengembangan lewat HTTP.
COPY .htaccess /var/www/html/.htaccess
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
CMD ["apache2-foreground"]
