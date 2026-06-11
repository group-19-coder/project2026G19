<?php

require_once 'config.php';
requireVendor();
if (isAdmin()) { header("Location: orders.php"); exit(); }

$vendorRestId = getVendorRestaurantId();
if (!$vendorRestId) {
    $pageTitle = 'My Orders';
    $activePage = 'orders';
    require_once 'vendor_header.php';
    echo '<div class="saas-glass-card" style="text-align:center; padding:60px; max-width:600px; margin:40px auto; background:rgba(255, 255, 255, 0.02); border:1px dashed rgba(200,150,62,0.25);"><p style="font-family:\'Space Grotesk\', sans-serif; font-size:14px; font-weight:700; color:var(--gold-lt); text-transform:uppercase; letter-spacing:0.05em; margin:0 0 12px 0;">Alert</p><p style="margin:0; font-family:\'Poppins\',sans-serif; font-weight:500; color:var(--cream-dk);">No restaurant assigned. Contact admin.</p></div>';
    require_once 'includes/footer.php';
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
    $orderId   = intval($_POST['order_id']);
    $newStatus = $conn->real_escape_string($_POST['new_status']);
    $staffId   = intval($_SESSION['user_id']);

    
    $check = $conn->query("SELECT id, status FROM orders WHERE id='$orderId' AND restaurant_id='$vendorRestId'");
    if ($check && $check->num_rows > 0) {
        $old = $check->fetch_assoc()['status'];
        $conn->query("UPDATE orders SET status='$newStatus' WHERE id='$orderId'");
        $conn->query("INSERT INTO order_status_log (order_id,old_status,new_status,changed_by) VALUES ('$orderId','$old','$newStatus','$staffId')");
        $_SESSION['toast'] = "Order #".str_pad($orderId,4,'0',STR_PAD_LEFT)." updated to ".ucfirst($newStatus);
    }
    header("Location: my_orders.php" . ($_GET ? '?'.http_build_query($_GET) : ''));
    exit();
}


$fStatus = $conn->real_escape_string($_GET['status'] ?? '');
$fDate   = $conn->real_escape_string($_GET['date']   ?? '');

$where = "WHERE o.restaurant_id='$vendorRestId'";
if ($fStatus) $where .= " AND o.status='$fStatus'";
if ($fDate)   $where .= " AND DATE(o.created_at)='$fDate'";

$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'unpaid'");

$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancellation_reason TEXT DEFAULT NULL");

$orders = $conn->query("
    SELECT o.*, u.name AS customer_name, u.email AS customer_email,
           COUNT(oi.id) AS item_count
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    $where
    GROUP BY o.id
    ORDER BY FIELD(o.status,'pending','confirmed','preparing','ready','completed','cancelled'), o.created_at DESC
");

// Stats
$stats = [];
$sr = $conn->query("SELECT status, COUNT(*) AS cnt FROM orders WHERE restaurant_id='$vendorRestId' GROUP BY status");
while ($row = $sr->fetch_assoc()) $stats[$row['status']] = $row['cnt'];
$totalRevenue = $conn->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE restaurant_id='$vendorRestId' AND status='completed'")->fetch_row()[0] ?? 0;

$pageTitle  = 'My Orders';
$activePage = 'orders';
require_once 'vendor_header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap');

:root {
  --navy: #0b1f3a;
  --navy-md: #132d52;
  --navy-lt: #1e4175;

  --gold: #c8963e;
  --gold-lt: #e0b96a;

  --cream: #f7f4ef;
  --cream-dk: #ede8e0;

  --text: #1a2535;
  --muted: #5a6a80;

  --success: #16a34a;
  --danger: #dc2626;
  --warning: #f59e0b;
  --info: #2563eb;
  

  --glass-bg: rgba(255, 255, 255, 0.03);
  --glass-border: rgba(255, 255, 255, 0.07);
  --glass-input-bg: rgba(11, 31, 58, 0.4);
}

