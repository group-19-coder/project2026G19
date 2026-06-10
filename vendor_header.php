<?php

requireVendor();
if (isAdmin()) { header("Location: admin_dashboard.php"); exit(); }
$vendorRestId = getVendorRestaurantId();
$myRest = $vendorRestId ? $conn->query("SELECT name, area FROM restaurants WHERE id='$vendorRestId'")->fetch_assoc() : null;
$pendingCount = $vendorRestId ? ($conn->query("SELECT COUNT(*) FROM orders WHERE restaurant_id='$vendorRestId' AND status='pending'")->fetch_row()[0] ?? 0) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Orderly — <?= h($pageTitle ?? 'Dashboard') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800&display=swap" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
<style>

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
 
 --navy: #0b1f3a;
 --navy-md: #132d52;
 --navy-lt: #1e4175;
 --navy-xlt: #253a68;
 --gold: #c8963e;
 --gold-lt: #e0b96a;
 --gold-dim: rgba(200,150,62,.18);
 --gold-glow:rgba(200,150,62,.35);
 --cream: #f7f4ef;
 --cream-dk: #ede8e0;

 
 --text: #1a2535;
 --text-inv: #e8edf5;
 --muted: #5a6a80;
 --muted-lt: #8a9ab8;
 --success: #16a34a;
 --danger: #dc2626;
 --warning: #f59e0b;
 --info: #2563eb;

 
 --glass: rgba(255,255,255,.055);
 --glass-border: rgba(255,255,255,.10);
 --glass-hover: rgba(255,255,255,.09);
 --glass-strong: rgba(255,255,255,.10);

 
 --sidebar-w: 240px;

 
 --shadow-sm: 0 2px 12px rgba(0,0,0,.25);
 --shadow-md: 0 8px 32px rgba(0,0,0,.35);
 --shadow-lg: 0 20px 60px rgba(0,0,0,.45);
 --gold-shadow:0 4px 20px rgba(200,150,62,.3);
}


body {
 font-family: 'Poppins', sans-serif;
 background: var(--navy);
 color: var(--text-inv);
 min-height: 100vh;
 display: flex;
 overflow-x: hidden;
}


body::before, body::after {
 content: '';
 position: fixed;
 border-radius: 50%;
 pointer-events: none;
 z-index: 0;
}
body::before {
 width: 700px; height: 700px;
 top: -200px; left: -200px;
 background: radial-gradient(circle, rgba(30,65,117,.6) 0%, transparent 70%);
 animation: glowPulse 8s ease-in-out infinite alternate;
}
body::after {
 width: 500px; height: 500px;
 bottom: -100px; right: -100px;
 background: radial-gradient(circle, rgba(200,150,62,.12) 0%, transparent 70%);
 animation: glowPulse 10s ease-in-out infinite alternate-reverse;
}

@keyframes glowPulse {
 0% { opacity: .6; transform: scale(1); }
 100% { opacity: 1; transform: scale(1.1); }
}


.sidebar {
 width: var(--sidebar-w);
 min-height: 100vh;
 background: linear-gradient(180deg, rgba(13,30,60,.97) 0%, rgba(11,31,58,.99) 100%);
 border-right: 1px solid var(--glass-border);
 display: flex;
 flex-direction: column;
 position: fixed;
 top: 0; left: 0;
 z-index: 200;
 backdrop-filter: blur(20px);
 -webkit-backdrop-filter: blur(20px);
 box-shadow: 4px 0 40px rgba(0,0,0,.4);
 transition: transform .3s cubic-bezier(.4,0,.2,1);
}


