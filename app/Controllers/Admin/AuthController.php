<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;

class AuthController extends Controller
{
    public function showLogin(): string
    {
        if (Auth::check()) { redirect('/admin'); return ''; }
        return $this->view('admin.login', [
            'title' => 'Sign in',
        ], 'auth');
    }

    public function login(): string
    {
        $this->requirePost();
        $email = (string) $this->input('email');
        $password = (string) $this->input('password');
        if (Auth::attempt($email, $password)) {
            $intended = $_SESSION['_intended'] ?? null;
            unset($_SESSION['_intended']);
            redirect($intended ? str_replace(base_path_url(), '', parse_url($intended, PHP_URL_PATH)) : '/admin');
            return '';
        }
        flash('error', 'Invalid email or password.');
        $_SESSION['_old']['email'] = $email;
        redirect('/admin/login');
        return '';
    }

    public function logout(): string
    {
        Auth::logout();
        flash('success', 'You have been signed out.');
        redirect('/admin/login');
        return '';
    }
}
