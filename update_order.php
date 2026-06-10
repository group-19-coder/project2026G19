<?php
require_once 'config.php';
requireVendor();

$vendorRestId = getVendorRestaurantId();
$pageTitle    = 'Update Order';
$activePage   = 'orders';

$singleId    = intval($_GET['id'] ?? 0);
$singleOrder = null;
if ($singleId) {
    $scopeWhere = $vendorRestId > 0 ? "AND o.restaurant_id='$vendorRestId'" : "";
    $r = $conn->query("
        SELECT o.*, u.name AS customer_name, u.email AS customer_email,
               r.name AS restaurant_name, r.area AS restaurant_area
        FROM orders o JOIN users u ON o.customer_id=u.id JOIN restaurants r ON o.restaurant_id=r.id
        WHERE o.id='$singleId' $scopeWhere
    ");
    $singleOrder = ($r && $r->num_rows) ? $r->fetch_assoc() : null;
    if (!$singleOrder) { header("Location: orders.php"); exit(); }
}

//  Handle POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staffId = intval($_SESSION['user_id']);
    $updated = 0;

    // Bulk update
    if (!empty($_POST['order_ids']) && !empty($_POST['bulk_status'])) {
        $newStatus = $conn->real_escape_string($_POST['bulk_status']);
        $ids = array_map('intval', $_POST['order_ids']);
        foreach ($ids as $oid) {
            $scopeCheck = $vendorRestId > 0 ? "AND restaurant_id='$vendorRestId'" : "";
            $chk = $conn->query("SELECT status FROM orders WHERE id='$oid' $scopeCheck");
            if ($chk && $chk->num_rows > 0) {
                $old = $chk->fetch_assoc()['status'];
                $conn->query("UPDATE orders SET status='$newStatus' WHERE id='$oid'");
                $conn->query("INSERT INTO order_status_log (order_id,old_status,new_status,changed_by) VALUES ('$oid','$old','$newStatus','$staffId')");
                $updated++;
            }
        }
        $_SESSION['toast'] = "$updated order(s) updated to ".ucfirst($newStatus);
        header("Location: orders.php"); exit();
    }

    // Single order update
    if (!empty($_POST['order_id']) && !empty($_POST['new_status'])) {
        $oid       = intval($_POST['order_id']);
        $newStatus = $conn->real_escape_string($_POST['new_status']);
        $notes     = $conn->real_escape_string(trim($_POST['notes'] ?? ''));
        $scopeCheck = $vendorRestId > 0 ? "AND restaurant_id='$vendorRestId'" : "";
        $chk = $conn->query("SELECT status FROM orders WHERE id='$oid' $scopeCheck");
        if ($chk && $chk->num_rows > 0) {
            $old = $chk->fetch_assoc()['status'];
            $conn->query("UPDATE orders SET status='$newStatus', notes='$notes' WHERE id='$oid'");
            $conn->query("INSERT INTO order_status_log (order_id,old_status,new_status,changed_by) VALUES ('$oid','$old','$newStatus','$staffId')");
            $_SESSION['toast'] = "Order #".str_pad($oid,4,'0',STR_PAD_LEFT)." updated to ".ucfirst($newStatus);
        }
        header("Location: update_order.php?id=$oid"); exit();
    }
}

