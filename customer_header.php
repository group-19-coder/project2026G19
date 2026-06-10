<?php

require_once __DIR__ . '/config.php';
requireCustomer();

$customerId = intval($_SESSION['user_id']);

// Cart count
$cartCount = $conn->query("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE customer_id='$customerId'")->fetch_row()[0] ?? 0;

// User initials
$dbUser    = $conn->query("SELECT name,email FROM users WHERE id='$customerId'")->fetch_assoc();
$nameParts = explode(' ', trim($dbUser['name'] ?? 'Guest'), 2);
$initials  = strtoupper(substr($nameParts[0],0,1).substr($nameParts[1]??'',0,1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Orderly — <?= h($pageTitle ?? 'Menu') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
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
  --cream-dk: #ede8e0;
  --text:     #1a2535;
  --muted:    #5a6a80;
  --success:  #16a34a;
  --danger:   #dc2626;
  --warning:  #f59e0b;
  --info:     #2563eb;
  /* Glass */
  --glass-bg:     rgba(255,255,255,.055);
  --glass-border: rgba(255,255,255,.10);
  --glass-hover:  rgba(255,255,255,.09);
  /* Shadows */
  --shadow-sm:  0 2px 12px rgba(0,0,0,.25);
  --shadow-md:  0 8px 32px rgba(0,0,0,.35);
  --shadow-lg:  0 20px 60px rgba(0,0,0,.45);
  --glow-gold:  0 0 24px rgba(200,150,62,.25);
}


*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }

body {
  font-family: 'Poppins', sans-serif;
  min-height: 100vh;
  background: var(--navy);
  color: #e2e8f4;
  overflow-x: hidden;
}


body::before {
  content: '';
  position: fixed; inset: 0; z-index: -2;
  background:
    radial-gradient(ellipse 70% 55% at 15% 10%,  rgba(30,65,117,.60) 0%, transparent 70%),
    radial-gradient(ellipse 60% 50% at 85% 80%,  rgba(200,150,62,.08) 0%, transparent 65%),
    radial-gradient(ellipse 80% 80% at 50% 50%,  #0d2240 0%, #0b1f3a 100%);
}
body::after {
  content: '';
  position: fixed; inset: 0; z-index: -1;
  background-image:
    radial-gradient(circle at 20% 20%, rgba(200,150,62,.06) 0%, transparent 40%),
    radial-gradient(circle at 80% 70%, rgba(30,65,117,.40) 0%, transparent 50%);
  pointer-events: none;
}


.navbar {
  position: sticky; top: 0; z-index: 200;
  height: 64px;
  padding: 0 32px;
  display: flex; align-items: center; justify-content: space-between;
  background: rgba(11,31,58,.75);
  backdrop-filter: blur(20px) saturate(1.4);
  -webkit-backdrop-filter: blur(20px) saturate(1.4);
  border-bottom: 1px solid var(--glass-border);
  box-shadow: 0 4px 24px rgba(0,0,0,.30);
}


.logo {
  display: flex; align-items: center; gap: 10px;
  text-decoration: none;
  font-size: 17px; font-weight: 700;
  letter-spacing: .01em;
  color: var(--cream);
  transition: opacity .2s;
}
.logo:hover { opacity: .85; }
.logo-icon {
  width: 34px; height: 34px;
  background: linear-gradient(135deg, var(--gold), var(--gold-lt));
  border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  box-shadow: var(--glow-gold);
}
.logo-icon svg { width: 17px; height: 17px; fill: #fff; }
.logo span { color: var(--gold-lt); }


.nav-right { display: flex; align-items: center; gap: 10px; }


.orders-btn {
  display: flex; align-items: center; gap: 6px;
  padding: 7px 16px;
  background: rgba(22,163,74,.12);
  color: #4ade80;
  border: 1px solid rgba(22,163,74,.25);
  border-radius: 9px;
  font-size: 12px; font-weight: 600;
  text-decoration: none;
  transition: all .2s;
}
.orders-btn:hover {
  background: rgba(22,163,74,.22);
  border-color: rgba(22,163,74,.5);
  color: #86efac;
  transform: translateY(-1px);
}


.cart-btn {
  position: relative;
  display: flex; align-items: center; gap: 6px;
  padding: 7px 16px;
  background: var(--gold-dim);
  color: var(--gold-lt);
  border: 1px solid rgba(200,150,62,.3);
  border-radius: 9px;
  font-size: 12px; font-weight: 600;
  text-decoration: none;
  transition: all .2s;
}
.cart-btn:hover {
  background: rgba(200,150,62,.25);
  border-color: rgba(200,150,62,.55);
  color: #f0c96a;
  transform: translateY(-1px);
  box-shadow: var(--glow-gold);
}
.cart-badge {
  position: absolute; top: -6px; right: -6px;
  background: var(--danger);
  color: #fff; font-size: 9px; font-weight: 700;
  width: 17px; height: 17px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  border: 2px solid var(--navy);
  animation: popIn .3s cubic-bezier(.34,1.56,.64,1);
}
@keyframes popIn { from{transform:scale(0)}to{transform:scale(1)} }


.avatar-wrap { position: relative; }
.avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--navy-lt), var(--navy-md));
  border: 2px solid rgba(200,150,62,.4);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; color: var(--gold-lt);
  cursor: pointer;
  transition: all .2s;
}
.avatar:hover { border-color: var(--gold); box-shadow: var(--glow-gold); }


