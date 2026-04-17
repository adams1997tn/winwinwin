<?php
namespace Sport2\Core;

class Logger
{
    private string $logFile;

    public function __construct(?string $logFile = null)
    {
        $this->logFile = $logFile ?? sport2_config()['sync']['log_file'];
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }

    public function info(string $msg, array $ctx = []): void  { $this->log('INFO', $msg, $ctx); }
    public function warn(string $msg, array $ctx = []): void  { $this->log('WARN', $msg, $ctx); }
    public function error(string $msg, array $ctx = []): void { $this->log('ERROR', $msg, $ctx); }

    private function log(string $level, string $msg, array $ctx): void
    {
        $ts = date('Y-m-d H:i:s');
        $line = "[{$ts}] [{$level}] {$msg}";
        if ($ctx) $line .= ' ' . json_encode($ctx, JSON_UNESCAPED_SLASHES);
        $line .= PHP_EOL;
        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