//  Fetch pending/active orders for bulk view 
$activeWhere = "WHERE o.status NOT IN ('completed','cancelled')";
if ($vendorRestId > 0) $activeWhere .= " AND o.restaurant_id='$vendorRestId'";
$allActive = $conn->query("
    SELECT o.*, u.name AS customer_name, r.name AS restaurant_name, COUNT(oi.id) AS item_count
    FROM orders o JOIN users u ON o.customer_id=u.id JOIN restaurants r ON o.restaurant_id=r.id
    LEFT JOIN order_items oi ON oi.order_id=o.id
    $activeWhere GROUP BY o.id
    ORDER BY FIELD(o.status,'pending','confirmed','preparing','ready'), o.created_at ASC
");

$statusFlow = [
    'pending'   => ['label'=>'Pending',   'color'=>'#fbbf24','bg'=>'rgba(245,158,11,.12)','border'=>'rgba(245,158,11,.25)'],
    'confirmed' => ['label'=>'Confirmed', 'color'=>'#7ba9f8','bg'=>'rgba(37,99,235,.12)', 'border'=>'rgba(37,99,235,.25)'],
    'preparing' => ['label'=>'Preparing', 'color'=>'#c4b5fd','bg'=>'rgba(139,92,246,.12)','border'=>'rgba(139,92,246,.25)'],
    'ready'     => ['label'=>'Ready',     'color'=>'#4ade80','bg'=>'rgba(22,163,74,.12)', 'border'=>'rgba(22,163,74,.25)'],
    'completed' => ['label'=>'Completed', 'color'=>'#e0b96a','bg'=>'rgba(200,150,62,.12)','border'=>'rgba(200,150,62,.25)'],
    'cancelled' => ['label'=>'Cancelled', 'color'=>'#f87171','bg'=>'rgba(220,38,38,.12)', 'border'=>'rgba(220,38,38,.22)'],
];

if (isAdmin()) { require_once 'admin_header.php'; }
else           { require_once 'vendor_header.php'; }
?>

<style>

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


.status-radio-label {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 11px 14px;
  border-radius: 10px;
  border: 1.5px solid var(--glass-border);
  background: transparent;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  transition: all .2s;
}
.status-radio-label:hover {
  background: var(--glass-hover);
  transform: translateY(-1px);
}
.status-radio-label.selected {
  box-shadow: 0 4px 16px rgba(0,0,0,.25);
}
.status-radio-label input[type="radio"] {
  width: 15px; height: 15px;
  flex-shrink: 0;
}


.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid var(--glass-border);
  font-size: 13px;
}
.info-row:last-child { border-bottom: none; }
.info-row-label { color: var(--muted-lt); }
.info-row-value { font-weight: 600; color: rgba(255,255,255,.9); }


input[type="checkbox"] {
  width: 15px; height: 15px;
  accent-color: var(--gold);
  cursor: pointer;
}


.current-status-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-radius: 10px;
  margin-bottom: 18px;
  font-size: 13px;
  font-weight: 600;
}
</style>

<?php if (isset($_SESSION['toast'])): ?>
<div class="toast-container-custom">
  <div class="toast-msg"><?= h($_SESSION['toast']) ?></div>
</div>
<?php unset($_SESSION['toast']); endif; ?>

<?php if ($singleOrder):
  $cur = $statusFlow[$singleOrder['status']] ?? ['label'=>ucfirst($singleOrder['status']),'color'=>'var(--muted-lt)','bg'=>'rgba(255,255,255,.06)','border'=>'rgba(255,255,255,.1)'];
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <p class="page-title">Update Order #<?= str_pad($singleOrder['id'],4,'0',STR_PAD_LEFT) ?></p>
    <p class="page-sub"><?= h($singleOrder['customer_name']) ?> &middot; <?= h($singleOrder['restaurant_name']) ?></p>
  </div>
  <a href="order_detail.php?id=<?= $singleOrder['id'] ?>" class="btn-ghost">View Detail</a>
</div>

