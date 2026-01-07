---
sidebar_position: 2
---

# Database Servers

Database servers are the source of your backups. Databasement can connect to and backup MySQL, PostgreSQL, and MariaDB servers.

## Connection Requirements

### MySQL / MariaDB

#### Creating the user

```sql
CREATE USER 'databasement'@'%' IDENTIFIED BY 'your_secure_password';
```

#### Permissions for backup and restore (all databases)

```sql
GRANT SELECT, SHOW VIEW, TRIGGER, LOCK TABLES, PROCESS, EVENT, RELOAD,
      CREATE, DROP, ALTER, INDEX, INSERT, UPDATE, DELETE, REFERENCES
ON *.* TO 'databasement'@'%';

FLUSH PRIVILEGES;
```

:::note[Single database only]
To restrict the user to a single database, replace `*.*` with `database_name.*`. Note that with single-database permissions, the user cannot create or drop the database itself - you'll need to ensure the target database exists before restoring.
:::

### PostgreSQL

#### Creating the user

```sql
CREATE USER databasement WITH PASSWORD 'your_secure_password';
```

#### Permissions for backup and restore (all databases)

For full backup and restore capabilities, the user needs elevated privileges. The method depends on your PostgreSQL setup:

#### Self-hosted PostgreSQL

```sql
-- Option 1: Superuser (full access)
ALTER USER databasement WITH SUPERUSER;

-- Option 2: Create database privilege (can create/drop databases for restore)
ALTER USER databasement WITH CREATEDB;
```

#### AWS RDS PostgreSQL

RDS doesn't allow `SUPERUSER`. Grant the `rds_superuser` role instead:

```sql
GRANT rds_superuser TO databasement;
```

#### Azure Database for PostgreSQL

Azure uses the `azure_pg_admin` role:

```sql
GRANT azure_pg_admin TO databasement;
```

#### Additional grants for non-superuser setups

If not using superuser/rds_superuser/azure_pg_admin, grant access to existing databases:

```sql
-- Grant ownership or full privileges on the database
GRANT ALL PRIVILEGES ON DATABASE database_name TO databasement;

-- Connect to the database and grant schema access
\c database_name
GRANT ALL PRIVILEGES ON SCHEMA public TO databasement;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO databasement;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO databasement;
```

:::note[Single database only]
For single-database access without `CREATEDB`, the target database must already exist. Grant `ALL PRIVILEGES` on that specific database and its schema. The user won't be able to drop/recreate the database during restore - Databasement will drop and recreate tables instead.
:::

## Testing Connections

Before saving a server, always test the connection by clicking **Test Connection**.

### Common Connection Issues

| Error              | Solution                                                   |
|--------------------|------------------------------------------------------------|
| Connection refused | Verify host, port, and that the database server is running |
| Access denied      | Check username and password                                |
| Unknown host       | Verify the hostname is correct and DNS is resolving        |
| Connection timeout | Check firewall rules and network connectivity              |
