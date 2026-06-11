<?php

require_once 'config.php';
requireVendor();

if (isAdmin()) {
 header("Location: admin_dashboard.php");
 exit();
}

// Vendor scope 
$vendorRestId = getVendorRestaurantId();

// Restaurant info 
$myRestaurant = null;
if ($vendorRestId) {
 $myRestaurant = $conn->query("SELECT * FROM restaurants WHERE id='$vendorRestId'")->fetch_assoc();
}

// Stats (scoped) 
if ($vendorRestId) {
 $totalMenus = $conn->query("SELECT COUNT(*) FROM menu_items WHERE restaurant_id='$vendorRestId'")->fetch_row()[0];
 $availableMenus = $conn->query("SELECT COUNT(*) FROM menu_items WHERE availability=1 AND restaurant_id='$vendorRestId'")->fetch_row()[0];
 $totalOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE restaurant_id='$vendorRestId'")->fetch_row()[0] ?? 0;
 $pendingOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending' AND restaurant_id='$vendorRestId'")->fetch_row()[0] ?? 0;
 $todayOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE restaurant_id='$vendorRestId' AND DATE(created_at)=CURDATE()")->fetch_row()[0] ?? 0;
 $todayRevenue = $conn->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE restaurant_id='$vendorRestId' AND status='completed' AND DATE(created_at)=CURDATE()")->fetch_row()[0] ?? 0;
} else {
 $totalMenus = $availableMenus = $totalOrders = $pendingOrders = $todayOrders = $todayRevenue = 0;
}
$unavailable = $totalMenus - $availableMenus;