.sidebar-logo {
 padding: 22px 20px 18px;
 display: flex;
 align-items: center;
 gap: 12px;
 border-bottom: 1px solid var(--glass-border);
}
.logo-icon {
 width: 36px; height: 36px;
 background: linear-gradient(135deg, var(--gold) 0%, var(--gold-lt) 100%);
 border-radius: 10px;
 display: flex;
 align-items: center;
 justify-content: center;
 font-size: 16px;
 flex-shrink: 0;
 box-shadow: var(--gold-shadow);
}
.logo-text {
 font-size: 17px;
 font-weight: 800;
 background: linear-gradient(135deg, #fff 0%, var(--gold-lt) 100%);
 -webkit-background-clip: text;
 -webkit-text-fill-color: transparent;
 background-clip: text;
 letter-spacing: -.3px;
}
.role-tag {
 font-size: 8px;
 font-weight: 700;
 background: var(--gold-dim);
 color: var(--gold-lt);
 padding: 2px 8px;
 border-radius: 20px;
 border: 1px solid var(--gold-glow);
 margin-left: auto;
 letter-spacing: .08em;
}


.rest-chip {
 margin: 14px 12px 0;
 padding: 12px 14px;
 background: linear-gradient(135deg, rgba(200,150,62,.12) 0%, rgba(200,150,62,.06) 100%);
 border-radius: 12px;
 border: 1px solid rgba(200,150,62,.25);
}
.rest-chip-name {
 font-size: 12px;
 font-weight: 700;
 color: var(--gold-lt);
}
.rest-chip-area {
 font-size: 10px;
 color: var(--muted-lt);
 margin-top: 2px;
}


.nav-section {
 padding: 18px 18px 6px;
 font-size: 9px;
 font-weight: 700;
 color: rgba(255,255,255,.25);
 letter-spacing: .15em;
 text-transform: uppercase;
}

.nav-item {
 display: flex;
 align-items: center;
 gap: 10px;
 padding: 9px 14px;
 border-radius: 10px;
 margin: 2px 8px;
 font-size: 13px;
 font-weight: 500;
 color: rgba(255,255,255,.45);
 text-decoration: none;
 transition: all .2s cubic-bezier(.4,0,.2,1);
 position: relative;
 overflow: hidden;
}
.nav-item::before {
 content: '';
 position: absolute;
 inset: 0;
 background: linear-gradient(135deg, var(--gold-dim) 0%, transparent 100%);
 opacity: 0;
 transition: opacity .2s;
 border-radius: 10px;
}
.nav-item:hover {
 color: rgba(255,255,255,.9);
 background: var(--glass-hover);
 transform: translateX(3px);
}
.nav-item:hover::before { opacity: 1; }
.nav-item.active {
 color: var(--gold-lt);
 background: var(--gold-dim);
 border: 1px solid rgba(200,150,62,.25);
 font-weight: 600;
 box-shadow: 0 2px 12px rgba(200,150,62,.15);
}
.nav-item.active::before { opacity: 1; }
.nav-item svg {
 width: 15px; height: 15px;
 flex-shrink: 0;
 opacity: .8;
}
.nav-item.active svg { opacity: 1; stroke: var(--gold-lt); }

.nav-badge {
 margin-left: auto;
 font-size: 10px;
 font-weight: 700;
 padding: 2px 7px;
 border-radius: 10px;
 background: rgba(200,150,62,.25);
 color: var(--gold-lt);
 border: 1px solid var(--gold-glow);
}

/* Sidebar bottom */
.sidebar-bottom {
 margin-top: auto;
 padding: 16px;
 border-top: 1px solid var(--glass-border);
}
.user-name {
 font-size: 12px;
 font-weight: 700;
 color: rgba(255,255,255,.85);
}
.user-role {
 font-size: 10px;
 color: var(--muted-lt);
 margin-top: 1px;
}
.logout-btn {
 display: flex;
 align-items: center;
 gap: 6px;
 font-size: 11px;
 color: var(--muted-lt);
 text-decoration: none;
 margin-top: 10px;
 transition: color .2s;
}
.logout-btn:hover { color: #ef4444; }


.main {
 margin-left: var(--sidebar-w);
 flex: 1;
 min-height: 100vh;
 display: flex;
 flex-direction: column;
 position: relative;
 z-index: 1;
}


.topbar {
 height: 58px;
 background: rgba(11,31,58,.8);
 backdrop-filter: blur(20px);
 -webkit-backdrop-filter: blur(20px);
 border-bottom: 1px solid var(--glass-border);
 display: flex;
 align-items: center;
 padding: 0 28px;
 position: sticky;
 top: 0;
 z-index: 100;
 box-shadow: 0 4px 20px rgba(0,0,0,.2);
}
.topbar-title {
 font-size: 14px;
 font-weight: 700;
 color: rgba(255,255,255,.9);
 letter-spacing: .01em;
}


.content {
 padding: 28px;
 flex: 1;
}


.page-header {
 margin-bottom: 28px;
 animation: fadeUp .5s ease both;
}
.page-title {
 font-size: 22px;
 font-weight: 800;
 color: #fff;
 letter-spacing: -.3px;
}
.page-sub {
 font-size: 13px;
 color: var(--muted-lt);
 margin-top: 4px;
}
.page-sub strong { color: var(--gold-lt); font-weight: 600; }


.dash-card {
 background: var(--glass-strong);
 backdrop-filter: blur(20px);
 -webkit-backdrop-filter: blur(20px);
 border: 1px solid var(--glass-border);
 border-radius: 18px;
 overflow: hidden;
 box-shadow: var(--shadow-md);
 transition: transform .25s ease, box-shadow .25s ease;
}
.dash-card:hover {
 transform: translateY(-2px);
 box-shadow: 0 12px 40px rgba(0,0,0,.45);
}
.dash-card-header {
 padding: 16px 20px;
 border-bottom: 1px solid var(--glass-border);
 display: flex;
 align-items: center;
 justify-content: space-between;
 background: rgba(255,255,255,.03);
}
.dash-card-title {
 font-size: 14px;
 font-weight: 700;
 color: rgba(255,255,255,.9);
}
.dash-card-body {
 padding: 20px;
}


.stat-card {
 background: var(--glass-strong);
 backdrop-filter: blur(20px);
 -webkit-backdrop-filter: blur(20px);
 border: 1px solid var(--glass-border);
 border-radius: 16px;
 padding: 20px 16px;
 text-align: center;
 box-shadow: var(--shadow-sm);
 transition: all .25s cubic-bezier(.4,0,.2,1);
 animation: fadeUp .5s ease both;
 position: relative;
 overflow: hidden;
}
.stat-card::before {
 content: '';
 position: absolute;
 top: 0; left: 0; right: 0;
 height: 2px;
 background: linear-gradient(90deg, transparent, var(--gold), transparent);
 opacity: 0;
 transition: opacity .25s;
}
.stat-card:hover {
 transform: translateY(-4px);
 box-shadow: 0 12px 35px rgba(0,0,0,.4), 0 0 20px var(--gold-dim);
 border-color: rgba(200,150,62,.3);
}
.stat-card:hover::before { opacity: 1; }
.stat-icon {
 width: 42px; height: 42px;
 border-radius: 12px;
 background: linear-gradient(135deg, rgba(200,150,62,.2) 0%, rgba(200,150,62,.08) 100%);
 border: 1px solid rgba(200,150,62,.2);
 display: flex;
 align-items: center;
 justify-content: center;
 margin: 0 auto 10px;
}
.stat-icon svg {
 width: 18px; height: 18px;
 stroke: var(--gold-lt);
 fill: none;
 stroke-width: 1.8;
 stroke-linecap: round;
 stroke-linejoin: round;
}
.stat-val {
 font-size: 26px;
 font-weight: 800;
 color: #fff;
 letter-spacing: -.5px;
 line-height: 1;
 margin-bottom: 4px;
}
.stat-lbl {
 font-size: 11px;
 color: var(--muted-lt);
 font-weight: 500;
 letter-spacing: .02em;
}


.btn-orderly {
 display: inline-flex;
 align-items: center;
 gap: 7px;
 padding: 10px 20px;
 background: linear-gradient(135deg, var(--gold) 0%, var(--gold-lt) 100%);
 color: var(--navy);
 border: none;
 border-radius: 10px;
 font-family: 'Poppins', sans-serif;
 font-size: 13px;
 font-weight: 700;
 cursor: pointer;
 text-decoration: none;
 box-shadow: var(--gold-shadow);
 transition: all .2s cubic-bezier(.4,0,.2,1);
}
.btn-orderly:hover {
 color: var(--navy);
 transform: translateY(-2px);
 box-shadow: 0 8px 25px rgba(200,150,62,.5);
 filter: brightness(1.08);
}
.btn-edit-sm {
 padding: 4px 12px;
 background: rgba(200,150,62,.15);
 color: var(--gold-lt);
 border: 1px solid rgba(200,150,62,.3);
 border-radius: 6px;
 font-size: 12px;
 font-weight: 600;
 text-decoration: none;
 cursor: pointer;
 display: inline-block;
 transition: all .18s;
}
.btn-edit-sm:hover {
 background: rgba(200,150,62,.25);
 color: var(--gold-lt);
 transform: translateY(-1px);
}
.btn-danger-sm {
 padding: 4px 12px;
 background: rgba(220,38,38,.12);
 color: #ef4444;
 border: 1px solid rgba(220,38,38,.25);
 border-radius: 6px;
 font-size: 12px;
 font-weight: 600;
 cursor: pointer;
 display: inline-block;
 transition: all .18s;
}
.btn-danger-sm:hover { background: rgba(220,38,38,.22); }


.badge-pill {
 display: inline-flex;
 align-items: center;
 padding: 4px 12px;
 border-radius: 20px;
 font-size: 11px;
 font-weight: 600;
}
.bp-blue {
 background: rgba(37,99,235,.15);
 color: #7ba9f8;
 border: 1px solid rgba(37,99,235,.25);
}
.bp-green {
 background: rgba(22,163,74,.12);
 color: #4ade80;
 border: 1px solid rgba(22,163,74,.25);
}
.avail-on {
 background: rgba(22,163,74,.12);
 color: #4ade80;
 border: 1px solid rgba(22,163,74,.25);
}
.avail-off {
 background: rgba(220,38,38,.12);
 color: #f87171;
 border: 1px solid rgba(220,38,38,.22);
}


.form-select, .form-control {
 font-size: 13px;
 background: rgba(255,255,255,.06);
 border: 1px solid var(--glass-border);
 border-radius: 10px;
 padding: 9px 14px;
 color: rgba(255,255,255,.85);
 font-family: 'Poppins', sans-serif;
 transition: border-color .2s, box-shadow .2s;
}
.form-select:focus, .form-control:focus {
 border-color: rgba(200,150,62,.5);
 box-shadow: 0 0 0 3px rgba(200,150,62,.1);
 outline: none;
 background: rgba(255,255,255,.08);
 color: #fff;
}
.form-select option { background: var(--navy-md); color: #fff; }
.form-label {
 font-size: 12px;
 font-weight: 600;
 margin-bottom: 6px;
 color: rgba(255,255,255,.7);
}
.req { color: #f87171; }


.table {
 font-size: 13px;
 color: rgba(255,255,255,.8);
 --bs-table-bg: transparent;
 --bs-table-hover-bg: rgba(255,255,255,.04);
}
.table th {
 font-size: 10px;
 font-weight: 700;
 color: var(--muted-lt);
 text-transform: uppercase;
 letter-spacing: .08em;
 border-bottom: 1px solid var(--glass-border) !important;
 padding: 12px 16px;
 background: rgba(255,255,255,.03);
}
.table td {
 padding: 12px 16px;
 border-color: var(--glass-border);
 vertical-align: middle;
}
.table tbody tr { transition: background .15s; }
.table tbody tr:hover { background: rgba(255,255,255,.035); }


.order-status-badge {
 display: inline-flex;
 align-items: center;
 padding: 4px 11px;
 border-radius: 20px;
 font-size: 11px;
 font-weight: 700;
 white-space: nowrap;
}
.status-pending { background: rgba(245,158,11,.12); color: #fbbf24; border: 1px solid rgba(245,158,11,.25); }
.status-confirmed { background: rgba(37,99,235,.12); color: #7ba9f8; border: 1px solid rgba(37,99,235,.25); }
.status-preparing { background: rgba(139,92,246,.12); color: #c4b5fd; border: 1px solid rgba(139,92,246,.25); }
.status-ready { background: rgba(22,163,74,.12); color: #4ade80; border: 1px solid rgba(22,163,74,.25); }
.status-completed { background: rgba(200,150,62,.12); color: var(--gold-lt); border: 1px solid var(--gold-glow); }
.status-cancelled { background: rgba(220,38,38,.12); color: #f87171; border: 1px solid rgba(220,38,38,.22); }


.food-card {
 background: var(--glass);
 backdrop-filter: blur(16px);
 -webkit-backdrop-filter: blur(16px);
 border: 1px solid var(--glass-border);
 border-radius: 14px;
 overflow: hidden;
 transition: all .25s cubic-bezier(.4,0,.2,1);
}
.food-card:hover {
 transform: translateY(-5px);
 box-shadow: 0 16px 40px rgba(0,0,0,.45), 0 0 0 1px rgba(200,150,62,.2);
}
.food-card-img { width: 100%; height: 110px; object-fit: cover; }
.food-card-img-placeholder {
 width: 100%; height: 110px;
 background: linear-gradient(135deg, rgba(200,150,62,.1) 0%, rgba(30,65,117,.4) 100%);
 display: flex;
 align-items: center;
 justify-content: center;
 font-size: 32px;
}
.food-card-body { padding: 12px 14px; }
.food-card-name {
 font-size: 13px;
 font-weight: 700;
 margin-bottom: 5px;
 white-space: nowrap;
 overflow: hidden;
 text-overflow: ellipsis;
 color: rgba(255,255,255,.9);
}
.food-card-rest { font-size: 11px; color: var(--muted-lt); margin-bottom: 6px; }
.food-card-price { font-size: 13px; font-weight: 700; color: var(--gold-lt); }
.food-card-footer {
 padding: 9px 14px;
 border-top: 1px solid var(--glass-border);
 background: rgba(255,255,255,.025);
}


.progress-track {
 background: rgba(255,255,255,.07);
 border-radius: 6px;
 height: 6px;
 overflow: hidden;
}
.progress-fill {
 height: 6px;
 border-radius: 6px;
 transition: width .6s cubic-bezier(.4,0,.2,1);
}


.alert-no-rest {
 text-align: center;
 padding: 70px 30px;
 background: var(--glass);
 backdrop-filter: blur(20px);
 -webkit-backdrop-filter: blur(20px);
 border: 1px dashed rgba(200,150,62,.3);
 border-radius: 20px;
 color: rgba(255,255,255,.7);
 animation: fadeUp .5s ease both;
}
.alert {
 padding: 14px 18px;
 border-radius: 12px;
 font-size: 13px;
}
.alert-danger {
 background: rgba(220,38,38,.1);
 color: #f87171;
 border: 1px solid rgba(220,38,38,.22);
}


.toast-container-custom {
 position: fixed;
 top: 72px; right: 20px;
 z-index: 9999;
}
.toast-msg {
 background: rgba(13,30,60,.95);
 backdrop-filter: blur(20px);
 border: 1px solid rgba(22,163,74,.4);
 color: #4ade80;
 padding: 12px 20px;
 border-radius: 12px;
 font-size: 13px;
 font-weight: 600;
 box-shadow: 0 8px 30px rgba(0,0,0,.4);
 animation: toastSlide .35s cubic-bezier(.4,0,.2,1) both;
}
@keyframes toastSlide {
 from { transform: translateX(80px); opacity: 0; }
 to { transform: translateX(0); opacity: 1; }
}


.filter-bar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.img-preview-wrap {
 border: 2px dashed rgba(200,150,62,.3);
 border-radius: 12px;
 height: 160px;
 overflow: hidden;
 cursor: pointer;
}
#imgPreview { height: 100%; }
.img-placeholder {
 height: 100%;
 display: flex;
 flex-direction: column;
 align-items: center;
 justify-content: center;
 color: var(--muted-lt);
}
.img-placeholder .icon { font-size: 32px; margin-bottom: 8px; }


.confirm-overlay {
 position: fixed; inset: 0;
 background: rgba(0,0,0,.65);
 backdrop-filter: blur(6px);
 z-index: 500;
 display: none;
 align-items: center;
 justify-content: center;
}
.confirm-overlay.open { display: flex; }
.confirm-box {
 background: rgba(13,30,60,.97);
 backdrop-filter: blur(20px);
 border: 1px solid var(--glass-border);
 border-radius: 20px;
 padding: 32px;
 max-width: 400px; width: 90%;
 box-shadow: var(--shadow-lg);
 animation: fadeUp .3s ease both;
}
.confirm-title { font-size: 17px; font-weight: 700; margin-bottom: 10px; color: #fff; }
.confirm-body { font-size: 13px; color: var(--muted-lt); margin-bottom: 24px; }
.confirm-actions { display: flex; gap: 12px; justify-content: flex-end; }


@keyframes fadeUp {
 from { opacity: 0; transform: translateY(18px); }
 to { opacity: 1; transform: translateY(0); }
}


.col-6:nth-child(1) .stat-card { animation-delay: .05s; }
.col-6:nth-child(2) .stat-card { animation-delay: .10s; }
.col-6:nth-child(3) .stat-card { animation-delay: .15s; }
.col-6:nth-child(4) .stat-card { animation-delay: .20s; }
.col-6:nth-child(5) .stat-card { animation-delay: .25s; }
.col-6:nth-child(6) .stat-card { animation-delay: .30s; }


@media (max-width: 768px) {
 .sidebar { transform: translateX(-100%); }
 .sidebar.open { transform: translateX(0); }
 .main { margin-left: 0; }
 .content { padding: 16px; }
 :root { --sidebar-w: 240px; }
}
</style>
</head>
<body>


<nav class="sidebar">
 <div class="sidebar-logo">

 <span class="logo-text">Orderly</span>
 <span class="role-tag">STAFF</span>
 </div>

 <?php if ($myRest): ?>
 <div class="rest-chip">
 <div class="rest-chip-name"> <?= h($myRest['name']) ?></div>
 <div class="rest-chip-area"> <?= h($myRest['area']) ?></div>
 </div>
 <?php endif; ?>

 <div class="nav-section">Main</div>
 <a href="dashboard.php" class="nav-item <?= $activePage==='dashboard'?'active':'' ?>">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
 Dashboard
 </a>

 <div class="nav-section">Restaurant</div>
 <a href="my_restaurant.php" class="nav-item <?= $activePage==='my_restaurant'?'active':'' ?>">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
 My Restaurant
 </a>
 <a href="manage_menu.php" class="nav-item <?= $activePage==='manage_menu'?'active':'' ?>">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 010 8h-1"/><path d="M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
 My Menu
 </a>
 <a href="add_menu.php" class="nav-item <?= $activePage==='add_menu'?'active':'' ?>">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
 Add Item
 </a>

 <div class="nav-section">Orders</div>
 <a href="my_orders.php" class="nav-item <?= $activePage==='orders'?'active':'' ?>">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
 My Orders
 <?php if ($pendingCount > 0): ?>
 <span class="nav-badge"><?= $pendingCount ?></span>
 <?php endif; ?>
 </a>

 <div class="sidebar-bottom">
 <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
 <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-lt));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--navy);flex-shrink:0;">
 <?= strtoupper(substr($_SESSION['name'],0,1)) ?>
 </div>
 <div>
 <div class="user-name"><?= h($_SESSION['name']) ?></div>
 <div class="user-role">Vendor Staff</div>
 </div>
 </div>
 <a href="logout.php" class="logout-btn">
 <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
 Logout
 </a>
 </div>
</nav>


<div class="main">
 <div class="topbar">
 <span class="topbar-title"><?= h($pageTitle ?? 'Dashboard') ?></span>
 </div>
 <div class="content">