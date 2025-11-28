<?php

namespace App\Services\Backup;

use App\Contracts\JobInterface;
use App\Exceptions\ShellProcessFailed;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ShellProcessor
{
    private ?JobInterface $logger = null;

    public function setLogger(JobInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function process(Process $process): string
    {
        $process->setTimeout(null);

        $commandLine = $process->getCommandLine();

        // Mask sensitive data in command line for logging
        $sanitizedCommand = $this->sanitizeCommand($commandLine);

        // Log command execution start
        if ($this->logger) {
            $this->logger->log('Executing command', 'info');
        }

        $process->run();

        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();
        $exitCode = $process->getExitCode();

        // Log the command and result
        if ($this->logger) {
            $combinedOutput = trim($output."\n".$errorOutput);
            $this->logger->logCommand($sanitizedCommand, $combinedOutput, $exitCode);
        }

        if (! $process->isSuccessful()) {
            Log::error($commandLine."\n".$errorOutput);

            if ($this->logger) {
                $this->logger->log("Command failed with exit code {$exitCode}", 'error', ['exit_code' => $exitCode]);
            }

            throw new ShellProcessFailed($errorOutput);
        }

        return $output;
    }

    private function sanitizeCommand(string $command): string
    {
        // Mask passwords in MySQL/PostgreSQL commands
        $patterns = [
            '/--password=[^\s]+/' => '--password=***',
            '/-p[^\s]+/' => '-p***',
            '/PGPASSWORD=[^\s]+/' => 'PGPASSWORD=***',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $command = preg_replace($pattern, $replacement, $command);
        }

        return $command;
    }
}
