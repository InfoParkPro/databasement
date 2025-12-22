---
sidebar_position: 1
---

# Docker

This guide will help you deploy Databasement using Docker. This is the simplest deployment method, using a single container that includes everything you need.

## Prerequisites

- [Docker](https://docs.docker.com/engine/install/) installed on your system

## Quick Start (SQLite)

The simplest way to run Databasement with SQLite as the database:

```bash
# Generate an application key
APP_KEY=$(docker run --rm davidcrty/databasement:latest php artisan key:generate --show)
docker volume create databasement-data
# Run the container
docker run -d \
  --name databasement \
  -p 8000:8000 \
  -e APP_KEY=$APP_KEY \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/data/database.sqlite \
  -e ENABLE_QUEUE_WORKER=true \
  -v databasement-data:/data \
  davidcrty/databasement:latest
```

:::note
The `ENABLE_QUEUE_WORKER=true` environment variable enables the background queue worker inside the container. This is required for processing backup and restore jobs. When using Docker Compose, the worker runs as a separate service instead.
:::

Access the application at http://localhost:8000

## Production Setup (External Database)

For production, we recommend using MySQL or PostgreSQL instead of SQLite.
Check the [database configuration guide](../configuration.md#database-configuration) for more information.
