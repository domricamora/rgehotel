<?php
namespace App\Core;

abstract class Controller
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::instance();
    }

    protected function view(string $view, array $data = [], ?string $layout = 'main'): string
    {
        return View::render($view, $data, $layout);
    }

    protected function json($data, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit('Method Not Allowed');
        }
        if (!verify_csrf()) {
            http_response_code(419);
            exit('Invalid CSRF token. Please go back and try again.');
        }
    }

    protected function abort(int $code, string $message = ''): string
    {
        http_response_code($code);
        return View::render("errors.$code", ['message' => $message], 'main');
    }
}
