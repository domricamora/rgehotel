<?php /** @var array $reviews */ ?>
<div class="panel">
  <div class="panel__head"><h2>Reviews</h2></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Author</th><th>Rating</th><th>Review</th><th>Subject</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if (!$reviews): ?><tr><td colspan="6" class="muted">No reviews.</td></tr><?php endif; ?>
        <?php foreach ($reviews as $r): ?>
        <tr>
          <td><strong><?= e($r['author_name']) ?></strong><div class="muted" style="font-size:.8rem"><?= e($r['author_country']) ?></div></td>
          <td><?= stars($r['rating'],14) ?></td>
          <td style="max-width:320px"><strong><?= e($r['title']) ?></strong><div class="muted" style="font-size:.85rem"><?= e(mb_strimwidth($r['body'],0,90,'…')) ?></div></td>
          <td><?= $r['subject_type']==='room_type' ? e($r['room_name'] ?? 'Room') : 'Hotel' ?></td>
          <td><?= $r['is_approved'] ? badge('confirmed') : '<span class="badge badge-amber">Pending</span>' ?></td>
          <td>
            <form method="post" action="<?= e(url('/admin/reviews/'.$r['id'])) ?>" class="row-actions">
              <?= csrf_field() ?>
              <?php if (!$r['is_approved']): ?><button class="btn btn-primary btn-sm" name="action" value="approve"><?= icon('check','',14) ?> Approve</button>
              <?php else: ?><button class="btn btn-outline btn-sm" name="action" value="unapprove">Unpublish</button><?php endif; ?>
              <button class="btn btn-danger btn-sm" name="action" value="delete" onclick="return confirm('Delete this review?')"><?= icon('x','',14) ?></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