body {
  background: radial-gradient(circle at 0% 0%, #112746 0%, #061324 70%) !important;
  font-family: 'Poppins', sans-serif !important;
  color: var(--cream);
}

.animate-fadeUp {
  animation: saasElementFadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes saasElementFadeUp {
  0% { opacity: 0; transform: translateY(20px); filter: blur(5px); }
  100% { opacity: 1; transform: translateY(0); filter: blur(0); }
}


.saas-glass-card {
  background: var(--glass-bg);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid var(--glass-border);
  border-radius: 18px;
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
  transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.3s ease, border-color 0.3s ease;
  overflow: hidden;
}

.saas-glass-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.6);
  border-color: rgba(200, 150, 62, 0.2);
}


.saas-stat-card {
  background: var(--glass-bg);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid var(--glass-border);
  border-radius: 16px;
  padding: 20px 18px;
  box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.4);
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.saas-stat-card:hover {
  transform: translateY(-4px);
  border-color: rgba(255,255,255,0.15);
  box-shadow: 0 12px 30px -5px rgba(0, 0, 0, 0.5);
}

.saas-input {
  background: var(--glass-input-bg) !important;
  border: 1.5px solid var(--glass-border) !important;
  color: var(--cream) !important;
  font-family: 'Poppins', sans-serif;
  font-size: 13px;
  padding: 10px 14px;
  border-radius: 10px;
  transition: all 0.25s ease !important;
}

.saas-input:focus {
  background: rgba(11, 31, 58, 0.7) !important;
  border-color: var(--gold) !important;
  box-shadow: 0 0 0 4px rgba(200, 150, 62, 0.15) !important;
  outline: none;
}