<div class="row g-4">

  <div class="col-lg-6">
    <div class="dash-card">
      <div class="dash-card-header">
        <span class="dash-card-title">Change Status</span>
      </div>
      <div class="dash-card-body">
        <form method="POST">
          <input type="hidden" name="order_id" value="<?= $singleOrder['id'] ?>">

         
          <div class="current-status-bar" style="background:<?= $cur['bg'] ?>;border:1.5px solid <?= $cur['border'] ?>;">
            <span style="font-size:11px;color:var(--muted-lt);font-weight:500;">Current:</span>
            <span style="color:<?= $cur['color'] ?>;"><?= $cur['label'] ?></span>
          </div>

        
          <div class="row g-2 mb-4">
            <?php foreach ($statusFlow as $st => $sc): ?>
            <div class="col-6">
              <label class="status-radio-label <?= $singleOrder['status']===$st ? 'selected' : '' ?>"
                     style="<?= $singleOrder['status']===$st ? 'background:'.$sc['bg'].';border-color:'.$sc['border'].';color:'.$sc['color'].';' : 'color:rgba(255,255,255,.6);' ?>">
                <input type="radio" name="new_status" value="<?= $st ?>"
                       <?= $singleOrder['status']===$st ? 'checked' : '' ?>
                       style="accent-color:<?= $sc['color'] ?>;">
                <?= $sc['label'] ?>
              </label>
            </div>
            <?php endforeach; ?>
          </div>

         
          <div class="mb-4">
            <label class="form-label">Notes <span style="color:var(--muted-lt);font-weight:400;">(optional)</span></label>
            <textarea name="notes" class="form-control" rows="3"
              placeholder="Add a note for this status update..."><?= h($singleOrder['notes'] ?? '') ?></textarea>
          </div>

          <button type="submit" class="btn-orderly w-100" style="justify-content:center;">Save Update</button>
        </form>
      </div>
    </div>
  </div>


  <div class="col-lg-6">
    <div class="dash-card">
      <div class="dash-card-header">
        <span class="dash-card-title">Order Info</span>
      </div>
      <div class="dash-card-body">
        <div class="info-row">
          <span class="info-row-label">Order</span>
          <span class="info-row-value" style="color:var(--gold-lt);">#<?= str_pad($singleOrder['id'],4,'0',STR_PAD_LEFT) ?></span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Customer</span>
          <span class="info-row-value"><?= h($singleOrder['customer_name']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Restaurant</span>
          <span class="info-row-value"><?= h($singleOrder['restaurant_name']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Total</span>
          <span class="info-row-value" style="color:var(--gold-lt);font-size:15px;">RM <?= number_format($singleOrder['total_price'],2) ?></span>
        </div>
        <div class="info-row">
          <span class="info-row-label">Placed</span>
          <span class="info-row-value"><?= date('d M Y, H:i', strtotime($singleOrder['created_at'])) ?></span>
        </div>
        <div style="margin-top:20px;">
          <a href="order_detail.php?id=<?= $singleOrder['id'] ?>" class="btn-ghost w-100" style="justify-content:center;">
            View Full Detail
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php else: ?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <p class="page-title">Update Orders</p>
    <p class="page-sub">Select orders and bulk-update their status.</p>
  </div>
  <a href="orders.php" class="btn-ghost">Order List</a>
</div>

<form method="POST" id="bulkForm">
  <div class="dash-card">
    <div class="dash-card-header">
      <span class="dash-card-title">
        Active Orders
        <?php if ($allActive): ?>
        <span class="badge-pill bp-blue" style="margin-left:8px;"><?= $allActive->num_rows ?></span>
        <?php endif; ?>
      </span>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <select name="bulk_status" class="form-select" style="width:auto;min-width:180px;">
          <option value="">Select New Status</option>
          <?php foreach (['confirmed','preparing','ready','completed','cancelled'] as $s): ?>
          <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-orderly"
          onclick="return confirm('Update selected orders?')">Apply</button>
      </div>
    </div>
    <div class="dash-card-body" style="padding:0;">
      <div style="overflow-x:auto;">
        <table class="table w-100" style="margin:0;">
          <thead>
            <tr>
              <th style="padding:12px 20px;width:40px;">
                <input type="checkbox" id="checkAll">
              </th>
              <th>Order #</th>
              <th>Customer</th>
              <?php if (isAdmin()): ?><th>Restaurant</th><?php endif; ?>
              <th>Items</th>
              <th>Total</th>
              <th>Status</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($allActive && $allActive->num_rows > 0):
            while ($ord = $allActive->fetch_assoc()):
              $sc = $statusFlow[$ord['status']] ?? ['label'=>ucfirst($ord['status']),'color'=>'var(--muted-lt)','bg'=>'rgba(255,255,255,.06)','border'=>'var(--glass-border)'];
          ?>
          <tr>
            <td style="padding:12px 20px;">
              <input type="checkbox" name="order_ids[]" value="<?= $ord['id'] ?>">
            </td>
            <td>
              <strong style="color:var(--gold-lt);">#<?= str_pad($ord['id'],4,'0',STR_PAD_LEFT) ?></strong>
            </td>
            <td style="color:rgba(255,255,255,.8);"><?= h($ord['customer_name']) ?></td>
            <?php if (isAdmin()): ?>
            <td style="font-size:12px;color:var(--muted-lt);"><?= h($ord['restaurant_name']) ?></td>
            <?php endif; ?>
            <td style="color:var(--muted-lt);"><?= $ord['item_count'] ?> item<?= $ord['item_count']!=1?'s':'' ?></td>
            <td><strong style="color:rgba(255,255,255,.9);">RM <?= number_format($ord['total_price'],2) ?></strong></td>
            <td>
              <span style="display:inline-flex;align-items:center;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;border:1.5px solid <?= $sc['border'] ?>;">
                <?= $sc['label'] ?>
              </span>
            </td>
            <td style="font-size:12px;color:var(--muted-lt);"><?= date('d M, H:i', strtotime($ord['created_at'])) ?></td>
            <td>
              <a href="update_order.php?id=<?= $ord['id'] ?>" class="btn-edit-sm">Update</a>
            </td>
          </tr>
          <?php endwhile; else: ?>
          <tr>
            <td colspan="9" style="text-align:center;padding:50px;color:var(--muted-lt);">
              No active orders.
            </td>
          </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</form>

<script>
document.getElementById('checkAll')?.addEventListener('change', function() {
  document.querySelectorAll('input[name="order_ids[]"]').forEach(cb => cb.checked = this.checked);
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>