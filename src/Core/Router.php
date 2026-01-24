<?php

namespace Src\Core;
use RuntimeException;
use Closure;

class Router
{
    private static array $routes = [];
    private static $router;
    private function __construct()
    {
    }
    public static function getRouter(): Router
    {
        if (!isset(self::$router)) {

            self::$router = new Router();
        }

        return self::$router;
    }
    private function register(string $route, string $method, array|callable $action)
    {
        $route = trim($route, '/');

        self::$routes[$method][$route] = $action;
    }
    public function get(string $route, array|callable $action)
    {
        $this->register($route, 'GET', $action);
    }
    public function post(string $route, array|callable $action)
    {
        $this->register($route, 'POST', $action);
    }
    public function put(string $route, array|callable $action)
    {
        $this->register($route, 'PUT', $action);
    }
    public function delete(string $route, array|callable $action)
    {
        $this->register($route, 'DELETE', $action);
    }
    public function dispatch()
    {
        $requestedRoute = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        $method = $_SERVER['REQUEST_METHOD'];
        $routes = self::$routes[$method] ?? [];

        foreach ($routes as $route => $action) {
            $routeRegex = preg_replace_callback('/{\w+(:([^}]+))?}/', function ($matches) {
                return isset($matches[1]) ? '(' . $matches[2] . ')' : '([a-zA-Z0-9_-]+)';
            }, $route);

            $routeRegex = '@^' . $routeRegex . '$@';

            if (preg_match($routeRegex, $requestedRoute, $matches)) {
                array_shift($matches);
                $routeParamsValues = $matches;

                $routeParamsNames = [];
                if (preg_match_all('/{(\w+)(:[^}]+)?}/', $route, $matches)) {
                    $routeParamsNames = $matches[1];
                }

                $routeParams = array_combine($routeParamsNames, $routeParamsValues);

                return $this->resolveAction($action, $routeParams);
            }
        }
        return $this->abort('404 Page not found');
    }
    private function resolveAction($action, array $routeParams): mixed
    {
        if ($action instanceof Closure) {
            return $action(...array_values($routeParams));
        }

        if (is_array($action) && count($action) === 2) {
            return call_user_func_array(
                [new $action[0], $action[1]],
                array_values($routeParams)
            );
        }

        throw new RuntimeException('Invalid route action');
    }
    private function abort(string $message, int $code = 404)
    {

        http_response_code($code);
        echo $message;
        exit();
    }

}