<?php
require_once 'config.php';
requireVendor();

$vendorRestId = getVendorRestaurantId();
$pageTitle  = 'Order Detail';
$activePage = 'orders';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: orders.php"); exit(); }

$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'unpaid'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancellation_reason TEXT DEFAULT NULL");

$scopeWhere = $vendorRestId > 0 ? "AND o.restaurant_id='$vendorRestId'" : "";
$order = $conn->query("
    SELECT o.*, u.name AS customer_name, u.email AS customer_email,
           r.name AS restaurant_name, r.area AS restaurant_area
    FROM orders o
    JOIN users u ON o.customer_id=u.id
    JOIN restaurants r ON o.restaurant_id=r.id
    WHERE o.id='$id' $scopeWhere
")->fetch_assoc();

if (!$order) { header("Location: orders.php"); exit(); }

$items = $conn->query("
    SELECT oi.*, m.name AS item_name, m.image AS item_image, m.category
    FROM order_items oi JOIN menu_items m ON oi.menu_item_id=m.id
    WHERE oi.order_id='$id'
");
$log = $conn->query("
    SELECT l.*, u.name AS staff_name FROM order_status_log l
    LEFT JOIN users u ON l.changed_by=u.id
    WHERE l.order_id='$id' ORDER BY l.changed_at ASC
");

$statusFlow = [
    'pending'   => ['label'=>'Pending',   'color'=>'#fbbf24','bg'=>'rgba(245,158,11,.12)','border'=>'rgba(245,158,11,.25)'],
    'confirmed' => ['label'=>'Confirmed', 'color'=>'#7ba9f8','bg'=>'rgba(37,99,235,.12)', 'border'=>'rgba(37,99,235,.25)'],
    'preparing' => ['label'=>'Preparing', 'color'=>'#c4b5fd','bg'=>'rgba(139,92,246,.12)','border'=>'rgba(139,92,246,.25)'],
    'ready'     => ['label'=>'Ready',     'color'=>'#4ade80','bg'=>'rgba(22,163,74,.12)', 'border'=>'rgba(22,163,74,.25)'],
    'completed' => ['label'=>'Completed', 'color'=>'#e0b96a','bg'=>'rgba(200,150,62,.12)','border'=>'rgba(200,150,62,.25)'],
    'cancelled' => ['label'=>'Cancelled', 'color'=>'#f87171','bg'=>'rgba(220,38,38,.12)', 'border'=>'rgba(220,38,38,.22)'],
];
$sc = $statusFlow[$order['status']] ?? ['label'=>ucfirst($order['status']),'color'=>'var(--muted-lt)','bg'=>'rgba(255,255,255,.06)','border'=>'rgba(255,255,255,.1)'];

if (isAdmin()) { require_once 'admin_header.php'; }
else           { require_once 'vendor_header.php'; }
?>

<style>

.meta-label {
  font-size: 10px;
  font-weight: 700;
  color: var(--muted-lt);
  text-transform: uppercase;
  letter-spacing: .1em;
  margin-bottom: 5px;
}
.meta-value { font-size: 14px; font-weight: 600; color: rgba(255,255,255,.9); }
.meta-sub   { font-size: 12px; color: var(--muted-lt); margin-top: 2px; }

.notes-box {
  background: rgba(37,99,235,.08);
  border-left: 3px solid rgba(37,99,235,.5);
  padding: 12px 16px;
  border-radius: 0 10px 10px 0;
  font-size: 13px;
  color: rgba(255,255,255,.8);
}


.item-thumb {
  width: 40px; height: 40px;
  border-radius: 9px;
  object-fit: cover;
  border: 1px solid var(--glass-border);
}
.item-thumb-placeholder {
  width: 40px; height: 40px;
  border-radius: 9px;
  background: linear-gradient(135deg, rgba(200,150,62,.15) 0%, rgba(30,65,117,.4) 100%);
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
  border: 1px solid var(--glass-border);
}
tfoot td {
  background: rgba(200,150,62,.06);
  border-top: 1px solid rgba(200,150,62,.2) !important;
}
.total-amount {
  font-size: 17px;
  font-weight: 800;
  color: var(--gold-lt);
}


.timeline-dot {
  width: 32px; height: 32px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700;
  flex-shrink: 0;
  border: 2px solid;
}
.timeline-line {
  width: 2px; flex: 1;
  background: var(--glass-border);
  min-height: 14px;
  margin: 3px auto;
}
.timeline-entry {
  display: flex; gap: 14px;
  margin-bottom: 6px;
  animation: fadeUp .4s ease both;
}


.btn-ghost {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 18px;
  background: var(--glass);
  color: rgba(255,255,255,.7);
  border: 1px solid var(--glass-border);
  border-radius: 10px;
  font-family: 'Poppins', sans-serif;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  transition: all .2s;
  backdrop-filter: blur(10px);
}
.btn-ghost:hover {
  color: #fff;
  background: var(--glass-hover);
  transform: translateY(-1px);
}
</style>


<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <p class="page-title">Order #<?= str_pad($order['id'],4,'0',STR_PAD_LEFT) ?></p>
    <p class="page-sub">Placed on <?= date('d M Y, H:i', strtotime($order['created_at'])) ?></p>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <?php if ($order['status'] !== 'cancelled'): ?>
    <a href="update_order.php?id=<?= $order['id'] ?>" class="btn-orderly">Update Status</a>
    <?php else: ?>
    <span class="btn-orderly" style="opacity:.35;cursor:not-allowed;" title="Order was cancelled — no further updates allowed">Update Status</span>
    <?php endif; ?>
    <a href="<?= isAdmin() ? 'orders.php' : 'my_orders.php' ?>" class="btn-ghost">Back</a>
  </div>
</div>

<div class="row g-4">
 
  <div class="col-lg-8">

    
    <div class="dash-card mb-4">
      <div class="dash-card-header">
        <span class="dash-card-title">Order Summary</span>
        <span style="display:inline-flex;align-items:center;padding:5px 16px;border-radius:20px;font-size:12px;font-weight:700;background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;border:1.5px solid <?= $sc['border'] ?>;">
          <?= $sc['label'] ?>
        </span>
      </div>
      <div class="dash-card-body">
        <div class="row g-4">
          <div class="col-sm-6">
            <p class="meta-label">Customer</p>
            <p class="meta-value"><?= h($order['customer_name']) ?></p>
            <p class="meta-sub"><?= h($order['customer_email']) ?></p>
          </div>
          <div class="col-sm-6">
            <p class="meta-label">Restaurant</p>
            <p class="meta-value"><?= h($order['restaurant_name']) ?></p>
            <p class="meta-sub"><?= h($order['restaurant_area']) ?></p>
          </div>
          <?php if ($order['notes']): ?>
          <div class="col-12">
            <p class="meta-label">Notes</p>
            <div class="notes-box"><?= h($order['notes']) ?></div>
          </div>
          <?php endif; ?>
          <?php if ($order['status'] === 'cancelled' && !empty($order['cancellation_reason'])): ?>
          <div class="col-12">
            <p class="meta-label">Cancellation Reason</p>
            <div style="display:inline-flex;align-items:flex-start;gap:9px;background:rgba(248,113,113,.07);border:1px solid rgba(248,113,113,.2);border-left:3px solid rgba(248,113,113,.6);padding:11px 15px;border-radius:0 10px 10px 0;font-size:13px;color:#f87171;max-width:100%;">
              <svg style="flex-shrink:0;margin-top:1px;" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <?= h($order['cancellation_reason']) ?>
            </div>
          </div>
          <?php endif; ?>
          <div class="col-sm-6">
            <p class="meta-label">Payment Status</p>
            <?php
              $ps = $order['payment_status'] ?? 'unpaid';
              $pm = $order['payment_method'] ?? '';
              if ($ps === 'paid') {
                echo '<p class="meta-value" style="color:#4ade80;">Paid</p>';
              } elseif ($pm === 'cash') {
                echo '<p class="meta-value" style="color:#fbbf24;">💵 Cash on Delivery</p>';
              } else {
                echo '<p class="meta-value" style="color:#f87171;">Unpaid</p>';
              }
            ?>
          </div>
          <div class="col-sm-6">
            <p class="meta-label">Payment Method</p>
            <?php
              $label = '—';
              if ($pm === 'cash')  $label = 'Cash on Delivery';
              elseif ($pm === 'card') $label = '💳 Credit/Debit Card';
              elseif (str_starts_with($pm,'fpx_'))     $label = '🏦 FPX · '.strtoupper(substr($pm,4));
              elseif (str_starts_with($pm,'ewallet_')) {
                $map = ['tng'=>"Touch 'n Go",'grab'=>'GrabPay','boost'=>'Boost','maybankqr'=>'MAE QRPay'];
                $key = substr($pm,8);
                $label = '📱 '.($map[$key] ?? strtoupper($key));
              }
            ?>
            <p class="meta-value"><?= h($label) ?></p>
          </div>
        </div>
      </div>
    </div>

   
    <div class="dash-card">
      <div class="dash-card-header">
        <span class="dash-card-title">Items Ordered</span>
      </div>
      <div class="dash-card-body" style="padding:0;">
        <div style="overflow-x:auto;">
          <table class="table" style="margin:0;">
            <thead>
              <tr>
                <th style="padding:12px 20px;">Item</th>
                <th>Category</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
            <?php while ($it = $items->fetch_assoc()): ?>
            <tr>
              <td style="padding:12px 20px;">
                <div style="display:flex;align-items:center;gap:12px;">
                  <?php if ($it['item_image'] && file_exists('uploads/food_images/'.$it['item_image'])): ?>
                  <img src="uploads/food_images/<?= h($it['item_image']) ?>" class="item-thumb">
                  <?php else: ?>
                  <div class="item-thumb-placeholder"></div>
                  <?php endif; ?>
                  <strong style="color:rgba(255,255,255,.9);"><?= h($it['item_name']) ?></strong>
                </div>
              </td>
              <td><span class="badge-pill bp-blue"><?= h($it['category']) ?></span></td>
              <td style="color:var(--muted-lt);">x <?= $it['quantity'] ?></td>
              <td style="color:rgba(255,255,255,.75);">RM <?= number_format($it['unit_price'],2) ?></td>
              <td><strong style="color:rgba(255,255,255,.9);">RM <?= number_format($it['subtotal'],2) ?></strong></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="4" style="padding:14px 20px;font-weight:700;text-align:right;color:var(--muted-lt);">Total</td>
                <td style="padding:14px 20px;" class="total-amount">RM <?= number_format($order['total_price'],2) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

  </div>

 
  <div class="col-lg-4">
    <div class="dash-card">
      <div class="dash-card-header">
        <span class="dash-card-title">Status Timeline</span>
      </div>
      <div class="dash-card-body">
        <?php if ($log->num_rows > 0):
          $entries = [];
          while ($l = $log->fetch_assoc()) $entries[] = $l;
          foreach ($entries as $idx => $l):
            $lc = $statusFlow[$l['new_status']] ?? ['label'=>ucfirst($l['new_status']),'color'=>'var(--muted-lt)','bg'=>'rgba(255,255,255,.06)','border'=>'rgba(255,255,255,.1)'];
            $isLast = ($idx === count($entries) - 1);
        ?>
        <div class="timeline-entry" style="animation-delay:<?= $idx * .07 ?>s;">
          <div style="display:flex;flex-direction:column;align-items:center;">
            <div class="timeline-dot" style="background:<?= $lc['bg'] ?>;border-color:<?= $lc['border'] ?>;color:<?= $lc['color'] ?>;">
              <?= strtoupper(substr($l['new_status'],0,1)) ?>
            </div>
            <?php if (!$isLast): ?><div class="timeline-line"></div><?php endif; ?>
          </div>
          <div style="padding-top:4px;padding-bottom:<?= $isLast ? '0' : '14px' ?>;">
            <p style="font-size:13px;font-weight:700;color:<?= $lc['color'] ?>;margin-bottom:2px;"><?= ucfirst($l['new_status']) ?></p>
            <p style="font-size:11px;color:var(--muted-lt);margin-bottom:1px;">by <?= h($l['staff_name'] ?? 'System') ?></p>
            <p style="font-size:11px;color:var(--muted-lt);"><?= date('d M Y, H:i', strtotime($l['changed_at'])) ?></p>
            <?php if ($l['new_status'] === 'cancelled' && !empty($order['cancellation_reason'])): ?>
            <p style="font-size:11px;color:#f87171;margin-top:4px;display:flex;align-items:center;gap:4px;">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <?= h(mb_strimwidth($order['cancellation_reason'], 0, 48, '…')) ?>
            </p>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; else: ?>
        <p style="font-size:13px;color:var(--muted-lt);text-align:center;padding:24px 0;">No changes logged yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<?php require_once 'includes/footer.php'; ?>