// Recent menu items (scoped) 
$recent = $vendorRestId ? $conn->query("
 SELECT m.*, r.name AS rest_name FROM menu_items m
 JOIN restaurants r ON m.restaurant_id = r.id
 WHERE m.restaurant_id='$vendorRestId'
 ORDER BY m.created_at DESC LIMIT 8
") : null;

// Category breakdown (scoped) 
$catBreak = $vendorRestId ? $conn->query("
 SELECT category, COUNT(*) AS cnt FROM menu_items
 WHERE restaurant_id='$vendorRestId' GROUP BY category ORDER BY cnt DESC
") : null;

// Recent orders (scoped) 
$recentOrders = $vendorRestId ? $conn->query("
 SELECT o.*, u.name AS customer_name FROM orders o
 JOIN users u ON o.customer_id = u.id
 WHERE o.restaurant_id='$vendorRestId'
 ORDER BY o.created_at DESC LIMIT 6
") : null;

// Order status breakdown 
$orderStats = [];
if ($vendorRestId) {
 $osr = $conn->query("SELECT status, COUNT(*) AS cnt FROM orders WHERE restaurant_id='$vendorRestId' GROUP BY status");
 while ($os = $osr->fetch_assoc()) $orderStats[$os['status']] = (int)$os['cnt'];
}

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once 'vendor_header.php';
?>

<?php if (isset($_SESSION['toast'])): ?>
<div class="toast-container-custom">
 <div class="toast-msg success"><?= h($_SESSION['toast']) ?></div>
</div>
<?php unset($_SESSION['toast']); endif; ?>


<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
 <div>
 <p class="page-title">Welcome back, <?= h($_SESSION['name']) ?> </p>
 <p class="page-sub">
 <?php if ($myRestaurant): ?>
 Managing <strong><?= h($myRestaurant['name']) ?></strong> · <?= h($myRestaurant['area']) ?>
 <?php else: ?>
 You are not assigned to any restaurant yet.
 <?php endif; ?>
 </p>
 </div>
 <?php if ($vendorRestId): ?>
 <a href="add_menu.php" class="btn-orderly">
 <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
 Add Menu Item
 </a>
 <?php endif; ?>
</div>

<?php if (!$vendorRestId): ?>

<div class="alert-no-rest">
 <div style="font-size:52px;margin-bottom:14px;"></div>
 <p style="font-size:17px;font-weight:700;margin-bottom:8px;color:#fff;">No Restaurant Assigned</p>
 <p style="font-size:13px;color:var(--muted-lt);">Your account has not been assigned to a restaurant yet.<br>Please contact your administrator to get assigned.</p>
</div>
<?php else: ?>


<div class="row g-3 mb-4">
 <div class="col-6 col-md-4 col-lg-2">
 <div class="stat-card">
 <div class="stat-icon">
 <svg viewBox="0 0 24 24"><path d="M18 8h1a4 4 0 010 8h-1"/><path d="M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
 </div>
 <div class="stat-val"><?= $totalMenus ?></div>
 <div class="stat-lbl">Menu Items</div>
 </div>
 </div>
 <div class="col-6 col-md-4 col-lg-2">
 <div class="stat-card">
 <div class="stat-icon">
 <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
 </div>
 <div class="stat-val"><?= $availableMenus ?></div>
 <div class="stat-lbl">Available</div>
 </div>
 </div>
 <div class="col-6 col-md-4 col-lg-2">
 <div class="stat-card">
 <div class="stat-icon">
 <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
 </div>
 <div class="stat-val"><?= $unavailable ?></div>
 <div class="stat-lbl">Unavailable</div>
 </div>
 </div>
 <div class="col-6 col-md-4 col-lg-2">
 <div class="stat-card">
 <div class="stat-icon">
 <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
 </div>
 <div class="stat-val"><?= $totalOrders ?></div>
 <div class="stat-lbl">Total Orders</div>
 </div>
 </div>
 <div class="col-6 col-md-4 col-lg-2">
 <div class="stat-card">
 <div class="stat-icon">
 <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
 </div>
 <div class="stat-val"><?= $pendingOrders ?></div>
 <div class="stat-lbl">Pending</div>
 </div>
 </div>
 <div class="col-6 col-md-4 col-lg-2">
 <div class="stat-card">
 <div class="stat-icon">
 <svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
 </div>
 <div class="stat-val"><?= $todayOrders ?></div>
 <div class="stat-lbl">Today's Orders</div>
 </div>
 </div>
</div>


<div class="row g-3 mb-4">

 
 <div class="col-md-5">
 <div class="dash-card h-100">
 <div class="dash-card-header">
 <span class="dash-card-title"> Order Status</span>
 </div>
 <div class="dash-card-body">
 <?php
 $statusDef = [
 'pending' => [' Pending', '#fbbf24'],
 'confirmed' => [' Confirmed', '#7ba9f8'],
 'preparing' => [' Preparing', '#c4b5fd'],
 'ready' => [' Ready', '#4ade80'],
 'completed' => [' Completed', '#e0b96a'],
 'cancelled' => [' Cancelled', '#f87171'],
 ];
 foreach ($statusDef as $st => [$lbl, $color]):
 $cnt = $orderStats[$st] ?? 0;
 $pct = $totalOrders > 0 ? round($cnt / $totalOrders * 100) : 0;
 ?>
 <div class="mb-3">
 <div class="d-flex justify-content-between mb-1">
 <span style="font-size:12px;font-weight:600;color:rgba(255,255,255,.8);"><?= $lbl ?></span>
 <span style="font-size:11px;color:var(--muted-lt);"><?= $cnt ?> (<?= $pct ?>%)</span>
 </div>
 <div class="progress-track">
 <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
 </div>
 </div>
 <?php endforeach; ?>
 </div>
 </div>
 </div>


 <div class="col-md-7">
 <div class="dash-card h-100">
 <div class="dash-card-header">
 <span class="dash-card-title"> Menu by Category</span>
 </div>
 <div class="dash-card-body">
 <?php if ($catBreak && $catBreak->num_rows > 0): ?>
 <div class="d-flex flex-wrap gap-2">
 <?php while ($c = $catBreak->fetch_assoc()): ?>
 <span class="badge-pill bp-blue"><?= h($c['category']) ?> &nbsp;<strong><?= $c['cnt'] ?></strong></span>
 <?php endwhile; ?>
 </div>
 <?php else: ?>
 <p style="font-size:13px;color:var(--muted-lt);text-align:center;padding:24px;">No menu items yet.</p>
 <?php endif; ?>
 </div>
 </div>
 </div>
</div>


<?php if ($recentOrders && $recentOrders->num_rows > 0): ?>
<div class="dash-card mb-4">
 <div class="dash-card-header">
 <span class="dash-card-title"> Recent Orders</span>
 <a href="my_orders.php" class="badge-pill bp-blue" style="text-decoration:none;">View All →</a>
 </div>
 <div class="dash-card-body" style="padding:0;">
 <div style="overflow-x:auto;">
 <table class="table table-hover w-100" style="margin:0;">
 <thead>
 <tr>
 <th>Order #</th>
 <th>Customer</th>
 <th>Total</th>
 <th>Status</th>
 <th>Date</th>
 <th>Action</th>
 </tr>
 </thead>
 <tbody>
 <?php while ($ord = $recentOrders->fetch_assoc()):
 $sCfg = [
 'pending' => [' Pending', 'status-pending'],
 'confirmed' => [' Confirmed', 'status-confirmed'],
 'preparing' => [' Preparing', 'status-preparing'],
 'ready' => [' Ready', 'status-ready'],
 'completed' => [' Completed', 'status-completed'],
 'cancelled' => [' Cancelled', 'status-cancelled'],
 ];
 [$sLbl, $sCls] = $sCfg[$ord['status']] ?? [$ord['status'], ''];
 ?>
 <tr>
 <td><strong style="color:var(--gold-lt);">#<?= str_pad($ord['id'],4,'0',STR_PAD_LEFT) ?></strong></td>
 <td style="color:rgba(255,255,255,.8);"><?= h($ord['customer_name']) ?></td>
 <td><strong style="color:#fff;">RM <?= number_format($ord['total_price'],2) ?></strong></td>
 <td><span class="order-status-badge <?= $sCls ?>"><?= $sLbl ?></span></td>
 <td style="font-size:12px;color:var(--muted-lt);"><?= date('d M, H:i', strtotime($ord['created_at'])) ?></td>
 <td><a href="update_order.php?id=<?= $ord['id'] ?>" class="btn-edit-sm">Update</a></td>
 </tr>
 <?php endwhile; ?>
 </tbody>
 </table>
 </div>
 </div>
</div>
<?php endif; ?>


<div class="dash-card">
 <div class="dash-card-header">
 <span class="dash-card-title">Recently Added Items</span>
 <a href="manage_menu.php" class="badge-pill bp-blue" style="text-decoration:none;">View All →</a>
 </div>
 <div class="dash-card-body">
 <?php if ($recent && $recent->num_rows > 0): ?>
 <div class="row g-3">
 <?php while ($item = $recent->fetch_assoc()): ?>
 <div class="col-6 col-md-4 col-lg-3">
 <div class="food-card">
 <?php if ($item['image'] && file_exists('uploads/food_images/'.$item['image'])): ?>
 <img src="uploads/food_images/<?= h($item['image']) ?>" class="food-card-img" alt="<?= h($item['name']) ?>"/>
 <?php else: ?>
 <div class="food-card-img-placeholder"></div>
 <?php endif; ?>
 <div class="food-card-body">
 <div class="food-card-name"><?= h($item['name']) ?></div>
 <div class="d-flex align-items-center justify-content-between">
 <span class="food-card-price">RM <?= number_format($item['price'],2) ?></span>
 <span class="badge-pill <?= $item['availability'] ? 'avail-on' : 'avail-off' ?>">
 <?= $item['availability'] ? '' : '' ?>
 </span>
 </div>
 </div>
 <div class="food-card-footer">
 <a href="add_menu.php?edit=<?= $item['id'] ?>" class="btn-edit-sm">Edit</a>
 </div>
 </div>
 </div>
 <?php endwhile; ?>
 </div>
 <?php else: ?>
 <div class="text-center py-5" style="color:var(--muted-lt);">
 <div style="font-size:52px;margin-bottom:14px;"></div>
 <p>No menu items yet. <a href="add_menu.php" style="color:var(--gold-lt);font-weight:600;">Add one now!</a></p>
 </div>
 <?php endif; ?>
 </div>
</div>

<?php endif; // end if vendorRestId ?>

<?php require_once 'includes/footer.php'; ?>