---
sidebar_position: 2
---

# Docker Compose

This guide will help you deploy Databasement using Docker Compose. This method is ideal when you want to run Databasement alongside its own dedicated database container.

## Prerequisites

- [Docker](https://docs.docker.com/engine/install/) and [Docker Compose](https://docs.docker.com/compose/install/)

## Quick Start

### 1. Create Project Directory

```bash
mkdir databasement && cd databasement
```

### 2. Generate Application Key

```bash
docker run --rm davidcrty/databasement:latest php artisan key:generate --show
```

Save this key for the next step.

### 3. Create docker-compose.yml

```yaml title="docker-compose.yml"
services:
  app:
    image: davidcrty/databasement:latest
    container_name: databasement
    restart: unless-stopped
    ports:
      - "8000:8000"
    environment:
      APP_URL: http://localhost:8000
      APP_KEY: base64:your-generated-key-here
      DB_CONNECTION: sqlite # or mysql, postgres
      DB_DATABASE: /data/database.sqlite
    volumes:
      - app-data:/data
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8000"]
      interval: 10s
      timeout: 5s
      retries: 5

  worker:
    image: davidcrty/databasement:latest
    container_name: databasement-worker
    restart: unless-stopped
    command: sh -c "php artisan db:wait && php artisan queue:work --queue=backups,default --tries=3 --timeout=3600 --sleep=3 --max-jobs=1000"
    environment:
      APP_URL: http://localhost:8000
      APP_KEY: base64:your-generated-key-here
      DB_CONNECTION: sqlite # or mysql, postgres
      DB_DATABASE: /data/database.sqlite
    volumes:
      - app-data:/data
    depends_on:
      - app

volumes:
  app-data:
```

:::tip
The `worker` service runs the Laravel queue worker as a separate container. This provides better stability and allows independent restarts without affecting the web application. The worker processes backup and restore jobs from the queue.
:::

### 4. Start the Services

```bash
docker compose up -d
```

### 5. Access the Application

Open http://localhost:8000 in your browser.

:::note
To expose your Databasement instance with HTTPS, you can use Traefik as a reverse proxy. For detailed instructions on
how to configure Traefik with Docker, please refer to
the [official Traefik documentation](https://doc.traefik.io/traefik/expose/docker/).
:::

## Production Setup (External Database)

For production, we recommend using MySQL or PostgreSQL instead of SQLite.
Check the [database configuration guide](../configuration.md#database-configuration) for more information.
