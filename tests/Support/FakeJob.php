<?php

namespace Tests\Support;

use App\Contracts\JobInterface;

/**
 * A simple test double that implements JobInterface
 * Useful for testing services that depend on job logging
 */
class FakeJob implements JobInterface
{
    public array $logs = [];

    public string $status = 'pending';

    public ?\DateTimeInterface $started_at = null;

    public ?\DateTimeInterface $completed_at = null;

    public function logCommand(string $command, ?string $output = null, ?int $exitCode = null): void
    {
        $this->logs[] = [
            'timestamp' => now()->toIso8601String(),
            'type' => 'command',
            'command' => $command,
            'output' => $output,
            'exit_code' => $exitCode,
        ];
    }

    public function log(string $message, string $level = 'info', ?array $context = null): void
    {
        $entry = [
            'timestamp' => now()->toIso8601String(),
            'type' => 'log',
            'level' => $level,
            'message' => $message,
        ];

        if ($context !== null) {
            $entry['context'] = $context;
        }

        $this->logs[] = $entry;
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function getLogsByType(string $type): array
    {
        return array_filter($this->logs, fn ($log) => ($log['type'] ?? null) === $type);
    }

    public function getCommandLogs(): array
    {
        return $this->getLogsByType('command');
    }

    public function getDurationMs(): ?int
    {
        if ($this->completed_at === null || $this->started_at === null) {
            return null;
        }

        return (int) $this->started_at->diff($this->completed_at)->format('%s') * 1000;
    }

    public function getHumanDuration(): ?string
    {
        $ms = $this->getDurationMs();

        if ($ms === null) {
            return null;
        }

        if ($ms < 1000) {
            return "{$ms}ms";
        }

        $seconds = round($ms / 1000, 2);

        return "{$seconds}s";
    }

    public function markRunning(): void
    {
        $this->status = 'running';
        $this->started_at = new \DateTime;
    }

    public function markCompleted(): void
    {
        $this->status = 'completed';
        $this->completed_at = new \DateTime;
    }

    public function markFailed(\Throwable $exception): void
    {
        $this->status = 'failed';
        $this->completed_at = new \DateTime;
        $this->log($exception->getMessage(), 'error');
    }

    /**
     * Helper method to clear logs (useful for test assertions)
     */
    public function clearLogs(): void
    {
        $this->logs = [];
    }

    /**
     * Helper method to get the last log entry
     */
    public function getLastLog(): ?array
    {
        return empty($this->logs) ? null : end($this->logs);
    }

    /**
     * Helper method to check if a log message exists
     */
    public function hasLogMessage(string $message): bool
    {
        foreach ($this->logs as $log) {
            if (isset($log['message']) && str_contains($log['message'], $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper method to check if a command was logged
     */
    public function hasCommand(string $command): bool
    {
        foreach ($this->logs as $log) {
            if ($log['type'] === 'command' && str_contains($log['command'], $command)) {
                return true;
            }
        }

        return false;
    }
}
