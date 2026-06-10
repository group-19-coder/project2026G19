<?php

requireAdmin();
$pendingOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetch_row()[0] ?? 0;
$unassignedVendors = $conn->query("SELECT COUNT(*) FROM users WHERE role='vendor_staff' AND (restaurant_id IS NULL OR restaurant_id=0)")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Orderly Admin — <?= h($pageTitle ?? 'Dashboard') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
<style>

:root {
 
  --navy: #0b1f3a;
  --navy-md: #132d52;
  --navy-lt: #1e4175;

 
  --gold: #c8963e;
  --gold-lt: #e0b96a;
  --gold-glow: rgba(200, 150, 62, 0.15);

 
  --cream: #f7f4ef;
  --cream-dk: #ede8e0;
  
  --text-light: #f7f4ef;
  --text-muted: #8fa0b5;
  --glass-surface: rgba(255, 255, 255, 0.04);
  --glass-border: rgba(255, 255, 255, 0.08);
  --glass-border-focus: rgba(200, 150, 62, 0.4);

  
  --success: #16a34a;
  --danger: #dc2626;
  --warning: #f59e0b;
  --info: #2563eb;
  --preparing: #a855f7;
  
  
  --sidebar-w: 260px;
  --radius: 18px;
  --radius-sm: 8px;
  --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}


*, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Poppins', sans-serif;
  background: radial-gradient(circle at 50% 0%, var(--navy-md) 0%, var(--navy) 70%);
  color: var(--text-light);
  min-height: 100vh;
  display: flex;
  overflow-x: hidden;
}


::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
::-webkit-scrollbar-track {
  background: var(--navy);
}
::-webkit-scrollbar-thumb {
  background: var(--navy-lt);
  border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
  background: var(--gold);
}


.main {
  margin-left: var(--sidebar-w);
  flex: 1;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  position: relative;
  transition: var(--transition);
}

.content {
  padding: 40px;
  flex: 1;
  animation: fadeUp 0.6s ease-out;
}


.sidebar {
  width: var(--sidebar-w);
  min-height: 100vh;
  background: rgba(11, 31, 58, 0.6);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-right: 1px solid var(--glass-border);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 100;
  box-shadow: 10px 0 30px rgba(0, 0, 0, 0.3);
}

.sidebar-logo {
  padding: 30px 24px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid var(--glass-border);
}

.logo-mark {
  width: 38px;
  height: 38px;
  background: linear-gradient(135deg, var(--gold-lt), var(--gold));
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  box-shadow: 0 4px 15px var(--gold-glow);
}

.logo-text {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-light);
  letter-spacing: 0.5px;
}

.logo-badge {
  font-size: 9px;
  font-weight: 700;
  background: var(--gold-glow);
  color: var(--gold-lt);
  padding: 3px 8px;
  border-radius: 20px;
  border: 1px solid rgba(200, 150, 62, 0.3);
  margin-left: auto;
  letter-spacing: 1px;
}

.sidebar-section {
  padding: 24px 24px 8px;
  font-size: 10px;
  font-weight: 700;
  color: var(--gold);
  letter-spacing: .15em;
  text-transform: uppercase;
  opacity: 0.8;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 18px;
  border-radius: var(--radius-sm);
  margin: 2px 14px;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-muted);
  text-decoration: none;
  transition: var(--transition);
  border: 1px solid transparent;
}

.nav-item:hover {
  background: var(--glass-surface);
  color: var(--text-light);
  transform: translateX(4px);
}

.nav-item.active {
  background: linear-gradient(90deg, var(--gold-glow), transparent);
  color: var(--gold-lt);
  border-left: 3px solid var(--gold);
  font-weight: 600;
}

.nav-item svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  transition: var(--transition);
}

.nav-item:hover svg, .nav-item.active svg {
  transform: scale(1.1);
  color: var(--gold-lt);
}

.nav-badge {
  margin-left: auto;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 20px;
  background: rgba(220, 38, 36, 0.15);
  color: var(--danger);
  border: 1px solid rgba(220, 38, 36, 0.25);
}

.nav-badge.amber {
  background: rgba(245, 158, 11, 0.15);
  color: var(--warning);
  border: 1px solid rgba(245, 158, 11, 0.25);
}

.sidebar-bottom {
  margin-top: auto;
  padding: 20px 24px;
  background: rgba(0, 0, 0, 0.15);
  border-top: 1px solid var(--glass-border);
}

.user-chip-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-light);
}

