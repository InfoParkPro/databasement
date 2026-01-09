---
sidebar_position: 4
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

### 3. Create Environment File

Create a `.env` file with your configuration. This file is shared between the `app` and `worker` services to ensure consistent settings.

#### SQLite (Simple Setup)

```bash title=".env"
APP_URL=http://localhost:2226
APP_KEY=base64:your-generated-key-here

# Database (SQLite)
DB_CONNECTION=sqlite
DB_DATABASE=/data/database.sqlite

# S3 Storage (optional - for cloud backups)
# AWS_ACCESS_KEY_ID=your-access-key
# AWS_SECRET_ACCESS_KEY=your-secret-key
# AWS_DEFAULT_REGION=us-east-1
# AWS_ENDPOINT_URL_S3=https://s3.amazonaws.com
# AWS_USE_PATH_STYLE_ENDPOINT=false
```

#### MySQL (Production Setup)

```bash title=".env"
APP_URL=http://localhost:2226
APP_KEY=base64:your-generated-key-here

# Database (MySQL)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=databasement
DB_USERNAME=databasement
DB_PASSWORD=your-secure-password

# S3 Storage (optional - for cloud backups)
# AWS_ACCESS_KEY_ID=your-access-key
# AWS_SECRET_ACCESS_KEY=your-secret-key
# AWS_DEFAULT_REGION=us-east-1
# AWS_ENDPOINT_URL_S3=https://s3.amazonaws.com
# AWS_USE_PATH_STYLE_ENDPOINT=false
```


### 4. Create docker-compose.yml

#### SQLite (Simple Setup)

```yaml title="docker-compose.yml"
services:
  app:
    image: davidcrty/databasement:latest
    container_name: databasement
    restart: unless-stopped
    ports:
      - "2226:2226"
    env_file: .env
    volumes:
      - app-data:/data
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:2226/health"]
      interval: 10s
      timeout: 5s
      retries: 5

  worker:
    image: davidcrty/databasement:latest
    container_name: databasement-worker
    restart: unless-stopped
    command: sh -c "php artisan db:wait && php artisan queue:work --queue=backups,default --tries=3 --timeout=3600 --sleep=3 --max-jobs=1000"
    env_file: .env
    volumes:
      - app-data:/data
    depends_on:
      - app

volumes:
  app-data:
```

#### MySQL (Production Setup)

```yaml title="docker-compose.yml"
services:
  app:
    image: davidcrty/databasement:latest
    container_name: databasement
    restart: unless-stopped
    ports:
      - "2226:2226"
    env_file: .env
    volumes:
      - app-data:/data
    depends_on:
      mysql:
        condition: service_healthy
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:2226/health"]
      interval: 10s
      timeout: 5s
      retries: 5

  worker:
    image: davidcrty/databasement:latest
    container_name: databasement-worker
    restart: unless-stopped
    command: sh -c "php artisan db:wait && php artisan queue:work --queue=backups,default --tries=3 --timeout=3600 --sleep=3 --max-jobs=1000"
    env_file: .env
    volumes:
      - app-data:/data
    depends_on:
      mysql:
        condition: service_healthy

  mysql:
    image: mysql:8.0
    container_name: databasement-mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: your-root-password
      MYSQL_DATABASE: databasement
      MYSQL_USER: databasement
      MYSQL_PASSWORD: your-secure-password
    volumes:
      - mysql-data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  app-data:
  mysql-data:
```

:::tip
Remember to restart both the `app` and `worker` services whenever you change the `.env` file: `docker compose restart app worker`
:::

:::tip
The `worker` service runs the Laravel queue worker as a separate container. This provides better stability and allows independent restarts without affecting the web application. The worker processes backup and restore jobs from the queue.
:::

### 5. Start the Services

```bash
docker compose up -d
```

### 6. Access the Application

Open http://localhost:2226 in your browser.

:::note
To expose your Databasement instance with HTTPS, you can use Traefik as a reverse proxy. For detailed instructions on
how to configure Traefik with Docker, please refer to
the [official Traefik documentation](https://doc.traefik.io/traefik/expose/docker/).
:::


## Use local directory as data volume
You will need to create the directory `/path/to/databasement/data` (you can replace with your actual path) and make sure it is owned by the user `1000:1000`

```bash
# Create directory with app ownership (replace /path/to/databasement with your actual path)
mkdir -p /path/to/databasement/data
sudo chown 1000:1000 /path/to/databasement/data
```

```yaml title="docker-compose.yml"
services:
  app:
    image: davidcrty/databasement:latest
    container_name: databasement
    restart: unless-stopped
    ports:
      - "2226:2226"
    env_file: .env
    volumes:
      - /path/to/databasement/data:/data
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:2226/health"]
      interval: 10s
      timeout: 5s
      retries: 5

  worker:
    image: davidcrty/databasement:latest
    container_name: databasement-worker
    restart: unless-stopped
    command: sh -c "php artisan db:wait && php artisan queue:work --queue=backups,default --tries=3 --timeout=3600 --sleep=3 --max-jobs=1000"
    env_file: .env
    volumes:
      - /path/to/databasement/data:/data
    depends_on:
      - app
```

## Custom User ID

:::info
The Docker image is [**rootless**](https://docs.docker.com/engine/security/rootless/) and runs as UID `1000` by default.
:::

To run as a different user, add the `user` directive:

```yaml
services:
  app:
    image: davidcrty/databasement:latest
    user: "1010:1010"
    # ... rest of config

  worker:
    image: davidcrty/databasement:latest
    user: "1010:1010"
    # ... rest of config
```

:::tip
For NAS platforms like **Unraid**, **Synology**, or **TrueNAS**, see the [NAS Platforms](./nas-platforms.md) guide for platform-specific instructions.
:::
