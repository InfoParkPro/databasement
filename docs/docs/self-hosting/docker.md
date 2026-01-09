---
sidebar_position: 3
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
  -p 2226:2226 \
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

Access the application at http://localhost:2226


:::info
The Docker image is [**rootless**](https://docs.docker.com/engine/security/rootless/) and runs as UID `1000` by default.
:::

## Use local directory as data volume

```bash
# Create directory with app ownership
mkdir -p /path/to/databasement/data
sudo chown 1000:1000 /path/to/databasement/data

# Run container

docker run -d \
  --name databasement \
  -p 2226:2226 \
  -e APP_KEY=YOUR_APP_KEY \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/data/database.sqlite \
  -e ENABLE_QUEUE_WORKER=true \
  -v /path/to/databasement/data:/data \
  davidcrty/databasement:latest
```

### Custom User ID (PUID/GUID)

To run as a different user, use Docker's `--user` flag. Replace `499:499` with your desired UID:GID (you can find your user's UID/GID with `id username`):

```bash
# Create directory with custom ownership
mkdir -p /path/to/databasement/data
sudo chown 499:499 /path/to/databasement/data

# Run with custom user
docker run -d \
  --user 499:499 \
  --name databasement \
  -p 2226:2226 \
  -e APP_KEY=YOUR_APP_KEY \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/data/database.sqlite \
  -e ENABLE_QUEUE_WORKER=true \
  -v /path/to/databasement/data:/data \
  davidcrty/databasement:latest
```

:::tip
For NAS platforms like **Unraid**, **Synology**, or **TrueNAS**, see the [NAS Platforms](./nas-platforms.md) guide for platform-specific instructions.
:::
