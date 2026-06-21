<?php /** @var array $users */ ?>
<div class="panel">
  <div class="panel__head"><h2>Staff &amp; Users</h2><a class="btn btn-primary btn-sm" href="<?= e(url('/admin/users/new')) ?>"><?= icon('check') ?> New User</a></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><strong><?= e($u['name']) ?></strong><div class="muted" style="font-size:.8rem"><?= e($u['position']) ?></div></td>
          <td><?= e($u['email']) ?></td>
          <td><span class="badge badge-blue"><?= e($u['role_name']) ?></span></td>
          <td><?= e($u['department'] ?: '—') ?></td>
          <td><?= $u['is_active'] ? badge('confirmed') : '<span class="badge badge-gray">Inactive</span>' ?></td>
          <td><a class="btn btn-outline btn-sm" href="<?= e(url('/admin/users/'.$u['id'])) ?>">Edit</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
