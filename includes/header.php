<?php

requireVendor();
$pageTitle  = $pageTitle  ?? 'Dashboard';
$activePage = $activePage ?? 'dashboard';
$userName   = h($_SESSION['name'] ?? 'Staff');
$userRole   = h($_SESSION['role'] ?? '');
$initials   = strtoupper(substr($_SESSION['name'] ?? 'S', 0, 1) . (strpos($_SESSION['name'] ?? '', ' ') !== false ? substr(strrchr($_SESSION['name'], ' '), 1, 1) : ''));

$nav = [
    'dashboard'    => ['icon' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',  'label' => 'Dashboard',        'href' => 'dashboard.php',    'group' => 'main'],
    'orders'       => ['icon' => '<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/>',  'label' => 'Order List',   'href' => 'orders.php',       'group' => 'orders'],
    'update_order' => ['icon' => '<polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/>',                                                                                           'label' => 'Update Status',    'href' => 'update_order.php', 'group' => 'orders'],
    'add_menu'     => ['icon' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>',                                                          'label' => 'Add Menu',         'href' => 'add_menu.php',     'group' => 'menu'],
    'manage_menu'  => ['icon' => '<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>',                                   'label' => 'Manage Menu',      'href' => 'manage_menu.php',  'group' => 'menu'],
    'restaurants'  => ['icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',                                                                    'label' => 'Restaurants',      'href' => 'restaurants.php',  'group' => 'menu'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Orderly — <?= h($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
<link rel="stylesheet" href="css/dashboard.css"/>
<style>

svg { max-width: 100%; }
.sidebar-link svg,
.dd-item svg,
.stat-icon svg,
.btn-orderly svg,
.sidebar-toggle svg { width: 16px !important; height: 16px !important; flex-shrink: 0; display: inline-block; vertical-align: middle; }
.logo-icon svg { width: 18px !important; height: 18px !important; }
.sidebar-toggle svg { width: 20px !important; height: 20px !important; }
</style>
</head>
<body>


<nav class="top-navbar">
  <button class="sidebar-toggle d-lg-none" id="sidebarToggle">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>
  <a href="dashboard.php" class="nav-logo">
    <div class="logo-icon">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="white">
        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
      </svg>
    </div>
    <span>Orderly</span>
  </a>
  <div class="nav-right">
    <span class="staff-badge">🏪 <?= ucfirst(str_replace('_',' ',$userRole)) ?></span>
    <div class="avatar-wrap">
      <div class="avatar" id="avatarBtn"><?= $initials ?></div>
      <div class="dropdown-menu-custom" id="avatarDropdown">
        <div class="dd-header">
          <div class="dd-name"><?= $userName ?></div>
          <div class="dd-email"><?= h($_SESSION['email'] ?? '') ?></div>
        </div>
        <a href="logout.php" class="dd-item danger">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
          </svg>
          Log out
        </a>
      </div>
    </div>
  </div>
</nav>


<aside class="sidebar" id="sidebar">
  <div class="sidebar-inner">
    <span class="sidebar-label">Main</span>
    <?php
    $lastGroup = '';
    $groupLabels = ['orders' => 'Orders', 'menu' => 'Menu'];
    foreach ($nav as $key => $item):
        if ($item['group'] !== 'main' && $item['group'] !== $lastGroup):
            $lastGroup = $item['group'];
    ?>
    <span class="sidebar-label"><?= $groupLabels[$item['group']] ?? ucfirst($item['group']) ?></span>
    <?php endif; ?>
    <a href="<?= $item['href'] ?>" class="sidebar-link <?= $activePage === $key ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <?= $item['icon'] ?>
      </svg>
      <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
    <div class="sidebar-divider"></div>
    <a href="logout.php" class="sidebar-link danger">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
      </svg>
      Log Out
    </a>
  </div>
</aside>

<div class="page-overlay" id="pageOverlay"></div>


<main class="main-content">
