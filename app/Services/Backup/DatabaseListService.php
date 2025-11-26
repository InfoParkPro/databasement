<?php

namespace App\Services\Backup;

use App\Models\DatabaseServer;
use PDO;
use PDOException;

class DatabaseListService
{
    /**
     * Get list of databases/schemas from a database server
     *
     * @return array<string>
     */
    public function listDatabases(DatabaseServer $databaseServer): array
    {
        try {
            $pdo = $this->createConnection($databaseServer);

            return match ($databaseServer->database_type) {
                'mysql', 'mariadb' => $this->listMysqlDatabases($pdo),
                'postgresql' => $this->listPostgresqlDatabases($pdo),
                default => throw new \Exception("Database type {$databaseServer->database_type} not supported"),
            };
        } catch (PDOException $e) {
            throw new \Exception("Failed to list databases: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * @return array<string>
     */
    private function listMysqlDatabases(PDO $pdo): array
    {
        $statement = $pdo->query('SHOW DATABASES');
        $databases = $statement->fetchAll(PDO::FETCH_COLUMN, 0);

        // Filter out system databases
        return array_values(array_filter($databases, function ($db) {
            return ! in_array($db, ['information_schema', 'performance_schema', 'mysql', 'sys']);
        }));
    }

    /**
     * @return array<string>
     */
    private function listPostgresqlDatabases(PDO $pdo): array
    {
        $statement = $pdo->query(
            "SELECT datname FROM pg_database WHERE datistemplate = false AND datname NOT IN ('postgres')"
        );

        return $statement->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    private function createConnection(DatabaseServer $databaseServer): PDO
    {
        $dsn = $this->buildDsn($databaseServer);

        return new PDO(
            $dsn,
            $databaseServer->username,
            $databaseServer->password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]
        );
    }

    private function buildDsn(DatabaseServer $databaseServer): string
    {
        return match ($databaseServer->database_type) {
            'mysql', 'mariadb' => sprintf(
                'mysql:host=%s;port=%d',
                $databaseServer->host,
                $databaseServer->port
            ),
            'postgresql' => sprintf(
                'pgsql:host=%s;port=%d',
                $databaseServer->host,
                $databaseServer->port
            ),
            default => throw new \Exception("Database type {$databaseServer->database_type} not supported"),
        };
    }
}
