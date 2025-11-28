<?php

namespace App\Contracts;

interface JobInterface
{
    /**
     * Add a command log entry
     */
    public function logCommand(string $command, ?string $output = null, ?int $exitCode = null): void;

    /**
     * Add a log entry
     */
    public function log(string $message, string $level = 'info', ?array $context = null): void;

    /**
     * Get all logs
     */
    public function getLogs(): array;

    /**
     * Get logs filtered by type
     */
    public function getLogsByType(string $type): array;

    /**
     * Get command logs only
     */
    public function getCommandLogs(): array;

    /**
     * Calculate the duration of the job in milliseconds
     */
    public function getDurationMs(): ?int;

    /**
     * Get human-readable duration
     */
    public function getHumanDuration(): ?string;

    /**
     * Mark job as running
     */
    public function markRunning(): void;

    /**
     * Mark job as completed
     */
    public function markCompleted(): void;

    /**
     * Mark job as failed
     */
    public function markFailed(\Throwable $exception): void;
}
