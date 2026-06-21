<?php /** @var array $rooms @var bool $canManage */ ?>
<div class="panel">
  <div class="panel__head"><h2>Room Status Board</h2></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Room</th><th>Type</th><th>Occupancy</th><th>Housekeeping</th><?php if($canManage):?><th>Update</th><?php endif;?></tr></thead>
      <tbody>
        <?php foreach ($rooms as $r): ?>
        <tr>
          <td><strong><?= e($r['code']) ?></strong> <span class="muted">Fl <?= e($r['floor']) ?></span></td>
          <td><?= e($r['room_name']) ?></td>
          <td><?= badge($r['status']) ?></td>
          <td><span class="badge badge-<?= $r['housekeeping']==='clean'?'green':($r['housekeeping']==='dirty'?'amber':'blue') ?>"><?= e(ucfirst($r['housekeeping'])) ?></span></td>
          <?php if($canManage): ?>
          <td>
            <form method="post" action="<?= e(url('/admin/housekeeping/'.$r['id'])) ?>" style="display:flex;gap:6px;align-items:center">
              <?= csrf_field() ?>
              <select name="status" style="padding:6px 8px;border:1px solid var(--line);border-radius:0;font-size:.82rem"><?php foreach(['available','occupied','cleaning','maintenance'] as $s):?><option <?= $r['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach;?></select>
              <select name="housekeeping" style="padding:6px 8px;border:1px solid var(--line);border-radius:0;font-size:.82rem"><?php foreach(['clean','dirty','inspected'] as $s):?><option <?= $r['housekeeping']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach;?></select>
              <button class="btn btn-primary btn-sm" type="submit"><?= icon('check','',14) ?></button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
