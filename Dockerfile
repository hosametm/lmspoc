FROM php:8.3-apache
RUN a2enmod rewrite
RUN docker-php-ext-install pdo pdo_mysql
WORKDIR /var/www/html
COPY ./ /var/www/html/
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf
EXPOSE 80
CMD ["apache2-foreground"]