<?php
require_once 'config.php';
requireVendor();
if (isAdmin()) { header("Location: restaurants.php"); exit(); }

$vendorRestId = getVendorRestaurantId();
if (!$vendorRestId) {
    $pageTitle = 'My Restaurant';
    $activePage = 'my_restaurant';
    require_once 'vendor_header.php';
    echo '<div class="saas-glass-card" style="text-align:center; padding:60px 20px; max-width:600px; margin:40px auto; background:rgba(255, 255, 255, 0.02); border:1px dashed rgba(200,150,62,0.25);"><p style="font-family:\'Space Grotesk\', sans-serif; font-size:14px; font-weight:700; color:var(--gold-lt); text-transform:uppercase; letter-spacing:0.05em; margin:0 0 12px 0;">Alert</p><p style="margin:0; font-family:\'Poppins\',sans-serif; font-weight:600; color:var(--cream-dk);">No Restaurant Assigned</p><p style="font-family:\'Poppins\',sans-serif; font-size:13.5px; color:var(--muted); margin-top:8px; margin-bottom:0;">Please contact your administrator to get assigned to a restaurant.</p></div>';
    require_once 'includes/footer.php';
    exit();
}

$rest = $conn->query("SELECT * FROM restaurants WHERE id='$vendorRestId'")->fetch_assoc();
$menuCount   = $conn->query("SELECT COUNT(*) FROM menu_items WHERE restaurant_id='$vendorRestId'")->fetch_row()[0];
$availCount  = $conn->query("SELECT COUNT(*) FROM menu_items WHERE restaurant_id='$vendorRestId' AND availability=1")->fetch_row()[0];
$orderCount  = $conn->query("SELECT COUNT(*) FROM orders WHERE restaurant_id='$vendorRestId'")->fetch_row()[0] ?? 0;
$revenue     = $conn->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE restaurant_id='$vendorRestId' AND status='completed'")->fetch_row()[0] ?? 0;

$pageTitle  = 'My Restaurant';
$activePage = 'my_restaurant';
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
  
  /* Glassmorphism Presets */
  --glass-bg: rgba(255, 255, 255, 0.03);
  --glass-border: rgba(255, 255, 255, 0.07);
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

/* Premium Component Reusable Styles */
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

.saas-card-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--glass-border);
  background: rgba(11, 31, 58, 0.3);
}

.saas-card-title {
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 700;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--gold-lt);
}

/* Stat Grid Components Integration */
.saas-stat-card {
  background: var(--glass-bg);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid var(--glass-border);
  border-radius: 16px;
  padding: 22px 20px;
  display: flex;
  flex-direction: column;
  position: relative;
  box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.4);
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.saas-stat-card:hover {
  transform: translateY(-4px);
  border-color: rgba(255, 255, 255, 0.15);
  box-shadow: 0 12px 30px -5px rgba(0, 0, 0, 0.5);
}

.saas-stat-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: rgba(200, 150, 62, 0.08);
  border: 1px solid rgba(200, 150, 62, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  transition: all 0.3s ease;
}

.saas-stat-card:hover .saas-stat-icon {
  background: var(--gold);
  border-color: var(--gold);
}

.saas-stat-icon svg {
  width: 18px;
  height: 18px;
  fill: none;
  stroke: var(--gold-lt);
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
  transition: all 0.3s ease;
}

.saas-stat-card:hover .saas-stat-icon svg {
  stroke: #0b1f3a;
}

.saas-stat-val {
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 700;
  font-size: 22px;
  color: var(--cream);
  line-height: 1.2;
}

.saas-stat-lbl {
  font-family: 'Poppins', sans-serif;
  font-size: 12px;
  font-weight: 500;
  color: var(--muted);
  margin-top: 4px;
}

/* Framework Badges Override */
.saas-badge-pill {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 6px;
  font-family: 'Poppins', sans-serif;
  font-size: 11px;
  font-weight: 600;
  border: 1px solid transparent;
}
.bp-blue {
  background: rgba(37, 99, 235, 0.1);
  color: #60a5fa;
  border-color: rgba(37, 99, 235, 0.25);
}
</style>

<div class="page-header mb-4 animate-fadeUp">
  <p class="page-title" style="font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:28px; color:var(--cream); margin:0; letter-spacing:-0.02em;">My Restaurant</p>
  <p class="page-sub" style="font-family:'Poppins',sans-serif; font-size:13.5px; color:var(--muted); margin-top:6px; margin-bottom:0;">View your assigned restaurant details.</p>
</div>

<div class="row g-4 animate-fadeUp" style="animation-delay: 0.1s;">
  <div class="col-lg-6">
    <div class="saas-glass-card h-100">
      <div class="saas-card-header">
        <span class="saas-card-title">Restaurant Info</span>
      </div>
      <div class="saas-card-body" style="padding: 24px;">
        <div style="font-family:'Space Grotesk',sans-serif; font-size:26px; font-weight:700; color:var(--cream); margin-bottom:12px; letter-spacing:-0.01em;"><?= h($rest['name']) ?></div>
        
        <div style="margin-bottom:16px;">
          <span class="saas-badge-pill bp-blue"><?= h($rest['area']) ?></span>
        </div>
        
        <?php if ($rest['address']): ?>
        <div style="display:flex; gap:8px; margin-top:16px;">
          <span style="font-size:13px; color:var(--gold-lt); font-family:'Poppins',sans-serif; font-weight:500; text-transform:uppercase; letter-spacing:0.04em;">Address:</span>
          <p style="font-family:'Poppins',sans-serif; font-size:13.5px; color:var(--cream-dk); margin:0; line-height:1.5; flex:1;"><?= h($rest['address']) ?></p>
        </div>
        <?php endif; ?>
        
        <hr style="border-top:1px solid var(--glass-border); border-bottom:none; border-left:none; border-right:none; margin:24px 0 16px 0;">
        <p style="font-family:'Poppins',sans-serif; font-size:11.5px; color:var(--muted); margin:0; font-weight:500; letter-spacing:0.02em;">Restaurant Reference Identifier: #<?= $rest['id'] ?></p>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="row g-3">
      <div class="col-6">
        <div class="saas-stat-card">
          <div class="saas-stat-icon">
            <svg viewBox="0 0 24 24"><path d="M18 8h1a4 4 0 010 8h-1"/><path d="M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/></svg>
          </div>
          <div class="saas-stat-val"><?= $menuCount ?></div>
          <div class="saas-stat-lbl">Menu Items</div>
        </div>
      </div>
      
      <div class="col-6">
        <div class="saas-stat-card">
          <div class="saas-stat-icon">
            <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <div class="saas-stat-val"><?= $availCount ?></div>
          <div class="saas-stat-lbl">Available</div>
        </div>
      </div>
      
      <div class="col-6">
        <div class="saas-stat-card">
          <div class="saas-stat-icon">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/></svg>
          </div>
          <div class="saas-stat-val"><?= $orderCount ?></div>
          <div class="saas-stat-lbl">Total Orders</div>
        </div>
      </div>
      
      <div class="col-6">
        <div class="saas-stat-card">
          <div class="saas-stat-icon">
            <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          </div>
          <div class="saas-stat-val" style="color:var(--gold-lt);">RM <?= number_format($revenue,2) ?></div>
          <div class="saas-stat-lbl">Revenue</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>