<?php /** @var string $entity @var array $rows @var string $label */ ?>
<div class="panel">
  <div class="panel__head"><h2><?= e($label) ?>s</h2><a class="btn btn-primary btn-sm" href="<?= e(url('/admin/'.$entity.'/new')) ?>"><?= icon('check') ?> New <?= e($label) ?></a></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Title</th><?php if($entity!=='offers'):?><th>Price</th><?php else:?><th>Discount</th><?php endif;?><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="4" class="muted">None yet.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><strong><?= e($r['name'] ?? $r['title']) ?></strong><div class="muted" style="font-size:.8rem"><?= e($r['summary'] ?? $r['subtitle'] ?? '') ?></div></td>
          <?php if ($entity !== 'offers'): ?>
            <td><?= isset($r['price']) && $r['price'] ? money($r['price']) : '—' ?></td>
          <?php else: ?>
            <td><?= $r['discount_type']==='percent' ? (int)$r['discount_value'].'%' : money($r['discount_value']) ?> <span class="muted">(<?= e($r['code']) ?>)</span></td>
          <?php endif; ?>
          <td><?= $r['is_published'] ? badge('confirmed') : '<span class="badge badge-gray">Hidden</span>' ?> <?= !empty($r['is_featured']) ? '<span class="badge badge-blue">Featured</span>' : '' ?></td>
          <td><a class="btn btn-outline btn-sm" href="<?= e(url('/admin/'.$entity.'/'.$r['id'])) ?>">Edit</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
