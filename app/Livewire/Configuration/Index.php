<?php

namespace App\Livewire\Configuration;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    /**
     * @return array<string, array{value: mixed, env: string}>
     */
    public function getDatabaseConfig(): array
    {
        $driver = config('database.default');

        return [
            'driver' => [
                'value' => $driver,
                'env' => 'DB_CONNECTION',
            ],
            'host' => [
                'value' => config("database.connections.{$driver}.host", '-'),
                'env' => 'DB_HOST',
            ],
            'port' => [
                'value' => config("database.connections.{$driver}.port", '-'),
                'env' => 'DB_PORT',
            ],
            'username' => [
                'value' => config("database.connections.{$driver}.username", '-'),
                'env' => 'DB_USERNAME',
            ],
            'database' => [
                'value' => config("database.connections.{$driver}.database", '-'),
                'env' => 'DB_DATABASE',
            ],
        ];
    }

    /**
     * @return array<string, array{value: mixed, env: string, description: string}>
     */
    public function getBackupConfig(): array
    {
        return [
            'working_directory' => [
                'value' => config('backup.working_directory'),
                'env' => 'BACKUP_WORKING_DIRECTORY',
                'description' => __('Temporary directory for backup and restore operations.'),
            ],
            'mysql_cli_type' => [
                'value' => config('backup.mysql_cli_type'),
                'env' => 'MYSQL_CLI_TYPE',
                'description' => __('MySQL CLI type: "mariadb" or "mysql".'),
            ],
            'job_timeout' => [
                'value' => config('backup.job_timeout'),
                'env' => 'BACKUP_JOB_TIMEOUT',
                'description' => __('Maximum seconds a job can run.'),
            ],
            'job_tries' => [
                'value' => config('backup.job_tries'),
                'env' => 'BACKUP_JOB_TRIES',
                'description' => __('Number of times to attempt the job.'),
            ],
            'job_backoff' => [
                'value' => config('backup.job_backoff'),
                'env' => 'BACKUP_JOB_BACKOFF',
                'description' => __('Seconds to wait before retrying.'),
            ],
            'daily_cron' => [
                'value' => config('backup.daily_cron'),
                'env' => 'BACKUP_DAILY_CRON',
                'description' => __('Cron schedule for daily backups.'),
            ],
            'weekly_cron' => [
                'value' => config('backup.weekly_cron'),
                'env' => 'BACKUP_WEEKLY_CRON',
                'description' => __('Cron schedule for weekly backups.'),
            ],
            'cleanup_cron' => [
                'value' => config('backup.cleanup_cron'),
                'env' => 'BACKUP_CLEANUP_CRON',
                'description' => __('Cron schedule for snapshot cleanup.'),
            ],
        ];
    }

    public function render(): View
    {
        return view('livewire.configuration.index', [
            'databaseConfig' => $this->getDatabaseConfig(),
            'backupConfig' => $this->getBackupConfig(),
        ])->layout('components.layouts.app', ['title' => __('Configuration')]);
    }
}
