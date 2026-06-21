<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\Content;

class ContactController extends Controller
{
    public function index(): string
    {
        return $this->view('public.contact', [
            'active' => 'contact',
            'page'   => Content::page('contact'),
            'title'  => 'Contact RGE Hotel',
            'metaDescription' => 'Get in touch with RGE Hotel in Palompon, Leyte — the gateway to Kalanggaman Island.',
        ]);
    }

    public function store(): string
    {
        $this->requirePost();
        $name = trim((string)$this->input('name'));
        $email = filter_var($this->input('email'), FILTER_VALIDATE_EMAIL);
        $message = trim((string)$this->input('message'));
        if ($name === '' || !$email || $message === '') {
            flash('error', 'Please complete your name, a valid email and a message.');
            redirect('/contact');
            return '';
        }
        $this->db->insert('contact_messages', [
            'name' => $name, 'email' => $email,
            'phone' => trim((string)$this->input('phone')) ?: null,
            'subject' => trim((string)$this->input('subject')) ?: null,
            'message' => $message,
        ]);
        logger("Contact form: $name <$email>", 'contact');
        flash('success', 'Thank you for reaching out! Our team will reply shortly.');
        redirect('/contact');
        return '';
    }
}
