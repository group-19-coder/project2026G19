<?php
require_once 'config.php';
requireVendor();


$vendorRestId = getVendorRestaurantId();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
    $orderId   = intval($_POST['order_id']);
    $newStatus = $conn->real_escape_string($_POST['new_status']);
    $staffId   = intval($_SESSION['user_id']);

    $scopeCheck = $vendorRestId > 0 ? "AND restaurant_id='$vendorRestId'" : "";
    $check = $conn->query("SELECT status FROM orders WHERE id='$orderId' $scopeCheck");
    if ($check && $check->num_rows > 0) {
        $old = $check->fetch_assoc()['status'];
        $conn->query("UPDATE orders SET status='$newStatus' WHERE id='$orderId'");
        $conn->query("INSERT INTO order_status_log (order_id,old_status,new_status,changed_by) VALUES ('$orderId','$old','$newStatus','$staffId')");
        $_SESSION['toast'] = "Order #$orderId updated to ".ucfirst($newStatus);
    }
    header("Location: orders.php".($_SERVER['QUERY_STRING']?'?'.$_SERVER['QUERY_STRING']:''));
    exit();
}


$fStatus = $conn->real_escape_string($_GET['status']      ?? '');
$fRest   = $vendorRestId > 0 ? $vendorRestId : intval($_GET['restaurant'] ?? 0);
$fDate   = $conn->real_escape_string($_GET['date']        ?? '');

$where = "WHERE 1=1";
if ($vendorRestId > 0) $where .= " AND o.restaurant_id='$vendorRestId'";
elseif ($fRest)        $where .= " AND o.restaurant_id='$fRest'";
if ($fStatus)          $where .= " AND o.status='$fStatus'";
if ($fDate)            $where .= " AND DATE(o.created_at)='$fDate'";