.user-chip-role {
  font-size: 11px;
  color: var(--gold-lt);
  font-weight: 400;
}

.logout-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  font-weight: 500;
  color: var(--text-muted);
  text-decoration: none;
  margin-top: 12px;
  transition: var(--transition);
}

.logout-btn:hover {
  color: var(--danger);
}


.topbar {
  height: 70px;
  background: rgba(11, 31, 58, 0.4);
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  border-bottom: 1px solid var(--glass-border);
  display: flex;
  align-items: center;
  padding: 0 40px;
  position: sticky;
  top: 0;
  z-index: 50;
}

.topbar-title {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-light);
  letter-spacing: 0.5px;
}

.dash-card, .stat-card, .food-card {
  background: var(--glass-surface);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid var(--glass-border);
  border-radius: var(--radius);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  transition: var(--transition);
  overflow: hidden;
}

.dash-card:hover, .stat-card:hover {
  transform: translateY(-5px);
  border-color: rgba(200, 150, 62, 0.25);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
}

.dash-card-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--glass-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(255, 255, 255, 0.01);
}

.dash-card-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--text-light);
}

.dash-card-body {
  padding: 24px;
}

.stat-card {
  padding: 24px;
  text-align: center;
}

.stat-icon {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  background: var(--gold-glow);
  border: 1px solid rgba(200, 150, 62, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 14px;
  transition: var(--transition);
}

.stat-card:hover .stat-icon {
  background: var(--gold);
}

.stat-card:hover .stat-icon svg {
  stroke: var(--navy);
}

.stat-icon svg {
  width: 20px;
  height: 20px;
  stroke: var(--gold-lt);
  fill: none;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
  transition: var(--transition);
}

.stat-val {
  font-size: 26px;
  font-weight: 700;
  color: var(--text-light);
  letter-spacing: -0.5px;
}

.stat-lbl {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 2px;
  font-weight: 500;
}

.btn-orderly, .btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  background: linear-gradient(135deg, var(--gold-lt), var(--gold));
  color: var(--navy) !important;
  border: none;
  border-radius: var(--radius-sm);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  text-decoration: none;
  box-shadow: 0 4px 15px var(--gold-glow);
}

.btn-orderly:hover, .btn-primary:hover {
  opacity: 0.9;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(200, 150, 62, 0.3);
}

.btn-ghost {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 18px;
  background: transparent;
  color: var(--text-muted);
  border: 1.5px solid var(--glass-border);
  border-radius: var(--radius-sm);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: var(--transition);
  text-decoration: none;
}

.btn-ghost:hover {
  border-color: var(--gold);
  color: var(--gold-lt);
  background: var(--glass-surface);
}

.btn-edit-sm {
  padding: 5px 12px;
  background: rgba(37, 99, 235, 0.15);
  color: var(--info) !important;
  border: 1px solid rgba(37, 99, 235, 0.25);
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  display: inline-block;
  transition: var(--transition);
}

.btn-edit-sm:hover {
  background: var(--info);
  color: #fff !important;
}

.btn-danger-sm {
  padding: 5px 12px;
  background: rgba(220, 38, 38, 0.15);
  color: var(--danger) !important;
  border: 1px solid rgba(220, 38, 38, 0.25);
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  display: inline-block;
  transition: var(--transition);
}

.btn-danger-sm:hover {
  background: var(--danger);
  color: #fff !important;
}


.form-select, .form-control {
  background: rgba(11, 31, 58, 0.4);
  border: 1.5px solid var(--glass-border);
  color: var(--text-light);
  font-size: 13px;
  border-radius: var(--radius-sm);
  padding: 10px 14px;
  transition: var(--transition);
}

.form-select option {
  background: var(--navy-md);
  color: var(--text-light);
}

.form-select:focus, .form-control:focus {
  border-color: var(--gold);
  box-shadow: 0 0 0 4px var(--gold-glow);
  background: rgba(11, 31, 58, 0.6);
  color: var(--text-light);
}

.form-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--gold-lt);
  text-transform: uppercase;
  letter-spacing: .08em;
  margin-bottom: 6px;
  display: block;
}

.req {
  color: var(--danger);
  margin-left: 2px;
}


.table-responsive-container {
  border-radius: var(--radius);
  overflow: hidden;
  border: 1px solid var(--glass-border);
}

.table {
  color: var(--text-light);
  margin-bottom: 0;
  background: transparent;
}

