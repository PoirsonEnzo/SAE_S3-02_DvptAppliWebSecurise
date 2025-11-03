FROM php:8.2-fpm AS app_php

# Installer utilitaires et extensions PHP nécessaires
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions zip pdo_mysql


