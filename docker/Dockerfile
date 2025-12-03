FROM php:8.3-cli

WORKDIR /app

# Install required packages
RUN apt-get update && apt-get install -y \
    curl \
    zip \
    unzip \
    nano \
    iputils-ping \
    wget \
    libpq-dev \
    firebird-dev \
    gnupg \
    ca-certificates \
    unixodbc \
    unixodbc-dev \
    libgssapi-krb5-2 \
    && rm -rf /var/lib/apt/lists/*

# Add Microsoft repo to apt repository
RUN curl -sSL -o /etc/apt/trusted.gpg.d/microsoft.asc https://packages.microsoft.com/keys/microsoft.asc \
    && echo "deb [arch=amd64,arm64,armhf] https://packages.microsoft.com/debian/12/prod bookworm main" > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update

# Install Microsoft ODBC driver
RUN ACCEPT_EULA=Y apt-get install -y msodbcsql18 \
    && ACCEPT_EULA=Y apt-get install -y mssql-tools18 \
    && echo 'export PATH="$PATH:/opt/mssql-tools18/bin"' >> ~/.bashrc

# Install PDO with extensions
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install pdo_pgsql
RUN docker-php-ext-install pdo_firebird
# RUN docker-php-ext-install pdo_oci

RUN pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY entrypoint.sh /run/entrypoint.sh
RUN chmod +x /run/entrypoint.sh

RUN ln -sf /bin/bash /bin/sh

ENTRYPOINT [ "/run/entrypoint.sh" ]