.table th {
  font-size: 11px;
  font-weight: 600;
  color: var(--gold-lt);
  text-transform: uppercase;
  letter-spacing: .08em;
  border-bottom: 2px solid var(--glass-border);
  background: rgba(11, 31, 58, 0.5);
  padding: 14px 18px;
}

.table td {
  padding: 16px 18px;
  border-bottom: 1px solid var(--glass-border);
  vertical-align: middle;
  background: transparent;
  color: var(--text-light);
}

.table tr:last-child td {
  border-bottom: none;
}

.table tr:hover td {
  background: rgba(255, 255, 255, 0.02);
}

.badge-pill {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.3px;
}

.bp-blue      { background: rgba(37, 99, 235, 0.15);  color: var(--info);    border: 1px solid rgba(37, 99, 235, 0.25); }
.bp-green     { background: rgba(22, 163, 74, 0.15);  color: var(--success); border: 1px solid rgba(22, 163, 74, 0.25); }
.avail-on     { background: rgba(22, 163, 74, 0.15);  color: var(--success); border: 1px solid rgba(22, 163, 74, 0.2); }
.avail-off    { background: rgba(220, 38, 38, 0.15);  color: var(--danger);  border: 1px solid rgba(220, 38, 38, 0.2); }


.status-pending   { background: rgba(245, 158, 11, 0.15); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.25); }
.status-confirmed { background: rgba(37, 99, 235, 0.15);  color: var(--info);    border: 1px solid rgba(37, 99, 235, 0.25); }
.status-preparing { background: rgba(168, 85, 247, 0.15); color: var(--preparing);border: 1px solid rgba(168, 85, 247, 0.25); }
.status-ready     { background: rgba(22, 163, 74, 0.15);  color: var(--success); border: 1px solid rgba(22, 163, 74, 0.25); }


.food-card {
  background: rgba(255, 255, 255, 0.02);
}
.food-card-img {
  width: 100%;
  height: 130px;
  object-fit: cover;
  border-bottom: 1px solid var(--glass-border);
}
.food-card-img-placeholder {
  width: 100%;
  height: 130px;
  background: var(--gold-glow);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36px;
  border-bottom: 1px solid var(--glass-border);
}
.food-card-body {
  padding: 16px;
}
.food-card-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-light);
  margin-bottom: 6px;
}
.food-card-price {
  font-size: 14px;
  font-weight: 600;
  color: var(--gold-lt);
}
.food-card-footer {
  padding: 12px 16px;
  border-top: 1px solid var(--glass-border);
  background: rgba(0, 0, 0, 0.1);
}


