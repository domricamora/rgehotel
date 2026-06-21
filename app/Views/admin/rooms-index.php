<?php /** @var array $rooms */ ?>
<div class="panel">
  <div class="panel__head"><h2>Room Types</h2></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th></th><th>Name</th><th>Price/night</th><th>Sleeps</th><th>Units</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rooms as $r): ?>
        <tr>
          <td style="width:64px"><img src="<?= e(img_url($r['cover'] ?? null,'thumb')) ?>" alt="" style="width:56px;height:42px;object-fit:cover;border-radius:8px"></td>
          <td><strong><?= e($r['name']) ?></strong><div class="muted" style="font-size:.8rem"><?= e($r['slug']) ?></div></td>
          <td><?= money($r['base_price']) ?></td>
          <td><?= (int)$r['max_occupancy'] ?></td>
          <td><?= (int)$r['units'] ?></td>
          <td><?= $r['is_published'] ? badge('available') : '<span class="badge badge-gray">Hidden</span>' ?> <?= $r['is_featured'] ? '<span class="badge badge-blue">Featured</span>' : '' ?></td>
          <td><a class="btn btn-outline btn-sm" href="<?= e(url('/admin/rooms/'.$r['id'])) ?>">Edit</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
