<?php
require __DIR__ . '/../src/Core/Autoloader.php';

use Camagru\Core\Config;
use Camagru\Core\Router;
use Camagru\Controller\AuthController;
use Camagru\Controller\ImageController;

Config::init();

$router = new Router();

$router->add('GET', '/images', [new ImageController(), 'list']);
$router->add('GET', '/images/feed', [new ImageController(), 'feed']);
$router->add('POST', '/images/upload', [new ImageController(), 'upload']);
$router->add('DELETE', '/images/{id}', [new ImageController(), 'delete']);
$router->add('POST', '/images/{id}/like', [new ImageController(), 'like']);
$router->add('POST', '/images/{id}/unlike', [new ImageController(), 'unlike']);
$router->add('POST', '/images/{id}/comments', [new ImageController(), 'addComment']);
$router->add('GET', '/images/{id}/comments', [new ImageController(), 'getComments']);
$router->add('GET', '/images/user/{userId}', [new ImageController(), 'listByUser']);

$router->add('POST', '/register', [new AuthController(), 'register']);
$router->add('POST', '/login', [new AuthController(), 'login']);
$router->add('GET', '/status', [new AuthController(), 'status']);
$router->add('POST', '/logout', [new AuthController(), 'logout']);
$router->add('GET', '/confirm', [new AuthController(), 'confirm']);
$router->add('POST', '/updateProfile', [new AuthController(), 'updateProfile']);
$router->add('POST', '/recover', [new AuthController(), 'recover']);
$router->add('POST', '/reset', [new AuthController(), 'reset']);

$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$router->dispatch( $method, $url);
