<?php

namespace App\Services\Backup;

use App\Exceptions\ShellProcessFailed;
use Symfony\Component\Process\Process;

class ShellProcessor
{
    public function process(Process $process): string
    {
        $process->setTimeout(null);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ShellProcessFailed($process->getErrorOutput());
        }

        return $process->getOutput();
    }
}
