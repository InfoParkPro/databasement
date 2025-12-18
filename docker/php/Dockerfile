FROM dunglas/frankenphp:1.9-php8.4-alpine

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
    opcache

COPY --from=composer/composer:latest-bin /composer /usr/bin/composer
# Add DB CLI tools used by the app: mysqldump, mysql, pg_dump, psql
RUN apk add --no-cache \
        mariadb-client mariadb-connector-c \
        postgresql-client \
        supervisor

COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php-custom-production.ini /usr/local/etc/php/php-custom-production.ini
ENV USER_ID=1000
ENV USER=application

RUN adduser -u ${USER_ID} -D -h /home/${USER} ${USER} && \
    chown -R ${USER}:${USER} /data/caddy && chown -R ${USER}:${USER} /config/caddy

RUN mkdir -p /app && chown -R ${USER}:${USER} /app && chmod -R 775 /app
COPY start-frankenphp.sh /usr/local/bin/
COPY docker-entrypoint.sh /usr/local/bin/

WORKDIR /app
USER 1000

ENV WEB_DOCUMENT_ROOT=/app/public
ENV SERVER_NAME=":8000"
ENV MYSQL_CLI_TYPE="mariadb"

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
