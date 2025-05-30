<?php
namespace Camagru\Core;

class Router
{
    /**
     * @var array Registered routes
     */
    private array $routes = [];

    /**
     * Register route
     * @param string $method    HTTP method
     * @param string $uri       URI pattern. Example: "/images/{id}"
     * @param callable $handler [Class:method]
     */
    public function add(string $method, string $uri, callable $handler): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => $uri,
            'handler' => $handler,
        ];
    }

    /**
     * Finds route and calls handler
     * @param string $method
     * @param string $uri
     * @return void
     */
    public function dispatch(string $method, string $uri): void
    {
        foreach($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $regex = '#^' . preg_replace('#\{(\w+)\}#', '([^/]+)', $route['pattern']) . '$#';

            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches);

                [$className, $methodName] = $route['handler'];
                $controller = new $className();
                call_user_func_array([$controller, $methodName], $matches);

                return;
            }

        }
        header("HTTP/1.1 404 Not Found");
        echo "404 Not Found";
    }
}