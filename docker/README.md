# Docker Test Database Setup

This Docker Compose setup provides MySQL and PostgreSQL databases for testing backup and restoration functionality.

## Quick Start

### Start the databases
```bash
docker-compose up -d
```

### Stop the databases
```bash
docker-compose down
```

### Stop and remove data volumes (fresh start)
```bash
docker-compose down -v
```

## Database Connection Details

### MySQL
- **Host:** localhost
- **Port:** 3306
- **Database:** testdb
- **Username:** admin
- **Password:** admin
- **Root Password:** admin

### PostgreSQL
- **Host:** localhost
- **Port:** 5432
- **Database:** testdb
- **Username:** admin
- **Password:** admin

## Test Data Structure

Both databases contain identical test data:

### Users Table
| id | name | email |
|----|------|-------|
| 1 | John Doe | john.doe@example.com |
| 2 | Jane Smith | jane.smith@example.com |

### Products Table
| id | name | description | price | stock |
|----|------|-------------|-------|-------|
| 1 | Laptop | High-performance laptop for developers | 1299.99 | 15 |
| 2 | Mouse | Wireless ergonomic mouse | 29.99 | 50 |

## Connecting via CLI

### MySQL
```bash
# Using docker exec
docker exec -it backup-manager-mysql mysql -u admin -padmin testdb

# Using local mysql client
mysql -h 127.0.0.1 -P 3306 -u admin -padmin testdb
```

### PostgreSQL
```bash
# Using docker exec
docker exec -it backup-manager-postgres psql -U admin -d testdb

# Using local psql client
psql -h 127.0.0.1 -p 5432 -U admin -d testdb
```

## Sample Queries

### View all users
```sql
SELECT * FROM users;
```

### View all products
```sql
SELECT * FROM products;
```

### Count records
```sql
SELECT 'users' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'products', COUNT(*) FROM products;
```

## Healthchecks

Both databases have healthchecks configured. You can verify their status:

```bash
docker-compose ps
```

Healthy databases will show `(healthy)` in their status.

## Logs

View database logs:

```bash
# MySQL logs
docker-compose logs -f mysql

# PostgreSQL logs
docker-compose logs -f postgres
```

## Testing Backup and Restore

### MySQL Backup Example
```bash
# Backup
docker exec backup-manager-mysql mysqldump -u admin -padmin testdb > backup-mysql.sql

# Restore
docker exec -i backup-manager-mysql mysql -u admin -padmin testdb < backup-mysql.sql
```

### PostgreSQL Backup Example
```bash
# Backup
docker exec backup-manager-postgres pg_dump -U admin testdb > backup-postgres.sql

# Restore
docker exec -i backup-manager-postgres psql -U admin testdb < backup-postgres.sql
```

## Troubleshooting

### Port Already in Use
If ports 3306 or 5432 are already in use on your system, modify the ports in `docker-compose.yml`:

```yaml
ports:
  - "3307:3306"  # Change host port to 3307
```

### Permission Denied Errors
Ensure the init script directories have proper permissions:

```bash
chmod -R 755 docker/
```

### Reset Everything
To completely reset and rebuild:

```bash
docker-compose down -v
docker-compose up -d --force-recreate
```
