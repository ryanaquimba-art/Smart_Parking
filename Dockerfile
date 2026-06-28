FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN a2dismod mpm_event && a2enmod mpm_pref ork
RUN a2enmod rewrite

COPY . /var/www/html/