.btn-saas-primary {
  background: linear-gradient(135deg, var(--gold) 0%, #b38130 100%);
  color: #0b1f3a !important;
  border: none;
  font-family: 'Poppins', sans-serif;
  font-size: 13px;
  font-weight: 600;
  padding: 10px 24px;
  border-radius: 10px;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(200, 150, 62, 0.2);
  transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.btn-saas-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(200, 150, 62, 0.35);
  opacity: 0.95;
}

.btn-saas-secondary {
  background: rgba(255, 255, 255, 0.03);
  color: var(--cream-dk) !important;
  border: 1.5px solid var(--glass-border);
  font-family: 'Poppins', sans-serif;
  font-size: 13px;
  font-weight: 500;
  padding: 9px 20px;
  border-radius: 10px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s ease;
}

.btn-saas-secondary:hover {
  background: rgba(255, 255, 255, 0.07);
  border-color: var(--gold);
  color: var(--cream) !important;
}


.saas-table-row td { 
  background: transparent !important;
  border-bottom: 1px solid rgba(255,255,255,0.04) !important;
  transition: background 0.2s ease; 
}
.saas-table-row:hover td { 
  background: rgba(30, 65, 117, 0.2) !important; 
}


.order-status-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  font-family: 'Poppins', sans-serif;
  white-space: nowrap;
  border: 1px solid transparent;
}
.status-pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: rgba(245, 158, 11, 0.25); }
.status-confirmed { background: rgba(37, 99, 235, 0.1); color: #60a5fa; border-color: rgba(37, 99, 235, 0.25); }
.status-preparing { background: rgba(168, 85, 247, 0.1); color: #c084fc; border-color: rgba(168, 85, 247, 0.25); }
.status-ready { background: rgba(22, 163, 74, 0.1); color: #4ade80; border-color: rgba(22, 163, 74, 0.25); }
.status-completed { background: rgba(21, 128, 61, 0.12); color: #22c55e; border-color: rgba(21, 128, 61, 0.3); }
.status-cancelled { background: rgba(220, 38, 36, 0.1); color: #f87171; border-color: rgba(220, 38, 36, 0.25); }

.cancel-reason-note {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 11px; color: #f87171;
  background: rgba(248,113,113,.07);
  border: 1px solid rgba(248,113,113,.18);
  border-radius: 8px; padding: 5px 10px; margin-top: 6px;
  max-width: 100%;
}

.saas-toast-container {
  position: fixed; top: 24px; right: 24px; z-index: 9999;
}
.saas-toast {
  background: rgba(11, 31, 58, 0.85); backdrop-filter: blur(12px); border-left: 4px solid var(--gold);
  color: var(--cream); font-family: 'Poppins', sans-serif; font-size: 13.5px; padding: 14px 24px;
  border-radius: 0 10px 10px 0; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border); border-right: 1px solid var(--glass-border);
}
</style>

<?php if (isset($_SESSION['toast'])): ?>
<div class="saas-toast-container"><div class="saas-toast"><?= h($_SESSION['toast']) ?></div></div>
<?php unset($_SESSION['toast']); endif; ?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 animate-fadeUp">
  <div>
    <p class="page-title" style="font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:28px; color:var(--cream); margin:0; letter-spacing:-0.02em;">My Orders</p>
    <p class="page-sub" style="font-family:'Poppins',sans-serif; font-size:13.5px; color:var(--muted); margin-top:6px;">Orders for your restaurant only.</p>
  </div>
</div>

<div class="row g-3 mb-4 animate-fadeUp" style="animation-delay: 0.1s;">
  <?php
  $sc = [
    ['Pending',   'pending',   '#f59e0b'],
    ['Preparing', 'preparing', '#a855f7'],
    ['Ready',     'ready',     '#16a34a'],
    ['Completed', 'completed', '#15803d'],
    ['Cancelled', 'cancelled', '#dc2626'],
    ['Revenue',   '_revenue',  '#c8963e'],
  ];
  foreach ($sc as [$lbl, $key, $color]):
    $val = $key === '_revenue' ? 'RM '.number_format($totalRevenue,2) : ($stats[$key] ?? 0);
  ?>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="saas-stat-card" style="border-top: 3px solid <?= $color ?>;">
      <div class="stat-val" style="color:<?= $color ?>; font-size:20px; font-family:'Space Grotesk',sans-serif; font-weight:700;"><?= $val ?></div>
      <div class="stat-lbl" style="font-family:'Poppins',sans-serif; font-size:12px; color:var(--muted); font-weight:500; margin-top:4px;"><?= $lbl ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<form method="GET" class="animate-fadeUp" style="animation-delay: 0.2s; background:rgba(255,255,255,0.01); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); border:1px solid var(--glass-border); padding:16px; border-radius:14px; display:flex; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom: 24px;">
  <select name="status" class="form-select saas-input" style="max-width:180px;">
    <option value="" style="background:var(--navy); color:var(--muted);">All Statuses</option>
    <?php foreach (['pending','confirmed','preparing','ready','completed','cancelled'] as $s): ?>
    <option value="<?= $s ?>" <?= $fStatus===$s?'selected':'' ?> style="background:var(--navy); color:var(--cream);"><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="date" name="date" class="form-control saas-input" value="<?= h($fDate) ?>" style="max-width:180px;"/>
  <button type="submit" class="btn-saas-primary">Filter</button>
  <a href="my_orders.php" class="btn-saas-secondary">Reset</a>
</form>

<div class="saas-glass-card animate-fadeUp" style="animation-delay: 0.3s;">
  <div class="dash-card-body" style="padding:0;">
    <div style="overflow-x:auto;">
      <table class="table w-100" style="margin:0; border-collapse:collapse; background:transparent;">
        <thead>
          <tr>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Order #</th>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Customer</th>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Items</th>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Total</th>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Status</th>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Date</th>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Payment</th>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($orders && $orders->num_rows > 0):
          while ($order = $orders->fetch_assoc()):
            $sCfg = [
              'pending'=>['Pending','status-pending'],
              'confirmed'=>['Confirmed','status-confirmed'],
              'preparing'=>['Preparing','status-preparing'],
              'ready'=>['Ready','status-ready'],
              'completed'=>['Completed','status-completed'],
              'cancelled'=>['Cancelled','status-cancelled']
            ];
            [$sLbl,$sCls] = $sCfg[$order['status']] ?? [$order['status'],''];
        ?>
        <tr class="saas-table-row">
          <td style="padding:16px 24px;"><strong style="color:var(--gold-lt); font-family:monospace; font-size:14px;">#<?= str_pad($order['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
          <td style="padding:16px 24px;">
            <div style="font-family:'Poppins',sans-serif; font-size:13px; font-weight:600; color:var(--cream);"><?= h($order['customer_name']) ?></div>
            <div style="font-family:'Poppins',sans-serif; font-size:11px; color:var(--muted); margin-top:2px;"><?= h($order['customer_email']) ?></div>
            <?php if ($order['status'] === 'cancelled' && !empty($order['cancellation_reason'])): ?>
            <div class="cancel-reason-note">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <?= h($order['cancellation_reason']) ?>
            </div>
            <?php endif; ?>
          </td>
          <td style="padding:16px 24px;"><span class="badge-pill bp-blue" style="background:rgba(37,99,235,0.08); border:1px solid rgba(37,99,235,0.2); color:#93c5fd; padding:3px 10px; border-radius:6px; font-family:'Poppins',sans-serif; font-size:11px; font-weight:500;"><?= $order['item_count'] ?> item<?= $order['item_count']!=1?'s':'' ?></span></td>
          <td style="padding:16px 24px;"><strong style="color:var(--cream); font-family:'Poppins',sans-serif;">RM <?= number_format($order['total_price'],2) ?></strong></td>
          <td style="padding:16px 24px;"><span class="order-status-badge <?= $sCls ?>"><?= $sLbl ?></span></td>
          <td style="padding:16px 24px; color:var(--muted); font-family:'Poppins',sans-serif; font-size:12px;"><?= date('d M Y, H:i',strtotime($order['created_at'])) ?></td>
          <td style="padding:16px 24px;">
            <?php
              $pm = $order['payment_method'] ?? '';
              $ps = $order['payment_status'] ?? 'unpaid';
              if ($ps === 'paid') {
                echo '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;background:rgba(74,222,128,.1);color:#4ade80;border:1px solid rgba(74,222,128,.25);">✓ Paid</span>';
              } elseif ($pm === 'cash') {
                echo '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;background:rgba(245,158,11,.1);color:#fbbf24;border:1px solid rgba(245,158,11,.25);">💵 Cash</span>';
              } else {
                echo '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;background:rgba(248,113,113,.1);color:#f87171;border:1px solid rgba(248,113,113,.25);">Unpaid</span>';
              }
            ?>
          </td>
          <td style="padding:16px 24px;">
            <div style="display:flex; gap:6px; flex-wrap:wrap;">
              <?php if ($order['status'] !== 'cancelled'): ?>
              <a href="update_order.php?id=<?= $order['id'] ?>" class="btn-saas-secondary" style="padding:5px 12px; font-size:11px; font-weight:600; border-color:rgba(200,150,62,0.4); color:var(--gold-lt) !important;">Update</a>
              <?php else: ?>
              <span style="padding:5px 12px; font-size:11px; font-weight:600; border:1px solid rgba(255,255,255,.08); border-radius:8px; color:rgba(255,255,255,.2); cursor:not-allowed;">Update</span>
              <?php endif; ?>
              <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn-saas-secondary" style="padding:5px 12px; font-size:11px; background:rgba(255,255,255,0.02);">View</a>
            </div>
          </td>
        </tr>
        <?php endwhile; else: ?>
        <tr>
          <td colspan="8" style="text-align:center; padding:60px 24px; color:var(--muted); font-family:'Poppins',sans-serif; font-size:13px;">
            <div style="font-size:24px; color:var(--muted); margin-bottom:12px; font-family:'Space Grotesk',sans-serif; font-weight:700; text-transform:uppercase; letter-spacing:0.05em;">Empty</div>
            <p style="margin:0; font-weight:500;">No orders found.</p>
          </td>
        </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>