<?php
require __DIR__ . '/../src/Core/Autoloader.php';

\Camagru\Core\Config::init();

use Camagru\Core\Router;
use Camagru\Controller\HomeController;
use Camagru\Controller\ImageController;

$router = new Router();

$router->add('GET', '/', [new HomeController(), 'index']);
$router->add('GET', '/images', [new ImageController(), 'list']);
$router->add('POST', '/images/upload', [new ImageController(), 'upload']);
$router->add('GET', '/images/{id}', [new ImageController(), 'view']);

$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$router->dispatch( $method, $url);
