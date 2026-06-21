<?php /** @var array $user @var string $id @var array $roles */
$v = fn($k, $d = '') => e($user[$k] ?? $d);
?>
<form method="post" action="<?= e(url('/admin/users/'.$id)) ?>">
  <?= csrf_field() ?>
  <div class="panel"><div class="panel__head"><h2><?= $id==='new'?'New User':e($user['name']) ?></h2></div><div class="panel__body">
    <div class="form-grid">
      <div class="field"><label>Full name</label><input name="name" value="<?= $v('name') ?>" required></div>
      <div class="field"><label>Email</label><input type="email" name="email" value="<?= $v('email') ?>" required></div>
      <div class="field"><label>Role</label><select name="role_id"><?php foreach ($roles as $r): ?><option value="<?= $r['id'] ?>" <?= ($user['role_id']??'')==$r['id']?'selected':'' ?>><?= e($r['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Department</label><input name="department" value="<?= $v('department') ?>"></div>
      <div class="field"><label>Position</label><input name="position" value="<?= $v('position') ?>"></div>
      <div class="field"><label>Phone</label><input name="phone" value="<?= $v('phone') ?>"></div>
      <div class="field"><label>Password <?= $id==='new'?'':'(leave blank to keep)' ?></label><input type="password" name="password" <?= $id==='new'?'':'' ?>></div>
      <div class="field"><label>&nbsp;</label><label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= ($user['is_active']??1)?'checked':'' ?>> <span>Active account</span></label></div>
    </div>
  </div></div>
  <div class="row-actions"><button class="btn btn-primary" type="submit"><?= icon('check') ?> Save</button><a class="btn btn-outline" href="<?= e(url('/admin/users')) ?>">Cancel</a></div>
</form>