.dropdown {
  position: absolute; top: 46px; right: 0;
  width: 210px;
  background: rgba(13,34,64,.92);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border: 1px solid var(--glass-border);
  border-radius: 14px;
  box-shadow: var(--shadow-lg);
  z-index: 300;
  display: none;
  overflow: hidden;
  transform-origin: top right;
  animation: dropDown .2s ease;
}
@keyframes dropDown { from{opacity:0;transform:scale(.95) translateY(-6px)}to{opacity:1;transform:scale(1) translateY(0)} }
.dropdown.open { display: block; }
.dd-header { padding: 14px 16px; border-bottom: 1px solid var(--glass-border); }
.dd-name  { font-size: 13px; font-weight: 600; color: var(--cream); }
.dd-email { font-size: 11px; color: var(--muted); margin-top: 2px; }
.dd-item {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 16px;
  font-size: 12px; color: #c8d5e8;
  text-decoration: none;
  transition: background .15s;
}
.dd-item:hover { background: var(--glass-hover); }
.dd-item.danger { color: #fca5a5; }
.dd-item.danger:hover { background: rgba(220,38,38,.12); }
.dd-divider { height: 1px; background: var(--glass-border); }


.toast-wrap { position: fixed; top: 74px; right: 20px; z-index: 9999; }
.toast {
  background: rgba(13,34,64,.95);
  backdrop-filter: blur(16px);
  border-left: 3px solid var(--success);
  color: #e2e8f4;
  padding: 12px 18px;
  border-radius: 10px;
  font-size: 13px; font-weight: 500;
  box-shadow: var(--shadow-md);
  margin-bottom: 8px;
  animation: slideIn .3s cubic-bezier(.34,1.56,.64,1);
  max-width: 320px;
}
.toast.error { border-color: var(--danger); }
@keyframes slideIn { from{transform:translateX(60px);opacity:0}to{transform:translateX(0);opacity:1} }


.page-wrap {
  max-width: 1200px;
  margin: 0 auto;
  padding: 32px 24px;
}


.page-title {
  font-size: 24px; font-weight: 700;
  color: var(--cream);
  margin-bottom: 4px;
  letter-spacing: -.01em;
}
.page-sub {
  font-size: 13px; color: var(--muted);
  margin-bottom: 28px;
}


.card {
  background: var(--glass-bg);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid var(--glass-border);
  border-radius: 18px;
  box-shadow: var(--shadow-sm);
  transition: box-shadow .25s, transform .25s;
}
.card:hover { box-shadow: var(--shadow-md); }


.btn-primary {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 20px;
  background: linear-gradient(135deg, var(--gold), var(--gold-lt));
  color: #0b1f3a;
  border: none; border-radius: 9px;
  font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 700;
  cursor: pointer; text-decoration: none;
  box-shadow: 0 4px 16px rgba(200,150,62,.35);
  transition: all .2s;
  letter-spacing: .01em;
}
.btn-primary:hover {
  background: linear-gradient(135deg, var(--gold-lt), #f0c96a);
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(200,150,62,.50);
  color: #0b1f3a;
}

.btn-ghost {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 18px;
  background: var(--glass-bg);
  color: #94a3b8;
  border: 1px solid var(--glass-border);
  border-radius: 9px;
  font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 500;
  cursor: pointer; text-decoration: none;
  transition: all .2s;
}
.btn-ghost:hover {
  background: var(--glass-hover);
  border-color: rgba(200,150,62,.35);
  color: var(--gold-lt);
  transform: translateY(-1px);
}

.btn-danger {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 16px;
  background: rgba(220,38,38,.12);
  color: #fca5a5;
  border: 1px solid rgba(220,38,38,.25);
  border-radius: 8px;
  font-family: 'Poppins', sans-serif; font-size: 12px; font-weight: 600;
  cursor: pointer; text-decoration: none;
  transition: all .2s;
}
.btn-danger:hover { background: rgba(220,38,38,.25); transform: translateY(-1px); }


@keyframes fadeUp {
  from { opacity: 0; transform: translateY(18px); }
  to   { opacity: 1; transform: translateY(0); }
}
.fade-up { animation: fadeUp .45s ease both; }


.stagger > * { animation: fadeUp .4s ease both; }
.stagger > *:nth-child(1) { animation-delay: .05s; }
.stagger > *:nth-child(2) { animation-delay: .10s; }
.stagger > *:nth-child(3) { animation-delay: .15s; }
.stagger > *:nth-child(4) { animation-delay: .20s; }
.stagger > *:nth-child(5) { animation-delay: .25s; }


.divider { height: 1px; background: var(--glass-border); }


::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--navy); }
::-webkit-scrollbar-thumb { background: var(--navy-lt); border-radius: 4px; }
</style>
</head>
<body>
<nav class="navbar">
  <a href="recommendation.php" class="logo">
    <div class="logo-icon">
      <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
    </div>
    <span>Orderly</span>
  </a>
  <div class="nav-right">
    <a href="my_orders_customer.php" class="orders-btn">My Orders</a>
    <a href="cart.php" class="cart-btn">
      Cart
      <?php if ($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
    </a>
    <div class="avatar-wrap">
      <div class="avatar" id="avatarBtn"><?= h($initials) ?></div>
      <div class="dropdown" id="dropdown">
        <div class="dd-header">
          <div class="dd-name"><?= h($dbUser['name']) ?></div>
          <div class="dd-email"><?= h($dbUser['email']) ?></div>
        </div>
        <div class="dd-divider"></div>
        <a href="logout.php" class="dd-item danger">Log out</a>
      </div>
    </div>
  </div>
</nav>
<div class="toast-wrap" id="toastWrap"></div>
<?php if (isset($_SESSION['toast_customer'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_SESSION['toast_customer']) ?>));</script>
<?php unset($_SESSION['toast_customer']); endif; ?>
<script>
const avatarBtn = document.getElementById('avatarBtn');
const dropdown  = document.getElementById('dropdown');
if (avatarBtn) {
  avatarBtn.addEventListener('click', e => { e.stopPropagation(); dropdown.classList.toggle('open'); });
  document.addEventListener('click', () => dropdown.classList.remove('open'));
}
function showToast(msg, type='') {
  const t = document.createElement('div');
  t.className = 'toast' + (type ? ' '+type : '');
  t.textContent = msg;
  document.getElementById('toastWrap').appendChild(t);
  setTimeout(() => t.remove(), 4000);
}
</script>
