FROM php:8.3-cli

WORKDIR /app

# Install utilities
RUN apt-get update && apt-get install -y curl zip unzip nano iputils-ping wget libpq-dev firebird-dev


# Install PDO with extensions
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install pdo_pgsql
RUN docker-php-ext-install pdo_firebird
# RUN docker-php-ext-install pdo_oci

# Install PDO sqlsrv
RUN apt-get -y install unixodbc-dev
RUN pecl install sqlsrv pdo_sqlsrv

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY entrypoint.sh /run/entrypoint.sh
RUN chmod +x /run/entrypoint.sh

RUN ln -sf /bin/bash /bin/sh

ENTRYPOINT [ "/run/entrypoint.sh" ]
