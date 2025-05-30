<?php
namespace Camagru\Core;

class Config
{
    /**
     * @var array<string,mixed>
     */
    private static array $env;

    public static function init(): void
    {
        self::$env =         self::$env = [
            'DB_HOST' => getenv('DB_HOST'),
            'DB_PORT' => getenv('DB_PORT'),
            'DB_NAME' => getenv('DB_NAME'),
            'DB_USER' => getenv('DB_USER'),
            'DB_PASSWORD' => getenv('DB_PASSWORD'),
            'UPLOAD_DIR' => getenv('UPLOAD_DIR'),
            'LOG_FILE' => getenv('LOG_FILE')
        ];
    }

    public static function get(string $key, $default = null)
    {
        return self::$env[$key] ?? $default;
    }

    public static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function uploadDir(): string
    {
        $rel = self::get('UPLOAD_DIR', 'public/uploads');
        return rtrim(self::projectRoot() . DIRECTORY_SEPARATOR . $rel, '/');
    } 

}