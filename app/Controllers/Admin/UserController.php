<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class UserController extends Controller
{
    public function index(): string
    {
        $users = $this->db->all('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.name');
        return $this->view('admin.users-index', ['active'=>'users','pageTitle'=>'Users','users'=>$users], 'admin');
    }

    public function edit(string $id): string
    {
        $user = $id === 'new' ? [] : $this->db->first('SELECT * FROM users WHERE id=?', [$id]);
        if ($id !== 'new' && !$user) return $this->abort(404);
        return $this->view('admin.user-edit', [
            'active'=>'users','pageTitle'=>($id==='new'?'New User':'Edit User'),
            'user'=>$user ?: [], 'id'=>$id,
            'roles'=>$this->db->all('SELECT * FROM roles ORDER BY id'),
        ], 'admin');
    }

    public function save(string $id): string
    {
        $this->requirePost();
        $email = strtolower(trim((string)$this->input('email')));
        $data = [
            'name' => $this->input('name'),
            'email' => $email,
            'role_id' => (int)$this->input('role_id'),
            'position' => $this->input('position'),
            'department' => $this->input('department'),
            'phone' => $this->input('phone'),
            'is_active' => $this->input('is_active') ? 1 : 0,
            'updated_at' => date('c'),
        ];
        $password = (string)$this->input('password');

        if ($id === 'new') {
            if ($this->db->scalar('SELECT COUNT(*) FROM users WHERE email=?', [$email])) {
                flash('error', 'A user with that email already exists.');
                redirect('/admin/users/new'); return '';
            }
            $data['password_hash'] = password_hash($password ?: bin2hex(random_bytes(6)), PASSWORD_DEFAULT);
            $newId = $this->db->insert('users', $data);
            flash('success', 'User created.');
            redirect('/admin/users/' . $newId);
        } else {
            if ($password !== '') $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            $this->db->update('users', $data, ['id' => $id]);
            flash('success', 'User updated.');
            redirect('/admin/users/' . $id);
        }
        return '';
    }
}
