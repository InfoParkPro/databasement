<?php

namespace App\Services\Backup\Databases;

use App\Contracts\BackupLogger;
use App\Exceptions\Backup\UnsupportedDatabaseTypeException;
use App\Services\Backup\DTO\DatabaseOperationResult;
use App\Support\Formatters;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;

class FirebirdDatabase implements DatabaseInterface
{
    /** @var array<string, mixed> */
    private array $config = [];

    /**
     * @param  array<string, mixed>  $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function dump(string $outputPath): DatabaseOperationResult
    {
        return new DatabaseOperationResult(command: sprintf(
            'gbak -b -user %s -password %s %s %s',
            escapeshellarg((string) ($this->config['user'] ?? '')),
            escapeshellarg((string) ($this->config['pass'] ?? '')),
            escapeshellarg($this->connectionTarget()),
            escapeshellarg($outputPath)
        ));
    }

    public function restore(string $inputPath): DatabaseOperationResult
    {
        throw new UnsupportedDatabaseTypeException('firebird');
    }

    public function prepareForRestore(string $schemaName, BackupLogger $logger): void
    {
        throw new UnsupportedDatabaseTypeException('firebird');
    }

    public function listDatabases(): array
    {
        $configuredNames = $this->config['database_names'] ?? null;
        if (is_array($configuredNames)) {
            return array_values(array_filter(
                array_map(static fn ($name) => is_string($name) ? trim($name) : '', $configuredNames),
                static fn (string $name) => $name !== ''
            ));
        }

        $database = trim((string) ($this->config['database'] ?? ''));

        return $database === '' ? [] : [$database];
    }

    public function testConnection(): array
    {
        $startTime = microtime(true);

        try {
            $result = Process::timeout(10)->run($this->buildConnectionProbeCommand());
        } catch (ProcessTimedOutException) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            return [
                'success' => false,
                'message' => 'Connection timed out after '.Formatters::humanDuration($durationMs).'. Please check the host and port are correct and accessible.',
                'details' => [],
            ];
        }

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        if ($result->failed()) {
            $errorOutput = trim($result->errorOutput() ?: $result->output());

            return [
                'success' => false,
                'message' => $errorOutput ?: 'Connection failed with exit code '.$result->exitCode(),
                'details' => [],
            ];
        }

        return [
            'success' => true,
            'message' => 'Connection successful',
            'details' => [
                'ping_ms' => $durationMs,
                'output' => trim($result->output()),
            ],
        ];
    }

    private function connectionTarget(): string
    {
        $database = (string) ($this->config['database'] ?? '');
        $host = trim((string) ($this->config['host'] ?? ''));
        $port = (int) ($this->config['port'] ?? 3050);

        if ($host === '') {
            return $database;
        }

        return "{$host}/{$port}:{$database}";
    }

    private function buildConnectionProbeCommand(): string
    {
        $script = <<<'BASH'
if command -v isql-fb >/dev/null 2>&1; then
  ISQL=isql-fb
elif command -v isql >/dev/null 2>&1; then
  ISQL=isql
else
  echo "Neither isql-fb nor isql is installed" >&2
  exit 127
fi
printf '%%s\n' "SELECT 1 FROM RDB\$DATABASE;" | "$ISQL" -user %s -password %s %s
BASH;

        return sprintf(
            "sh -lc %s",
            escapeshellarg(sprintf(
                $script,
                escapeshellarg((string) ($this->config['user'] ?? '')),
                escapeshellarg((string) ($this->config['pass'] ?? '')),
                escapeshellarg($this->connectionTarget())
            ))
        );
    }
}
