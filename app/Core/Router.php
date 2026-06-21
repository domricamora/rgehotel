<?php
namespace App\Core;

/**
 * Minimal regex router with named params, middleware, and method support.
 */
class Router
{
    private array $routes = [];

    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /** Register both GET and POST. */
    public function any(string $path, $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, $handler, array $middleware): void
    {
        $pattern = $this->compile($path);
        $this->routes[] = compact('method', 'path', 'pattern', 'handler', 'middleware');
    }

    private function compile(string $path): string
    {
        // {id} -> named capture; {id:\d+} -> with custom regex
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}#', function ($m) {
            $name = $m[1];
            $expr = $m[2] ?? '[^/]+';
            return '(?P<' . $name . '>' . $expr . ')';
        }, $path);
        return '#^' . $regex . '$#';
    }

    public function currentPath(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $base = rtrim((string) config('base_path', ''), '/');
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        $uri = '/' . ltrim($uri, '/');
        $trimmed = rtrim($uri, '/');
        return $trimmed === '' ? '/' : $trimmed;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $this->currentPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            if (preg_match($route['pattern'], $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middleware (each may halt by redirecting/exiting).
                foreach ($route['middleware'] as $mw) {
                    $this->runMiddleware($mw);
                }

                $this->invoke($route['handler'], $params);
                return;
            }
        }

        $this->notFound();
    }

    private function runMiddleware($mw): void
    {
        // Middleware as "Class@method" or "Class:arg" style: "role:admin"
        if (str_contains($mw, ':')) {
            [$name, $arg] = explode(':', $mw, 2);
        } else {
            $name = $mw; $arg = null;
        }
        $class = 'App\\Middleware\\' . ucfirst($name) . 'Middleware';
        if (class_exists($class)) {
            (new $class())->handle($arg);
        }
    }

    private function invoke($handler, array $params): void
    {
        if (is_callable($handler)) {
            echo call_user_func_array($handler, $params);
            return;
        }
        // "Controller@method"
        [$controller, $action] = explode('@', $handler);
        $class = 'App\\Controllers\\' . $controller;
        if (!class_exists($class)) {
            $this->notFound("Controller $class not found");
            return;
        }
        $instance = new $class();
        echo call_user_func_array([$instance, $action], $params);
    }

    private function notFound(string $msg = ''): void
    {
        http_response_code(404);
        echo \App\Core\View::render('errors.404', ['message' => $msg, 'title' => 'Page Not Found — RGE Hotel'], 'main');
    }
}
