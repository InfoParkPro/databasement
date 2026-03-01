FROM dunglas/frankenphp:1.11-php8.4

RUN install-php-extensions \
    ctype \
    curl \
    dom \
    fileinfo \
    filter \
    hash \
    mbstring \
    openssl \
    pcre \
    pdo \
    session \
    tokenizer \
    xml \
    pdo_mysql \
    pdo_pgsql \
    intl \
    pcntl \
    opcache \
    pcov \
    ftp \
    sockets \
    mongodb

COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

# Add system tools and database client CLIs
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        wget \
        ca-certificates \
        git \
        mariadb-client \
        postgresql-client \
        sqlite3 \
        supervisor \
        gzip \
        zstd \
        p7zip-full \
        tzdata \
        passwd \
        findutils \
        bash \
        procps \
        libtommath1 \
        openssh-client \
        sshpass \
        redis-tools && \
    rm -rf /var/lib/apt/lists/*

# Install Firebird 5.0.3 client tools (gbak, isql-fb)
RUN wget https://github.com/FirebirdSQL/firebird/releases/download/v5.0.3/Firebird-5.0.3.1683-0-linux-x64.tar.gz -O /tmp/firebird.tar.gz && \
    tar -xzf /tmp/firebird.tar.gz -C /tmp && \
    cd /tmp/Firebird-5.0.3.1683-0-linux-x64 && \
    ./install.sh -silent && \
    ln -sf /opt/firebird/bin/gbak /usr/local/bin/gbak && \
    ln -sf /opt/firebird/bin/isql /usr/local/bin/isql-fb && \
    ln -sf /opt/firebird/bin/isql /usr/local/bin/isql && \
    rm -rf /tmp/Firebird-5.0.3.1683-0-linux-x64 /tmp/firebird.tar.gz

COPY supervisord.conf /config/supervisord.conf
COPY php-custom-production.ini /usr/local/etc/php/php-custom-production.ini
COPY php-custom.ini /usr/local/etc/php/conf.d/zz-php-custom.ini

# Default PUID/PGID for the application user (can be overridden at runtime)
ENV PUID=1000
ENV PGID=1000

# Create application user with default PUID/PGID
# This user will be recreated at runtime if PUID/PGID differs
RUN groupadd -g ${PGID} application && \
    useradd -m -u ${PUID} -g application -s /bin/bash application && \
    setcap -r /usr/local/bin/frankenphp || true

# Create directories with correct ownership
RUN mkdir -p /app /data
RUN chmod -R 777 /config /data /app && \
    chown -R application:application /app /config /data

COPY scripts/ /usr/local/bin/
RUN chmod 755 /usr/local/bin/*.sh

WORKDIR /app

ENV WEB_DOCUMENT_ROOT=/app/public
ENV SERVER_NAME=":2226"
ENV MYSQL_CLI_TYPE="mariadb"
ENV ENABLE_QUEUE_WORKER="false"
ENV ENABLE_DATABASE_MIGRATION="true"
ENV TZ="UTC"
ENV PATH="/opt/firebird/bin:${PATH}"

HEALTHCHECK NONE

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["/usr/local/bin/start.sh"]
