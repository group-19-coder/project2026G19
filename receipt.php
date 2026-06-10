<?php
require_once 'config.php';
requireCustomer();

$customerId = intval($_SESSION['user_id']);
$orderId    = intval($_GET['id'] ?? 0);
$download   = isset($_GET['download']);

if (!$orderId) { header('Location: my_orders_customer.php'); exit; }

$order = $conn->query("
    SELECT o.*, r.name AS rest_name, r.area, r.address,
           u.name AS customer_name, u.email AS customer_email
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    JOIN users u ON o.customer_id = u.id
    WHERE o.id='$orderId' AND o.customer_id='$customerId'
")->fetch_assoc();

if (!$order) { header('Location: my_orders_customer.php'); exit; }

$orderItems = $conn->query("
    SELECT oi.*, m.name AS item_name, m.category
    FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id
    WHERE oi.order_id = '$orderId'
");
$itemsList = [];
while ($r = $orderItems->fetch_assoc()) $itemsList[] = $r;

$statusCfg = [
    'pending'   => ['Pending',   '#f59e0b'],
    'confirmed' => ['Confirmed', '#60a5fa'],
    'preparing' => ['Preparing', '#fb923c'],
    'ready'     => ['Ready',     '#4ade80'],
    'completed' => ['Completed', '#c8963e'],
    'cancelled' => ['Cancelled', '#f87171'],
];
[$sLabel,$sColor] = $statusCfg[$order['status']] ?? ['·','#94a3b8'];
$pageTitle = 'Receipt #'.str_pad($orderId,4,'0',STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Orderly — <?= h($pageTitle) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<style>

:root {
  --navy:     #0b1f3a;
  --navy-md:  #132d52;
  --navy-lt:  #1e4175;
  --gold:     #c8963e;
  --gold-lt:  #e0b96a;
  --gold-dim: rgba(200,150,62,.15);
  --cream:    #f7f4ef;
  --muted:    #5a6a80;
  --glass-bg:     rgba(255,255,255,.055);
  --glass-border: rgba(255,255,255,.10);
  --shadow-md: 0 8px 40px rgba(0,0,0,.45);
}

*,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Poppins', sans-serif;
  min-height: 100vh;
  background: var(--navy);
  color: #e2e8f4;
  padding: 32px 20px;
}


body::before {
  content: '';
  position: fixed; inset: 0; z-index: -1;
  background:
    radial-gradient(ellipse 60% 50% at 20% 15%, rgba(30,65,117,.55) 0%, transparent 70%),
    radial-gradient(ellipse 50% 45% at 80% 80%, rgba(200,150,62,.07) 0%, transparent 65%),
    #0b1f3a;
}


.page-actions {
  max-width: 660px; margin: 0 auto 18px;
  display: flex; gap: 10px;
  justify-content: space-between; align-items: center;
  animation: fadeUp .4s ease both;
}
@keyframes fadeUp { from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)} }

.btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 9px 18px; border-radius: 9px;
  font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 600;
  cursor: pointer; text-decoration: none; border: none;
  transition: all .2s;
}
.btn-ghost {
  background: rgba(255,255,255,.06);
  color: #94a3b8;
  border: 1px solid rgba(255,255,255,.10);
}
.btn-ghost:hover { background: rgba(255,255,255,.10); color: #c8d5e8; transform: translateY(-1px); }

.btn-primary {
  background: linear-gradient(135deg, var(--gold), var(--gold-lt));
  color: #0b1f3a;
  box-shadow: 0 4px 16px rgba(200,150,62,.35);
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(200,150,62,.50); }

.btn-print {
  background: rgba(255,255,255,.08);
  color: #e2e8f4;
  border: 1px solid rgba(255,255,255,.12);
}
.btn-print:hover { background: rgba(255,255,255,.14); transform: translateY(-1px); }


.receipt {
  max-width: 660px; margin: 0 auto;
  background: rgba(19,45,82,.7);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 22px;
  box-shadow: var(--shadow-md);
  overflow: hidden;
  animation: fadeUp .5s .05s ease both;
}


.receipt-header {
  background: linear-gradient(135deg, #1a3a6b 0%, #0f2548 60%, rgba(200,150,62,.12) 100%);
  padding: 34px 38px;
  border-bottom: 1px solid rgba(200,150,62,.18);
  position: relative; overflow: hidden;
}
.receipt-header::after {
  content: '';
  position: absolute; inset: 0;
  background: radial-gradient(ellipse 80% 80% at 80% 50%, rgba(200,150,62,.08) 0%, transparent 70%);
  pointer-events: none;
}
.receipt-logo {
  display: flex; align-items: center; gap: 10px;
  margin-bottom: 22px;
}
.receipt-logo-icon {
  width: 38px; height: 38px;
  background: linear-gradient(135deg, var(--gold), var(--gold-lt));
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
  box-shadow: 0 4px 14px rgba(200,150,62,.35);
}
.receipt-logo-text {
  font-size: 18px; font-weight: 700;
  color: var(--cream); letter-spacing: -.01em;
}
.receipt-logo-text span { color: var(--gold-lt); }

.receipt-order-num {
  font-size: 30px; font-weight: 800;
  color: var(--cream); margin-bottom: 5px;
  letter-spacing: -.02em;
}
.receipt-date { font-size: 13px; color: rgba(247,244,239,.55); }


.receipt-body { padding: 30px 38px; }

.section { margin-bottom: 26px; }
.section-title {
  font-size: 10px; font-weight: 700;
  color: var(--muted); text-transform: uppercase;
  letter-spacing: .10em; margin-bottom: 13px;
}

.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.info-item label { font-size: 10px; color: var(--muted); display: block; margin-bottom: 3px; }
.info-item span  { font-size: 13px; font-weight: 600; color: var(--cream); }


.status-chip {
  display: inline-flex; align-items: center;
  padding: 5px 14px; border-radius: 20px;
  font-size: 12px; font-weight: 700;
  background: <?= $sColor ?>22;
  color: <?= $sColor ?>;
  border: 1px solid <?= $sColor ?>44;
}


.items-table {
  width: 100%; border-collapse: collapse;
}
.items-table th {
  font-size: 10px; font-weight: 700; color: var(--muted);
  text-transform: uppercase; letter-spacing: .07em;
  padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,.07);
  text-align: left;
}
.items-table th:last-child, .items-table td:last-child { text-align: right; }
.items-table td {
  padding: 13px 0;
  border-bottom: 1px solid rgba(255,255,255,.04);
  font-size: 13px; color: #c8d5e8;
  vertical-align: middle;
}
.items-table tr:last-child td { border-bottom: none; }
.item-name   { font-weight: 600; color: var(--cream); }
.item-cat    { font-size: 10px; color: var(--muted); margin-top: 2px; }


.totals {
  border-top: 1px dashed rgba(255,255,255,.10);
  padding-top: 18px; margin-top: 6px;
}
.total-row {
  display: flex; justify-content: space-between;
  font-size: 13px; color: var(--muted); margin-bottom: 8px;
}
.total-row.final {
  font-size: 18px; font-weight: 800; color: var(--cream);
  margin-top: 4px;
}
.total-row.final span:last-child { color: var(--gold-lt); }


.notes-block {
  background: rgba(200,150,62,.07);
  border-left: 3px solid var(--gold);
  border-radius: 0 9px 9px 0;
  padding: 10px 14px;
  font-size: 13px; color: #c8d5e8;
}


.dashed-divider {
  border: none;
  border-top: 1px dashed rgba(255,255,255,.10);
  margin: 0;
}


.receipt-footer {
  padding: 20px 38px 26px;
  text-align: center;
  border-top: 1px solid rgba(255,255,255,.06);
  background: rgba(255,255,255,.02);
}
.receipt-footer p { font-size: 12px; color: var(--muted); margin-bottom: 3px; }
.receipt-footer strong { color: var(--gold-lt); }


@media print {
  body { background: #fff !important; padding: 0; color: #111; }
  body::before { display: none; }
  .page-actions { display: none !important; }
  .receipt {
    box-shadow: none; border-radius: 0;
    max-width: 100%; border: none;
    background: #fff;
    backdrop-filter: none;
  }
  .receipt-header {
    background: #1a3a6b !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .receipt-body { padding: 20px 28px; }
  .receipt-footer { background: #f5f5f5; }

  nav { display: flex !important; background: #0b1f3a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  nav a div { background: linear-gradient(135deg, #c8963e, #e0b96a) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  nav svg { display: block !important; }
}
</style>
</head>
<body>


<nav style="
  position:sticky;top:0;z-index:200;height:64px;padding:0 32px;
  display:flex;align-items:center;justify-content:space-between;
  background:rgba(11,31,58,.75);backdrop-filter:blur(20px);
  -webkit-backdrop-filter:blur(20px);
  border-bottom:1px solid rgba(255,255,255,.10);
  box-shadow:0 4px 24px rgba(0,0,0,.30);
  margin-bottom:24px;
">
  <a href="recommendation.php" style="display:flex;align-items:center;gap:10px;text-decoration:none;font-size:17px;font-weight:700;color:#f7f4ef;">
    <div style="width:34px;height:34px;background:linear-gradient(135deg,#c8963e,#e0b96a);border-radius:9px;display:flex;align-items:center;justify-content:center;box-shadow:0 0 24px rgba(200,150,62,.25);">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="white"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
    </div>
    <span style="color:#e0b96a;">Orderly</span>
  </a>
  <a href="my_orders_customer.php" style="display:flex;align-items:center;gap:6px;padding:7px 16px;background:rgba(22,163,74,.12);color:#4ade80;border:1px solid rgba(22,163,74,.25);border-radius:9px;font-size:12px;font-weight:600;text-decoration:none;">
    My Orders
  </a>
</nav>


<div class="page-actions" style="justify-content:flex-end;">
  <div style="display:flex;gap:8px;">
    <button onclick="window.print()" class="btn btn-print">Print</button>
    <a href="receipt.php?id=<?= $orderId ?>&download=1" class="btn btn-primary" onclick="return downloadReceipt()">Download</a>
  </div>
</div>


<div class="receipt" id="receiptDoc">

 
  <div class="receipt-header">
    <div class="receipt-logo">
      <div class="receipt-logo-icon">
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
  </svg>
</div>
      <span class="receipt-logo-text">Order<span>ly</span></span>
    </div>
    <div class="receipt-order-num">Order #<?= str_pad($orderId,4,'0',STR_PAD_LEFT) ?></div>
    <div class="receipt-date">Placed on <?= date('d F Y \a\t H:i', strtotime($order['created_at'])) ?></div>
  </div>

  <div class="receipt-body">

    
    <div class="section">
      <div class="section-title">Order Details</div>
      <div class="info-grid">
        <div class="info-item">
          <label>Status</label>
          <span><span class="status-chip"><?= $sLabel ?></span></span>
        </div>
        <div class="info-item">
          <label>Order ID</label>
          <span style="color:var(--gold-lt);">#<?= str_pad($orderId,4,'0',STR_PAD_LEFT) ?></span>
        </div>
        <div class="info-item">
          <label>Customer</label>
          <span><?= h($order['customer_name']) ?></span>
        </div>
        <div class="info-item">
          <label>Email</label>
          <span style="font-size:12px;"><?= h($order['customer_email']) ?></span>
        </div>
      </div>
    </div>

    
    <div class="section">
      <div class="section-title">Restaurant</div>
      <div class="info-grid">
        <div class="info-item">
          <label>Name</label>
          <span><?= h($order['rest_name']) ?></span>
        </div>
        <div class="info-item">
          <label>Area</label>
          <span><?= h($order['area']) ?></span>
        </div>
        <?php if ($order['address']): ?>
        <div class="info-item" style="grid-column:1/-1;">
          <label>Address</label>
          <span><?= h($order['address']) ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($order['notes']): ?>
    <div class="section">
      <div class="section-title">Notes</div>
      <div class="notes-block"><?= h($order['notes']) ?></div>
    </div>
    <?php endif; ?>

    
    <div class="section">
      <div class="section-title">Items Ordered</div>
      <table class="items-table">
        <thead>
          <tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
          <?php foreach ($itemsList as $it): ?>
          <tr>
            <td>
              <div class="item-name"><?= h($it['item_name']) ?></div>
              <div class="item-cat"><?= h($it['category']) ?></div>
            </td>
            <td style="color:var(--muted);">× <?= $it['quantity'] ?></td>
            <td>RM <?= number_format($it['unit_price'],2) ?></td>
            <td><strong style="color:var(--gold-lt);">RM <?= number_format($it['subtotal'],2) ?></strong></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="totals">
        <div class="total-row">
          <span>Subtotal</span>
          <span>RM <?= number_format($order['total_price'],2) ?></span>
        </div>
        <div class="total-row final">
          <span>Total</span>
          <span>RM <?= number_format($order['total_price'],2) ?></span>
        </div>
      </div>
    </div>

  </div>

  <div class="receipt-footer">
    <p>Thank you for ordering with <strong>Orderly</strong>!</p>
    <p>Questions? Contact the restaurant directly.</p>
  </div>
</div>

<script>
function downloadReceipt() { window.print(); return false; }
window.onload = function() {
  <?php if ($download): ?>window.print();<?php endif; ?>
};
</script>
</body>
</html>
