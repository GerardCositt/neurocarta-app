# PHP 8.3 + GD + MariaDB en el MISMO contenedor (127.0.0.1). Más fiable que Docker Compose en GitHub Codespaces.
FROM mcr.microsoft.com/devcontainers/php:1-8.3-bookworm

# Evita que postinst de mariadb-server fallen en el build (no hay systemd; invoke-rc.d).
RUN printf '%s\n' '#!/bin/sh' 'exit 101' > /usr/sbin/policy-rc.d && chmod +x /usr/sbin/policy-rc.d

# Evita fallo de firma GPG de Yarn heredado de la imagen base.
RUN rm -f /etc/apt/sources.list.d/yarn.list /etc/apt/sources.list.d/yarn.list.save || true

RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        zip \
        unzip \
        default-mysql-client \
        mariadb-server \
        nodejs \
        npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd zip pdo_mysql \
    && rm -f /usr/sbin/policy-rc.d \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