.confirm-overlay, .ord-overlay {
  position: fixed;
  inset: 0;
  background: rgba(7, 20, 38, 0.8);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  z-index: 500;
  display: none;
  align-items: center;
  justify-content: center;
}
.confirm-overlay.open, .ord-overlay.open {
  display: flex;
}
.confirm-box, .ord-modal {
  background: rgba(19, 45, 82, 0.85);
  border: 1px solid var(--glass-border-focus);
  border-radius: var(--radius);
  padding: 32px;
  max-width: 440px;
  width: 90%;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
  max-height: 90vh;
  overflow-y: auto;
  animation: fadeUp 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.confirm-title, .ord-modal-title {
  font-size: 18px;
  font-weight: 600;
  color: var(--text-light);
  margin-bottom: 14px;
}
.confirm-body {
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 24px;
  line-height: 1.6;
}
.confirm-actions, .ord-modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.toast-container-custom {
  position: fixed;
  top: 85px;
  right: 24px;
  z-index: 9999;
}
.toast-msg {
  background: rgba(11, 31, 58, 0.9);
  backdrop-filter: blur(10px);
  border: 1px solid var(--success);
  color: var(--success);
  padding: 12px 20px;
  border-radius: var(--radius-sm);
  font-size: 13px;
  font-weight: 600;
  box-shadow: 0 10px 30px rgba(0,0,0,0.4);
  animation: slideIn 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.alert {
  padding: 14px 18px;
  border-radius: var(--radius-sm);
  font-size: 13px;
  font-weight: 500;
}
.alert-danger {
  background: rgba(220, 38, 38, 0.12);
  color: var(--danger);
  border: 1px solid rgba(220, 38, 38, 0.2);
}


@keyframes slideIn {
  from { transform: translateX(80px); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}
@keyframes fadeUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.page-header { margin-bottom: 30px; }
.page-title { font-size: 24px; font-weight: 600; color: var(--text-light); letter-spacing: -0.5px; }
.page-sub { font-size: 13px; color: var(--text-muted); margin-top: 4px; }
.filter-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.form-group { margin-bottom: 20px; }
.form-hint { font-size: 11px; color: var(--text-muted); margin-top: 5px; }

.img-preview-wrap {
  border: 2px dashed var(--glass-border);
  border-radius: var(--radius-sm);
  height: 160px;
  overflow: hidden;
  cursor: pointer;
  transition: var(--transition);
  background: rgba(0, 0, 0, 0.1);
}
.img-preview-wrap:hover {
  border-color: var(--gold);
}
.img-placeholder {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--text-muted);
}


@media (max-width: 991.98px) {
  .sidebar {
    transform: translateX(-100%);
    transition: var(--transition);
  }
  .sidebar.open {
    transform: translateX(0);
  }
  .main {
    margin-left: 0;
  }
  .content {
    padding: 24px;
  }
  .topbar {
    padding: 0 24px;
  }
}


.sidebar-toggle {
  display: none;
  background: none;
  border: none;
  color: var(--text-light);
  cursor: pointer;
  padding: 6px;
  margin-right: 12px;
  border-radius: var(--radius-sm);
  transition: var(--transition);
}
.sidebar-toggle:hover {
  background: var(--glass-surface);
}

.sidebar-backdrop {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(2px);
  z-index: 99; 
}
.sidebar-backdrop.open {
  display: block;
}

@media (max-width: 991.98px) {
  .sidebar-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
  }
}
</style>
</head>
<body>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<nav class="sidebar">
  <div class="sidebar-logo">
    <span class="logo-text">Orderly</span>
    <span class="logo-badge">ADMIN</span>
  </div>

  <div class="sidebar-section">Overview</div>
  <a href="admin_dashboard.php" class="nav-item <?= ($activePage==='dashboard')?'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    Dashboard
  </a>

  <div class="sidebar-section">Food System</div>
  <a href="restaurants.php" class="nav-item <?= ($activePage==='restaurants')?'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    Restaurants
  </a>
  <a href="manage_menu.php" class="nav-item <?= in_array($activePage,['manage_menu','add_menu'])?'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 010 8h-1"/><path d="M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
    All Menus
  </a>
  <a href="orders.php" class="nav-item <?= ($activePage==='orders')?'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    All Orders
    <?php if ($pendingOrders > 0): ?><span class="nav-badge"><?= $pendingOrders ?></span><?php endif; ?>
  </a>

  <div class="sidebar-section">User Management</div>
  <a href="admin_users.php" class="nav-item <?= ($activePage==='users')?'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
    Users &amp; Staff
    <?php if ($unassignedVendors > 0): ?><span class="nav-badge amber"><?= $unassignedVendors ?></span><?php endif; ?>
  </a>
  <a href="vendor_assignment.php" class="nav-item <?= ($activePage==='vendor_assignment')?'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
    Vendor Assignment
    <?php if ($unassignedVendors > 0): ?><span class="nav-badge amber"><?= $unassignedVendors ?></span><?php endif; ?>
  </a>

  <div class="sidebar-section">Analytics</div>
  <a href="admin_reports.php" class="nav-item <?= ($activePage==='reports')?'active':'' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    Reports
  </a>

  <div class="sidebar-bottom">
    <div class="user-chip-name"><?= h($_SESSION['name']) ?></div>
    <div class="user-chip-role">Administrator</div>
    <a href="logout.php" class="logout-btn">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</nav>

<div class="main">
  <div class="topbar">
  
  <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="3" y1="6" x2="21" y2="6"/>
      <line x1="3" y1="12" x2="21" y2="12"/>
      <line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>
  <span class="topbar-title"><?= h($pageTitle ?? 'Admin Panel') ?></span>
</div>
  <div class="content">

  <script>
  const toggle    = document.getElementById('sidebarToggle');
  const sidebar   = document.querySelector('.sidebar');
  const backdrop  = document.getElementById('sidebarBackdrop');

  function openSidebar() {
    sidebar.classList.add('open');
    backdrop.classList.add('open');
    document.body.style.overflow = 'hidden'; // prevent background scroll
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    backdrop.classList.remove('open');
    document.body.style.overflow = '';
  }

  toggle.addEventListener('click', () => {
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  });

  // Close when clicking the backdrop
  backdrop.addEventListener('click', closeSidebar);

  // Close when a nav link is tapped on mobile
  document.querySelectorAll('.nav-item').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth < 992) closeSidebar();
    });
  });

  // Reset on resize to desktop
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 992) closeSidebar();
  });
</script>