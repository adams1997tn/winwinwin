<?php

namespace App\Core;

class Logger
{
    private string $logDir;

    public function __construct(string $logDir)
    {
        $this->logDir = rtrim($logDir, '/\\');
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public function info(string $message): void
    {
        $this->write('INFO', $message);
    }

    public function error(string $message): void
    {
        $this->write('ERROR', $message);
    }

    public function warning(string $message): void
    {
        $this->write('WARN', $message);
    }

    private function write(string $level, string $message): void
    {
        $date = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        $line = "[{$time}] [{$level}] {$message}" . PHP_EOL;
        file_put_contents("{$this->logDir}/scraper-{$date}.log", $line, FILE_APPEND | LOCK_EX);
    }
}
