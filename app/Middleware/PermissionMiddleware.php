<?php
namespace App\Middleware;

use App\Core\Auth;
use App\Core\View;

/**
 * Usage in routes: middleware ['auth', 'permission:bookings.manage']
 */
class PermissionMiddleware
{
    public function handle(?string $arg = null): void
    {
        if (!Auth::check()) {
            redirect('/admin/login');
        }
        if ($arg && !Auth::can($arg)) {
            http_response_code(403);
            echo View::render('errors.403', ['message' => 'You do not have permission to access this area.'], 'admin');
            exit;
        }
    }
}