$orders = $conn->query("
    SELECT o.*, u.name AS customer_name, u.email AS customer_email,
           r.name AS restaurant_name, r.area AS restaurant_area,
           COUNT(oi.id) AS item_count
    FROM orders o
    JOIN users u ON o.customer_id=u.id
    JOIN restaurants r ON o.restaurant_id=r.id
    LEFT JOIN order_items oi ON oi.order_id=o.id
    $where GROUP BY o.id
    ORDER BY FIELD(o.status,'pending','confirmed','preparing','ready','completed','cancelled'), o.created_at DESC
");


$statsWhere = $vendorRestId > 0 ? "WHERE restaurant_id='$vendorRestId'" : '';
$stats = [];
$sr = $conn->query("SELECT status, COUNT(*) AS cnt FROM orders $statsWhere GROUP BY status");
while ($row = $sr->fetch_assoc()) $stats[$row['status']] = $row['cnt'];
$revenueWhere = $vendorRestId > 0 ? "WHERE status='completed' AND restaurant_id='$vendorRestId'" : "WHERE status='completed'";
$totalRevenue = $conn->query("SELECT COALESCE(SUM(total_price),0) FROM orders $revenueWhere")->fetch_row()[0] ?? 0;

$restaurants = isAdmin() ? $conn->query("SELECT id, name FROM restaurants ORDER BY name") : null;

$pageTitle  = isAdmin() ? 'All Orders' : 'My Orders';
$activePage = 'orders';
if (isAdmin()) { require_once 'admin_header.php'; }
else           { require_once 'vendor_header.php'; }
?>

<?php if (isset($_SESSION['toast'])): ?>
<div class="toast-container-custom"><div class="toast-msg success"><?= h($_SESSION['toast']) ?></div></div>
<?php unset($_SESSION['toast']); endif; ?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <p class="page-title"><?= isAdmin() ? 'All Orders' : 'My Orders' ?></p>
    <p class="page-sub"><?= isAdmin() ? 'Manage all orders across all restaurants.' : 'Orders for your restaurant.' ?></p>
  </div>
  <a href="update_order.php" class="btn-orderly">Bulk Update</a>
</div>

<div class="row g-3 mb-4">
  <?php
  $statCards = [
    ['Pending','pending','#e67e22'],['Preparing','preparing','#2980b9'],
    ['Ready','ready','#27ae60'],['Completed','completed','#7494ec'],
    ['Cancelled','cancelled','#c0392b'],['Revenue (RM)','_revenue','#27ae60'],
  ];
  foreach ($statCards as [$lbl,$key,$color]):
    $val = $key==='_revenue' ? 'RM '.number_format($totalRevenue,2) : ($stats[$key]??0);
  ?>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card" style="border-top:2px solid <?= $color ?>;">
      <div class="stat-val" style="color:<?= $color ?>;font-size:18px;"><?= $val ?></div>
      <div class="stat-lbl"><?= $lbl ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<form method="GET" class="filter-bar mb-3">
  <select name="status" class="form-select" style="max-width:160px;">
    <option value="">All Statuses</option>
    <?php foreach (['pending','confirmed','preparing','ready','completed','cancelled'] as $s): ?>
    <option value="<?= $s ?>" <?= $fStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if (isAdmin()): ?>
  <select name="restaurant" class="form-select" style="max-width:180px;">
    <option value="">All Restaurants</option>
    <?php while ($r = $restaurants->fetch_assoc()): ?>
    <option value="<?= $r['id'] ?>" <?= $fRest==$r['id']?'selected':'' ?>><?= h($r['name']) ?></option>
    <?php endwhile; ?>
  </select>
  <?php endif; ?>
  <input type="date" name="date" class="form-control" value="<?= h($fDate) ?>" style="max-width:170px;"/>
  <button type="submit" class="btn-orderly">Filter</button>
  <a href="orders.php" style="padding:8px 14px;background:transparent;color:var(--muted);border:1.5px solid var(--border);border-radius:8px;font-size:13px;text-decoration:none;">Reset</a>
</form>

<div class="dash-card">
  <div class="dash-card-body" style="padding:0;">
    <div style="overflow-x:auto;">
      <table class="table w-100">
        <thead>
          <tr>
            <th>Order #</th><th>Customer</th>
            <?php if (isAdmin()): ?><th>Restaurant</th><?php endif; ?>
            <th>Items</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($orders && $orders->num_rows > 0):
          while ($order = $orders->fetch_assoc()):
            $sCfg=['pending'=>['Pending','status-pending'], 'confirmed'=>['Confirmed','status-confirmed'], 'preparing'=>['Preparing','status-preparing'], 'ready'=>['Ready','status-ready'], 'completed'=>['Completed','status-completed'], 'cancelled'=>['Cancelled','status-cancelled']];
            [$sLbl,$sCls]=$sCfg[$order['status']]??[$order['status'],''];
        ?>
        <tr>
          <td><strong style="color:#7494ec;">#<?= str_pad($order['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
          <td>
            <div style="font-size:13px;font-weight:600;"><?= h($order['customer_name']) ?></div>
            <div style="font-size:11px;color:var(--muted);"><?= h($order['customer_email']) ?></div>
          </td>
          <?php if (isAdmin()): ?><td style="font-size:12px;"><?= h($order['restaurant_name']) ?><br><span style="color:var(--muted);">Area: <?= h($order['restaurant_area']) ?></span></td><?php endif; ?>
          <td><span class="badge-pill bp-blue"><?= $order['item_count'] ?> item<?= $order['item_count']!=1?'s':'' ?></span></td>
          <td><strong>RM <?= number_format($order['total_price'],2) ?></strong></td>
          <td><span class="order-status-badge <?= $sCls ?>"><?= $sLbl ?></span></td>
          <td style="font-size:12px;color:var(--muted);"><?= date('d M Y\nH:i',strtotime($order['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <a href="update_order.php?id=<?= $order['id'] ?>" class="btn-edit-sm">Update</a>
              <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn-edit-sm" style="opacity:.7;">View</a>
            </div>
          </td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="9" style="text-align:center;padding:60px;color:var(--muted);"><p>No orders found.</p></td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
.order-status-badge{display:inline-flex;align-items:center;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;border:1.5px solid transparent;}
.status-pending{background:#fff8e8;color:#d4a017;border-color:#f0d58c;}
.status-confirmed{background:#e8f4ff;color:#2980b9;border-color:#a8d4f0;}
.status-preparing{background:#e8f0ff;color:#5a7bd8;border-color:#b0c4f8;}
.status-ready{background:#e8fff2;color:#27ae60;border-color:#a0e0bc;}
.status-completed{background:#eef1fc;color:#7494ec;border-color:#c9d6ff;}
.status-cancelled{background:#fdf0f0;color:#c0392b;border-color:#f0b8b8;}
</style>

<?php require_once 'includes/footer.php'; ?>