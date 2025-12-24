---
sidebar_position: 0
slug: /
---

# Databasement Documentation

Welcome to the **Databasement** documentation!

Databasement is a web application for managing database server backups. It allows you to register database servers (MySQL, PostgreSQL, MariaDB), test connections, schedule automated backups, and restore snapshots to any registered server.

## Features

- **Multi-database support**: Manage MySQL, PostgreSQL, and MariaDB servers
- **Automated backups**: Schedule recurring backups with customizable retention
- **Storage volumes**: Store backups locally or on S3-compatible storage
- **Cross-server restore**: Restore snapshots from one server to another
- **User management**: Multi-user support with two-factor authentication
- **Simple deployment**: Single container with built-in web server, queue worker, and scheduler

## Documentation Sections

### Self-Hosting

Learn how to deploy Databasement on your own infrastructure:

- [Introduction](self-hosting/intro) - Overview and requirements
- [Configuration](self-hosting/configuration) - Environment variables reference
- [Docker Guide](self-hosting/guides/docker) - Deploy with Docker
- [Docker Compose Guide](self-hosting/guides/docker-compose) - Deploy with Docker Compose
- [Kubernetes + Helm](self-hosting/guides/kubernetes-helm) - Deploy on Kubernetes

### User Guide

Learn how to use Databasement:

- [Getting Started](user-guide/intro) - First steps after installation
- [Database Servers](user-guide/database-servers) - Managing database connections
- [Backups](user-guide/backups) - Creating and managing backups
- [Snapshots](user-guide/snapshots) - Working with backup snapshots
- [Storage Volumes](user-guide/volumes) - Configuring backup storage

### Contributing

Want to contribute to Databasement?

- [Development Guide](contributing/development) - Set up a local development environment

## Quick Links

- [GitHub Repository](https://github.com/David-Crty/databasement)
- [Report an Issue](https://github.com/David-Crty/databasement/issues)
