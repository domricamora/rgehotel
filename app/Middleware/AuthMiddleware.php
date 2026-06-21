<?php
namespace App\Middleware;

use App\Core\Auth;

class AuthMiddleware
{
    public function handle(?string $arg = null): void
    {
        if (!Auth::check()) {
            flash('error', 'Please sign in to continue.');
            $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? null;
            redirect('/admin/login');
        }
    }
}
