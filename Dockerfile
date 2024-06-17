# ใช้ PHP 8.0 ร่วมกับ Apache
FROM php:8.0-apache

# ติดตั้งและเปิดใช้งาน mysqli, pdo และ pdo_mysql extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql