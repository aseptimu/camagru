<?php
namespace Camagru\Core;

use DateTime;
use Camagru\Core\Config;

class Logger
{
    public static function error(string $message): void
    {
        $date = (new DateTime())->format("Y-m-d H:i:s");
        $line = "[{$date}] ERROR: {$message}" . PHP_EOL;
        $rel = Config::get('LOG_FILE', 'logs/app.log');
        $file = Config::projectRoot() . DIRECTORY_SEPARATOR . $rel;
        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        error_log($line, 3, $file);
    }
}