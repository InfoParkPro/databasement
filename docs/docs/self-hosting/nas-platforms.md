---
sidebar_position: 7
---

# NAS Platforms

This guide provides platform-specific instructions for deploying Databasement on popular NAS (Network Attached Storage) and home server systems.

## Overview

NAS platforms typically use bind mounts instead of Docker volumes, which requires matching the container's user ID with the host system's permissions. The Databasement Docker image is **rootless** and supports running as any user via the `--user` flag.

## Common Configuration

### Generate APP_KEY

Before deploying, generate an application key:

```bash
docker run --rm davidcrty/databasement:latest php artisan key:generate --show
```

### Required Environment Variables

All platforms need these environment variables:

| Variable              | Value                                    |
|-----------------------|------------------------------------------|
| `APP_KEY`             | Your generated key (from above)          |
| `DB_CONNECTION`       | `sqlite`                                 |
| `DB_DATABASE`         | `/data/database.sqlite`                  |
| `ENABLE_QUEUE_WORKER` | `true`                                   |
| `TZ`                  | Your timezone (e.g., `America/New_York`) |

For MySQL/PostgreSQL configuration, see the [configuration guide](./configuration.md#database-configuration).

### Default UID/GID by Platform

| Platform        | Default UID:GID |
|-----------------|-----------------|
| Unraid          | `99:100`        |
| Synology        | `1000:1000`     |
| TrueNAS SCALE   | `568:568`       |
| QNAP            | `500:100`       |
| OpenMediaVault  | `1000:100`      |

:::info
The Docker image is [**rootless**](https://docs.docker.com/engine/security/rootless/) and runs as UID `1000` by default.
:::

## Unraid

Unraid runs containers as `nobody:users` (UID 99, GID 100) by default.

### Setup

1. Add a new container with repository: `davidcrty/databasement:latest`
2. Add `--user 99:100` to **Extra Parameters**
3. Configure port: `2226` → `2226`
4. Add path mapping: `/mnt/user/appdata/databasement` → `/data`
5. Add [environment variables](#required-environment-variables)

## Synology DSM

Synology DSM typically uses UID/GID `1000` for the first user, which matches the container's default.

### Setup (Container Manager - DSM 7.2+)

1. Open **Container Manager** → **Registry**
2. Search for `davidcrty/databasement` and download
3. Create container:
   - **Port**: `2226` → `2226`
   - **Volume**: `/docker/databasement` → `/data`
   - **Environment**: Add [required variables](#required-environment-variables)
   - **Enable auto-restart**: Yes

### Custom User ID

If you need a different user, find your UID via SSH (`id your-username`), then add to **Advanced Settings** → **Execution Command**.

## TrueNAS SCALE

TrueNAS SCALE uses `apps` user (UID 568) by default for applications.

### Setup

1. Go to **Apps** → **Discover Apps** → **Custom App**
2. Follow the [Docker Compose guide](./docker-compose.md), adding `user: "568:568"` to both `app` and `worker` services (see [Custom User ID](./docker-compose.md#custom-user-id) for example)
3. Volume: `/mnt/pool/apps/databasement` → `/data`
4. Add [environment variables](#required-environment-variables)

### Host Path Permissions

```bash
sudo chown -R 568:568 /mnt/pool/apps/databasement
```

## QNAP

QNAP Container Station supports Docker containers with custom configurations.

### Setup (Container Station)

1. Open **Container Station** → **Create**
2. Search for `davidcrty/databasement`
3. Configure:
   - **Port**: `2226` → `2226`
   - **Volume**: `/Container/databasement` → `/data`
   - **Environment**: Add [required variables](#required-environment-variables)
   - **Advanced**: Add `--user 500:100`

## OpenMediaVault

OpenMediaVault uses Docker via omv-extras plugin. Follow the [Docker Compose guide](./docker-compose.md) with `user: "1000:100"` (adjust with `id your-username`).

## Proxmox (LXC)

For Proxmox, run Databasement in an LXC container or VM with Docker.

Proxmox delegates UID/GID to the LXC container or VM, so match the UID:GID of the user running Docker inside your container.

### Setup

1. Create an unprivileged LXC (Debian 12 or Ubuntu 22.04)
2. Enable `nesting=1` feature
3. Install Docker inside the LXC
4. Follow the standard [Docker guide](./docker.md)

## Troubleshooting

### Permission Denied Errors

1. Check directory ownership:
   ```bash
   ls -la /path/to/databasement
   ```

2. Fix ownership to match your platform's [default UID/GID](#default-uidgid-by-platform):
   ```bash
   sudo chown -R UID:GID /path/to/databasement
   ```

### Verify Container User

```bash
docker inspect databasement --format '{{.Config.User}}'
```
