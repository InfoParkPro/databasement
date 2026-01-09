<?php

namespace App\Services\Backup;

use App\Exceptions\ShellProcessFailed;
use App\Models\BackupJob;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ShellProcessor
{
    private ?BackupJob $logger = null;

    public function setLogger(BackupJob $logger): void
    {
        $this->logger = $logger;
    }

    public function process(string $command): string
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(null);

        // Mask sensitive data in command line for logging
        $sanitizedCommand = $this->sanitize($command);
        $startTime = microtime(true);

        // Start the command log entry before execution
        $logIndex = null;
        if ($this->logger) {
            $logIndex = $this->logger->startCommandLog($sanitizedCommand);
        }

        // Run with output callback for incremental updates
        $incrementalOutput = '';
        $process->run(function ($type, $data) use (&$incrementalOutput, $logIndex, $startTime) {
            $incrementalOutput .= $data;

            // Update the log entry with incremental output (sanitized)
            if ($this->logger && $logIndex !== null) {
                $this->logger->updateCommandLog($logIndex, [
                    'output' => $this->sanitize(trim($incrementalOutput)),
                    'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ]);
            }
        });

        $output = $this->sanitize($process->getOutput());
        $errorOutput = $this->sanitize($process->getErrorOutput());
        $exitCode = $process->getExitCode();

        // Finalize the log entry with exit code and status
        if ($this->logger && $logIndex !== null) {
            $combinedOutput = trim($output."\n".$errorOutput);
            $this->logger->updateCommandLog($logIndex, [
                'output' => $combinedOutput,
                'exit_code' => $exitCode,
                'status' => $process->isSuccessful() ? 'completed' : 'failed',
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);
        }

        if (! $process->isSuccessful()) {
            Log::error($sanitizedCommand."\n".$errorOutput);
            throw new ShellProcessFailed($errorOutput);
        }

        return $output;
    }

    /**
     * Sanitize sensitive data from commands or output before logging or throwing exceptions.
     *
     * This method redacts passwords, connection strings, tokens, and other credentials
     * that may appear in shell commands or their error output.
     */
    public function sanitize(string $input): string
    {
        $patterns = [
            // Match --password=VALUE or --password='VALUE' or --password="VALUE"
            '/--password=[\'"]?[^\s\'"]+[\'"]?/' => '--password=***',
            // Match -pPASSWORD (MySQL shorthand) - only when -p is a standalone argument
            // followed directly by password (not --port, not inside words like mysql-production)
            '/(^|\s)-p([^\s\-][^\s]*)/' => '$1-p***',
            // Match PGPASSWORD=VALUE
            '/PGPASSWORD=[^\s]+/' => 'PGPASSWORD=***',
            // Match MYSQL_PWD=VALUE
            '/MYSQL_PWD=[^\s]+/' => 'MYSQL_PWD=***',
            // Connection strings with embedded passwords (mysql://user:pass@host, postgresql://user:pass@host)
            '/(:\/\/[^:\/\s]+:)[^@\s]+(@)/' => '$1***$2',
            // AWS/S3 credentials that might leak in error messages
            '/AWS_SECRET_ACCESS_KEY=[^\s]+/' => 'AWS_SECRET_ACCESS_KEY=***',
            '/AWS_ACCESS_KEY_ID=[^\s]+/' => 'AWS_ACCESS_KEY_ID=***',
            // Generic token patterns
            '/(api[_-]?key|token|secret|auth)[=:]\s*[\'"]?[^\s\'"]+[\'"]?/i' => '$1=***',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $input = preg_replace($pattern, $replacement, $input);
        }

        return $input;
    }
}
