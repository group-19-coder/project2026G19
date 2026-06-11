<?php

require_once 'config.php';
requireAdmin();


$totalUsers    = $conn->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetch_row()[0];
$totalVendors  = $conn->query("SELECT COUNT(*) FROM users WHERE role='vendor_staff'")->fetch_row()[0];
$totalRests    = $conn->query("SELECT COUNT(*) FROM restaurants")->fetch_row()[0];
$totalMenus    = $conn->query("SELECT COUNT(*) FROM menu_items")->fetch_row()[0];
$totalOrders   = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0] ?? 0;
$pendingOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetch_row()[0] ?? 0;
$totalRevenue  = $conn->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status='completed'")->fetch_row()[0] ?? 0;
$menuAvail     = $conn->query("SELECT COUNT(*) FROM menu_items WHERE availability=1")->fetch_row()[0];
$unassigned    = $conn->query("SELECT COUNT(*) FROM users WHERE role='vendor_staff' AND (restaurant_id IS NULL OR restaurant_id=0)")->fetch_row()[0];

// Recent activity
$activityLog = $conn->query("
    SELECT a.*, u.name AS actor_name FROM activity_log a
    LEFT JOIN users u ON a.user_id=u.id ORDER BY a.created_at DESC LIMIT 12
");

// Restaurant overview
$restOverview = $conn->query("
    SELECT r.id, r.name, r.area,
           COUNT(DISTINCT m.id) AS menu_count,
           COUNT(DISTINCT vs.id) AS staff_count,
           COUNT(DISTINCT o.id) AS order_count
    FROM restaurants r
    LEFT JOIN menu_items m ON m.restaurant_id=r.id
    LEFT JOIN users vs ON vs.restaurant_id=r.id AND vs.role='vendor_staff'
    LEFT JOIN orders o ON o.restaurant_id=r.id
    GROUP BY r.id ORDER BY r.area, r.name
");

// Vendor staff
$vendorStaff = $conn->query("
    SELECT u.id, u.name, u.email, u.created_at,
           r.name AS rest_name, r.area AS rest_area, r.id AS rest_id
    FROM users u LEFT JOIN restaurants r ON u.restaurant_id=r.id
    WHERE u.role='vendor_staff' ORDER BY u.created_at DESC LIMIT 8
");

$restaurants = $conn->query("SELECT id, name, area FROM restaurants ORDER BY area, name");

// Recent orders
$recentOrders = $conn->query("
    SELECT o.id, o.status, o.total_price, o.created_at,
           o.payment_status, o.payment_method, o.cancellation_reason,
           u.name AS customer_name, r.name AS restaurant_name
    FROM orders o JOIN users u ON o.customer_id=u.id JOIN restaurants r ON o.restaurant_id=r.id
    ORDER BY o.created_at DESC LIMIT 6
");

$pageTitle  = 'Admin Dashboard';
$activePage = 'dashboard';
require_once 'admin_header.php';
?>

<?php if (isset($_SESSION['admin_toast'])): ?>
<div class="toast-container-custom"><div class="toast-msg success"><?= h($_SESSION['admin_toast']) ?></div></div>
<?php unset($_SESSION['admin_toast']); endif; ?>
<?php if (isset($_SESSION['admin_error'])): ?>
<div class="toast-container-custom"><div class="toast-msg" style="border-color:var(--red);color:var(--red);"><?= h($_SESSION['admin_error']) ?></div></div>
<?php unset($_SESSION['admin_error']); endif; ?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <p class="page-title">System Overview</p>
    <p class="page-sub">Welcome back, <?= h($_SESSION['name']) ?>. Here's what's happening.</p>
  </div>
  <div style="display:flex;gap:8px;">
    <button class="btn-primary" onclick="openOrdModal('addVendorModal')">+ Add Staff</button>
    <a href="vendor_assignment.php" class="btn-ghost">Assign Staff</a>
  </div>
</div>

<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['Restaurants',    $totalRests,    'green',  '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
    ['Menu Items',     $totalMenus,     'purple', '<path d="M18 8h1a4 4 0 010 8h-1"/><path d="M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>'],
    ['Total Orders',   $totalOrders,   'blue',   '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>'],
    ['Customers',      $totalUsers,    'purple', '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>'],
    ['Pending Orders', $pendingOrders, 'amber',  '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
    ['Revenue (RM)',   number_format($totalRevenue,2), 'green', '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>'],
    ['Vendor Staff',   $totalVendors,  'purple', '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>'],
    ['Unassigned',     $unassigned,    $unassigned>0?'amber':'green', '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'],
  ];
  $colorMap = ['green'=>'var(--green)','purple'=>'var(--accent)','blue'=>'var(--blue)','amber'=>'var(--amber)','red'=>'var(--red)'];
  $bgMap    = ['green'=>'var(--green-bg)','purple'=>'var(--accent-glow)','blue'=>'var(--blue-bg)','amber'=>'var(--amber-bg)','red'=>'var(--red-bg)'];
  foreach ($cards as [$lbl,$val,$color,$icon]):
  ?>
  <div class="col-6 col-md-3 col-xl-3" style="min-width:0;">
    <div class="stat-card" style="border-top:2px solid <?= $colorMap[$color] ?>;">
      <div class="stat-icon" style="background:<?= $bgMap[$color] ?>;margin:0 0 10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= $colorMap[$color] ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $icon ?></svg>
      </div>
      <div class="stat-val" style="font-size:22px;"><?= $val ?></div>
      <div class="stat-lbl"><?= $lbl ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-7">
    <div class="dash-card">
      <div class="dash-card-header">
        <span class="dash-card-title">Restaurant Overview</span>
        <a href="restaurants.php" style="font-size:12px;color:var(--accent2);text-decoration:none;">Manage →</a>
      </div>
      <div class="dash-card-body" style="padding:0;">
        <table class="table" style="margin:0;">
          <thead><tr><th>Restaurant</th><th>Area</th><th>Menus</th><th>Staff</th><th>Orders</th><th></th></tr></thead>
          <tbody>
          <?php while ($r = $restOverview->fetch_assoc()): ?>
          <tr>
            <td><strong><?= h($r['name']) ?></strong></td>
            <td><span class="badge-pill bp-blue"><?= h($r['area']) ?></span></td>
            <td><?= $r['menu_count'] ?></td>
            <td>
              <?php if ($r['staff_count'] > 0): ?>
              <span style="color:var(--green);font-weight:600;"><?= $r['staff_count'] ?></span>
              <?php else: ?>
              <span style="color:var(--amber);font-size:11px;">None</span>
              <?php endif; ?>
            </td>
            <td><?= $r['order_count'] ?></td>
            <td><a href="manage_menu.php?restaurant=<?= $r['id'] ?>" class="btn-edit-sm">Menu</a></td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="dash-card h-100">
      <div class="dash-card-header">
        <span class="dash-card-title">Recent Activity</span>
        <a href="admin_reports.php" style="font-size:12px;color:var(--accent2);text-decoration:none;">Reports →</a>
      </div>
      <div class="dash-card-body" style="max-height:320px;overflow-y:auto;">
        <?php if ($activityLog && $activityLog->num_rows > 0):
          while ($log = $activityLog->fetch_assoc()): ?>
        <div style="display:flex;gap:10px;margin-bottom:14px;align-items:flex-start;">
          <div style="width:7px;height:7px;border-radius:50%;background:var(--accent);margin-top:5px;flex-shrink:0;"></div>
          <div>
            <p style="font-size:12.5px;"><strong><?= h($log['actor_name']??'System') ?></strong> — <?= h($log['action']) ?>
              <?php if ($log['details']): ?><span style="color:var(--muted);"> · <?= h(mb_strimwidth($log['details'],0,35,'…')) ?></span><?php endif; ?>
            </p>
            <p style="font-size:11px;color:var(--muted);"><?= date('d M, H:i', strtotime($log['created_at'])) ?></p>
          </div>
        </div>
        <?php endwhile; else: ?>
        <p style="font-size:13px;color:var(--muted);text-align:center;padding:20px;">No activity yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="dash-card mb-4">
  <div class="dash-card-header">
    <span class="dash-card-title">Recent Orders</span>
    <a href="orders.php" style="font-size:12px;color:var(--accent2);text-decoration:none;">View All →</a>
  </div>
  <div class="dash-card-body" style="padding:0;">
    <table class="table" style="margin:0;">
      <thead><tr><th>Order #</th><th>Customer</th><th>Restaurant</th><th>Total</th><th>Status</th><th>Payment</th><th>Date</th><th></th></tr></thead>
      <tbody>
      <?php
      $sCfg=['pending'=>['','#d4a017'],'confirmed'=>['','#2980b9'],'preparing'=>['','#5a7bd8'],'ready'=>['','#27ae60'],'completed'=>['','#7494ec'],'cancelled'=>['','#c0392b']];
      while ($ord = $recentOrders->fetch_assoc()):
        [$emoji,$color] = $sCfg[$ord['status']] ?? ['','#8a90a8'];
      ?>
      <tr>
        <td><strong style="color:var(--accent2);">#<?= str_pad($ord['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
        <td><?= h($ord['customer_name']) ?></td>
        <td style="font-size:12px;color:var(--muted);"><?= h($ord['restaurant_name']) ?></td>
        <td><strong>RM <?= number_format($ord['total_price'],2) ?></strong></td>
        <td>
          <?php if ($ord['status'] === 'cancelled' && !empty($ord['cancellation_reason'])): ?>
          <span style="color:<?= $color ?>;font-size:12px;font-weight:700;cursor:help;border-bottom:1px dashed <?= $color ?>;"
                title="Reason: <?= h($ord['cancellation_reason']) ?>">
            <?= ucfirst($ord['status']) ?>
          </span>
          <?php else: ?>
          <span style="color:<?= $color ?>;font-size:12px;font-weight:700;"><?= ucfirst($ord['status']) ?></span>
          <?php endif; ?>
        </td>
        <td>
          <?php
            $ps = $ord['payment_status'] ?? 'unpaid';
            $pm = $ord['payment_method'] ?? '';
            if ($ps === 'paid') echo '<span style="color:#4ade80;font-size:11px;font-weight:700;">✓ Paid</span>';
            elseif ($pm === 'cash') echo '<span style="color:#fbbf24;font-size:11px;font-weight:600;">💵 Cash</span>';
            else echo '<span style="color:#f87171;font-size:11px;font-weight:600;">Unpaid</span>';
          ?>
        </td>
        <td style="font-size:12px;color:var(--muted);"><?= date('d M, H:i',strtotime($ord['created_at'])) ?></td>
        <td><a href="order_detail.php?id=<?= $ord['id'] ?>" class="btn-edit-sm">View</a></td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="dash-card">
  <div class="dash-card-header">
    <span class="dash-card-title">Vendor Staff</span>
    <a href="vendor_assignment.php" style="font-size:12px;color:var(--accent2);text-decoration:none;">Manage Assignments →</a>
  </div>
  <div class="dash-card-body" style="padding:0;">
    <table class="table" style="margin:0;">
      <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Assigned Restaurant</th><th>Since</th><th>Action</th></tr></thead>
      <tbody>
      <?php $vendorStaff->data_seek(0); while ($vs = $vendorStaff->fetch_assoc()): ?>
      <tr>
        <td style="color:var(--muted);font-size:12px;">#<?= $vs['id'] ?></td>
        <td><strong><?= h($vs['name']) ?></strong></td>
        <td style="color:var(--muted);font-size:12px;"><?= h($vs['email']) ?></td>
        <td>
          <?php if ($vs['rest_name']): ?>
          <span class="badge-pill" style="background:var(--accent-glow);color:var(--accent2);border:1px solid rgba(124,106,247,.25);"><?= h($vs['rest_name']) ?></span>
          <?php else: ?>
          <span class="badge-pill" style="background:var(--amber-bg);color:var(--amber);border:1px solid rgba(245,158,11,.25);">Not assigned</span>
          <?php endif; ?>
        </td>
        <td style="font-size:12px;color:var(--muted);"><?= date('d M Y', strtotime($vs['created_at'])) ?></td>
        <td>
          <a href="vendor_assignment.php" class="btn-edit-sm" style="background:var(--accent-glow);color:var(--accent2);border-color:rgba(124,106,247,.25);">Assign</a>
          <a href="admin_users.php" class="btn-edit-sm" style="margin-left:4px;">Edit</a>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="ord-overlay" id="addVendorModal">
  <div class="ord-modal">
    <p class="ord-modal-title">Add Vendor Staff</p>
    <form method="POST" action="admin_handler.php">
      <input type="hidden" name="action" value="add_vendor_staff">
      <input type="hidden" name="redirect" value="admin_dashboard.php">
      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Password *</label>
        <input type="password" name="password" class="form-control" minlength="6" required>
      </div>
      <div class="form-group">
        <label class="form-label">Assign Restaurant</label>
        <select name="restaurant_id" class="form-control">
          <option value="">— Assign later —</option>
          <?php $restaurants->data_seek(0); while ($r = $restaurants->fetch_assoc()): ?>
          <option value="<?= $r['id'] ?>"><?= h($r['name']) ?> (<?= h($r['area']) ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="ord-modal-actions">
        <button type="button" class="btn-ghost" onclick="closeOrdModal('addVendorModal')">Cancel</button>
        <button type="submit" class="btn-primary">Create Staff</button>
      </div>
    </form>
  </div>
</div>

<script>
function openOrdModal(id)  { document.getElementById(id).classList.add('open'); }
function closeOrdModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.ord-overlay').forEach(o => {
  o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); });
});
document.querySelectorAll('.toast-msg').forEach(t => setTimeout(() => t.style.display='none', 4000));
</script>

<?php require_once 'includes/footer.php'; ?>