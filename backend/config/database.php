<?php

$host     = getenv('DB_HOST')     ?: '127.0.0.1';
$port     = getenv('DB_PORT')     ?: '5432';
$dbname   = getenv('DB_NAME')     ?: 'camagru';
$user     = getenv('DB_USER')     ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: '';

return [
    'dsn'      => sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $dbname),
    'user'     => $user,
    'password' => $password,
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
];