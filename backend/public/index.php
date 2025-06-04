<?php
require __DIR__ . '/../src/Core/Autoloader.php';

use Camagru\Core\Config;
use Camagru\Core\Router;
use Camagru\Controller\AuthController;
use Camagru\Controller\ImageController;

Config::init();

$router = new Router();

$router->add('GET', '/images', [new ImageController(), 'list']);
$router->add('POST', '/images/upload', [new ImageController(), 'upload']);
//$router->add('GET', '/images/{id}', [new ImageController(), 'view']);
$router->add('POST', '/register', [new AuthController(), 'register']);
$router->add('GET', '/confirm', [new AuthController(), 'confirm']);

$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$router->dispatch( $method, $url);
