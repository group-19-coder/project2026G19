<?php
require_once 'config.php';
requireCustomer();

$customerId = intval($_SESSION['user_id']);
$orderId    = intval($_GET['order_id'] ?? 0);

if (!$orderId) { header('Location: my_orders_customer.php'); exit; }

// Ensure columns exist
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'unpaid'");

$order = $conn->query("
    SELECT o.*, r.name AS rest_name
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    WHERE o.id = '$orderId' AND o.customer_id = '$customerId'
")->fetch_assoc();

if (!$order) { header('Location: my_orders_customer.php'); exit; }

// Already paid — go to receipt
if ($order['payment_status'] === 'paid') {
    header("Location: receipt.php?id=$orderId");
    exit;
}

$pageTitle = 'Payment';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Orderly — Payment</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<style>
:root {
  --navy:         #0b1f3a;
  --navy-md:      #132d52;
  --navy-lt:      #1e4175;
  --gold:         #c8963e;
  --gold-lt:      #e0b96a;
  --cream:        #f7f4ef;
  --muted:        #5a6a80;
  --glass-bg:     rgba(255,255,255,.055);
  --glass-border: rgba(255,255,255,.10);
  --shadow-md:    0 8px 40px rgba(0,0,0,.45);
  --red:          #f87171;
  --green:        #4ade80;
}
*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
body {
  font-family:'Poppins',sans-serif;
  min-height:100vh;
  background:var(--navy);
  color:#e2e8f4;
  padding-bottom:60px;
}
body::before {
  content:'';
  position:fixed; inset:0; z-index:-1;
  background:
    radial-gradient(ellipse 60% 50% at 20% 15%, rgba(30,65,117,.55) 0%, transparent 70%),
    radial-gradient(ellipse 50% 45% at 80% 80%, rgba(200,150,62,.07) 0%, transparent 65%),
    #0b1f3a;
}

/* NAV */
nav {
  position:sticky; top:0; z-index:200; height:64px; padding:0 32px;
  display:flex; align-items:center; justify-content:space-between;
  background:rgba(11,31,58,.80); backdrop-filter:blur(20px);
  -webkit-backdrop-filter:blur(20px);
  border-bottom:1px solid rgba(255,255,255,.10);
  box-shadow:0 4px 24px rgba(0,0,0,.30);
}
.nav-logo {
  display:flex; align-items:center; gap:10px;
  text-decoration:none; font-size:17px; font-weight:700; color:#f7f4ef;
}
.nav-logo-icon {
  width:34px; height:34px;
  background:linear-gradient(135deg,var(--gold),var(--gold-lt));
  border-radius:9px;
  display:flex; align-items:center; justify-content:center;
  box-shadow:0 0 24px rgba(200,150,62,.25);
}

/* LAYOUT */
.wrap {
  max-width:860px; margin:0 auto; padding:36px 20px;
}
.page-header {
  margin-bottom:28px;
  animation:fadeUp .4s ease both;
}
@keyframes fadeUp { from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none} }

.page-title  { font-size:24px; font-weight:800; color:var(--cream); letter-spacing:-.02em; margin-bottom:4px; }
.page-sub    { font-size:13px; color:var(--muted); }

.grid {
  display:grid;
  grid-template-columns:1fr 300px;
  gap:22px; align-items:start;
}
@media(max-width:700px){ .grid{ grid-template-columns:1fr; } }

/* CARD */
.card {
  background:rgba(255,255,255,.04);
  backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px);
  border:1px solid var(--glass-border);
  border-radius:18px;
  overflow:hidden;
  animation:fadeUp .45s .05s ease both;
}
.card-head {
  padding:16px 22px;
  border-bottom:1px solid var(--glass-border);
  font-size:13px; font-weight:700; color:var(--cream);
}

/* METHOD TABS */
.methods {
  display:grid; grid-template-columns:repeat(4,1fr);
  gap:0; border-bottom:1px solid var(--glass-border);
}
.method-tab {
  padding:16px 10px;
  display:flex; flex-direction:column; align-items:center; gap:7px;
  cursor:pointer; border:none;
  background:none; color:var(--muted);
  font-family:'Poppins',sans-serif; font-size:10px; font-weight:600;
  text-transform:uppercase; letter-spacing:.05em;
  transition:all .2s; border-right:1px solid var(--glass-border);
  position:relative;
}
.method-tab:last-child { border-right:none; }
.method-tab .tab-icon {
  width:36px; height:36px; border-radius:10px;
  display:flex; align-items:center; justify-content:center;
  font-size:18px;
  background:rgba(255,255,255,.06);
  border:1px solid rgba(255,255,255,.08);
  transition:all .2s;
}
.method-tab:hover { color:var(--cream); }
.method-tab:hover .tab-icon { background:rgba(200,150,62,.12); border-color:rgba(200,150,62,.25); }
.method-tab.active { color:var(--gold-lt); }
.method-tab.active .tab-icon {
  background:linear-gradient(135deg,rgba(200,150,62,.25),rgba(224,185,106,.15));
  border-color:rgba(200,150,62,.4);
}
.method-tab.active::after {
  content:'';
  position:absolute; bottom:-1px; left:0; right:0; height:2px;
  background:linear-gradient(90deg,var(--gold),var(--gold-lt));
}

/* PANELS */
.panel { display:none; padding:24px 22px; }
.panel.active { display:block; }

/* FORM ELEMENTS */
.field { margin-bottom:16px; }
.field label {
  display:block; font-size:10px; font-weight:700;
  color:var(--muted); text-transform:uppercase; letter-spacing:.06em;
  margin-bottom:7px;
}
.field input, .field select {
  width:100%; padding:11px 14px;
  background:rgba(255,255,255,.05);
  border:1px solid var(--glass-border);
  border-radius:10px;
  color:#e2e8f4;
  font-family:'Poppins',sans-serif; font-size:13px;
  outline:none; transition:border-color .2s;
  appearance:none; -webkit-appearance:none;
}
.field input::placeholder { color:var(--muted); }
.field input:focus, .field select:focus { border-color:rgba(200,150,62,.5); }
.field select { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%235a6a80' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 14px center; padding-right:36px; }
.field select option { background:#132d52; color:#e2e8f4; }
.field-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

/* BANK LIST */
.bank-grid {
  display:grid; grid-template-columns:repeat(3,1fr); gap:10px;
  margin-bottom:16px;
}
.bank-item {
  padding:12px 8px;
  background:rgba(255,255,255,.04);
  border:2px solid rgba(255,255,255,.08);
  border-radius:12px;
  display:flex; flex-direction:column; align-items:center; gap:6px;
  cursor:pointer; transition:all .2s;
  font-size:10px; font-weight:600; color:var(--muted); text-align:center;
}
.bank-item:hover { border-color:rgba(200,150,62,.3); color:var(--cream); background:rgba(200,150,62,.06); }
.bank-item.selected { border-color:var(--gold); color:var(--gold-lt); background:rgba(200,150,62,.12); }
.bank-item .bank-logo {
  width:32px; height:32px; border-radius:8px;
  display:flex; align-items:center; justify-content:center;
  font-size:16px; font-weight:800;
}

/* EWALLET LIST */
.wallet-list { display:flex; flex-direction:column; gap:10px; margin-bottom:16px; }
.wallet-item {
  padding:14px 16px;
  background:rgba(255,255,255,.04);
  border:2px solid rgba(255,255,255,.08);
  border-radius:12px;
  display:flex; align-items:center; gap:14px;
  cursor:pointer; transition:all .2s;
}
.wallet-item:hover { border-color:rgba(200,150,62,.3); background:rgba(200,150,62,.06); }
.wallet-item.selected { border-color:var(--gold); background:rgba(200,150,62,.10); }
.wallet-icon {
  width:40px; height:40px; border-radius:10px;
  display:flex; align-items:center; justify-content:center;
  font-size:20px; flex-shrink:0;
}
.wallet-name { font-size:13px; font-weight:600; color:var(--cream); }
.wallet-sub  { font-size:11px; color:var(--muted); margin-top:2px; }
.wallet-check {
  margin-left:auto; width:20px; height:20px; border-radius:50%;
  border:2px solid rgba(255,255,255,.15);
  display:flex; align-items:center; justify-content:center;
  transition:all .2s; flex-shrink:0;
}
.wallet-item.selected .wallet-check {
  background:var(--gold); border-color:var(--gold);
}

/* CASH */
.cash-info {
  background:rgba(74,222,128,.06);
  border:1px solid rgba(74,222,128,.18);
  border-radius:12px; padding:18px;
  display:flex; gap:14px; align-items:flex-start;
  margin-bottom:20px;
}
.cash-icon { font-size:28px; flex-shrink:0; }
.cash-note { font-size:12px; color:#c8d5e8; line-height:1.6; }
.cash-note strong { color:var(--green); display:block; margin-bottom:4px; font-size:13px; }

/* CARD FORMAT HELPERS */
.card-num-display {
  letter-spacing:.18em; font-size:15px; font-weight:700;
  color:var(--gold-lt); text-align:center;
  padding:12px; background:rgba(200,150,62,.07);
  border-radius:9px; margin-bottom:12px;
  font-family:'Courier New',monospace;
  min-height:42px; display:flex; align-items:center; justify-content:center;
}

/* BUTTONS */
.btn-primary {
  display:inline-flex; align-items:center; justify-content:center; gap:8px;
  width:100%; padding:13px;
  background:linear-gradient(135deg,var(--gold),var(--gold-lt));
  color:#0b1f3a; font-family:'Poppins',sans-serif;
  font-size:14px; font-weight:700;
  border:none; border-radius:11px; cursor:pointer;
  box-shadow:0 4px 16px rgba(200,150,62,.35);
  transition:all .2s; text-decoration:none;
}
.btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(200,150,62,.5); }
.btn-primary:disabled { opacity:.5; cursor:not-allowed; transform:none; }
.btn-ghost {
  display:inline-flex; align-items:center; gap:6px;
  padding:9px 18px; border-radius:9px;
  background:rgba(255,255,255,.06); color:#94a3b8;
  border:1px solid rgba(255,255,255,.10);
  font-family:'Poppins',sans-serif; font-size:13px; font-weight:600;
  cursor:pointer; text-decoration:none; transition:all .2s;
}
.btn-ghost:hover { background:rgba(255,255,255,.10); color:var(--cream); }

/* SUMMARY CARD */
.summary {
  background:rgba(255,255,255,.04);
  backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px);
  border:1px solid rgba(200,150,62,.18);
  border-radius:18px; padding:22px;
  box-shadow:0 8px 32px rgba(0,0,0,.3), inset 0 1px 0 rgba(255,255,255,.06);
  position:sticky; top:84px;
  animation:fadeUp .45s .1s ease both;
}
.summary-title { font-size:14px; font-weight:700; color:var(--cream); margin-bottom:18px; }
.summary-row {
  display:flex; justify-content:space-between;
  font-size:12px; color:var(--muted); margin-bottom:10px;
}
.summary-row span:last-child { color:#c8d5e8; font-weight:600; }
.summary-divider { border:none; border-top:1px solid var(--glass-border); margin:14px 0; }
.summary-total {
  display:flex; justify-content:space-between; align-items:center;
  font-size:18px; font-weight:800; color:var(--cream);
}
.summary-total span:last-child { color:var(--gold-lt); }
.order-badge {
  display:inline-flex; padding:4px 12px;
  background:rgba(200,150,62,.12); color:var(--gold-lt);
  border:1px solid rgba(200,150,62,.25); border-radius:20px;
  font-size:11px; font-weight:700; margin-bottom:14px;
}
.secure-note {
  display:flex; align-items:center; gap:6px; justify-content:center;
  margin-top:14px; font-size:11px; color:var(--muted);
}

/* MODAL */
.modal-overlay {
  position:fixed; inset:0; z-index:500;
  background:rgba(0,0,0,.75); backdrop-filter:blur(6px);
  display:none; align-items:center; justify-content:center; padding:20px;
}
.modal-overlay.open { display:flex; }
.modal {
  background:linear-gradient(135deg,#132d52,#0f2548);
  border:1px solid rgba(255,255,255,.10);
  border-radius:22px; padding:36px;
  max-width:400px; width:100%; text-align:center;
  box-shadow:0 24px 60px rgba(0,0,0,.6);
  animation:popIn .35s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes popIn { from{opacity:0;transform:scale(.85)}to{opacity:1;transform:scale(1)} }
.modal-icon {
  width:70px; height:70px; border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  font-size:32px; margin:0 auto 20px;
}
.modal-icon.success { background:rgba(74,222,128,.15); border:2px solid rgba(74,222,128,.3); }
.modal-icon.processing { background:rgba(200,150,62,.15); border:2px solid rgba(200,150,62,.3); }
.modal h2 { font-size:20px; font-weight:800; color:var(--cream); margin-bottom:8px; }
.modal p  { font-size:13px; color:var(--muted); line-height:1.6; }
.modal .amount { font-size:28px; font-weight:800; color:var(--gold-lt); margin:14px 0; }

/* SPINNER */
.spinner {
  width:36px; height:36px; border-radius:50%;
  border:3px solid rgba(200,150,62,.2);
  border-top-color:var(--gold);
  animation:spin .8s linear infinite;
  margin:16px auto;
}
@keyframes spin { to{transform:rotate(360deg)} }

/* INLINE ERROR */
.field-error {
  display:none;
  margin-top:12px;
  padding:10px 14px;
  background:rgba(248,113,113,.10);
  border:1px solid rgba(248,113,113,.30);
  border-radius:9px;
  font-size:12px; font-weight:600;
  color:#f87171;
  display:none;
  align-items:center; gap:8px;
}
.field-error.visible { display:flex; }
.field-error svg { flex-shrink:0; }
.field input.input-err, .field select.input-err {
  border-color:rgba(248,113,113,.55) !important;
}

/* SECURITY BADGE */
.sec-badges {
  display:flex; gap:8px; justify-content:center; margin-top:18px; flex-wrap:wrap;
}
.sec-badge {
  display:flex; align-items:center; gap:4px;
  padding:4px 10px; border-radius:20px;
  background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08);
  font-size:10px; color:var(--muted);
}
</style>
</head>
<body>

<!-- NAV -->
<nav>
  <a href="recommendation.php" class="nav-logo">
    <div class="nav-logo-icon">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="white">
        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
      </svg>
    </div>
   <span style="color:#e0b96a;">Orderly</span>
  </a>
  <a href="my_orders_customer.php" class="btn-ghost" style="font-size:12px;padding:7px 14px;">My Orders</a>
</nav>

<div class="wrap">
  <div class="page-header">
    <p class="page-title">Complete Payment</p>
    <p class="page-sub">Order #<?= str_pad($orderId,4,'0',STR_PAD_LEFT) ?> · <?= h($order['rest_name']) ?></p>
  </div>

  <div class="grid">

    <!-- LEFT: PAYMENT METHODS -->
    <div class="card">
      <div class="card-head">Choose Payment Method</div>

      <!-- TABS -->
      <div class="methods">
        <button class="method-tab active" onclick="switchTab('fpx',this)">
          <div class="tab-icon">🏦</div>
          <span>Online Banking</span>
        </button>
        <button class="method-tab" onclick="switchTab('card',this)">
          <div class="tab-icon">💳</div>
          <span>Card</span>
        </button>
        <button class="method-tab" onclick="switchTab('ewallet',this)">
          <div class="tab-icon">📱</div>
          <span>E-Wallet</span>
        </button>
        <button class="method-tab" onclick="switchTab('cash',this)">
          <div class="tab-icon">💵</div>
          <span>Cash</span>
        </button>
      </div>

      <!-- FPX PANEL -->
      <div class="panel active" id="panel-fpx">
        <p style="font-size:12px;color:var(--muted);margin-bottom:16px;">Select your bank to proceed via FPX online banking.</p>
        <div class="bank-grid" id="bankGrid">
          <?php
          $banks = [
            ['Maybank','MAY','#f6c90e','#1a1a2e'],
            ['CIMB','CIM','#c8102e','#fff'],
            ['Public Bank','PBB','#003087','#fff'],
            ['RHB Bank','RHB','#005baa','#fff'],
            ['Hong Leong','HLB','#e31837','#fff'],
            ['AmBank','AMB','#e31837','#fff'],
            ['Bank Islam','BIM','#006633','#fff'],
            ['Affin Bank','AFF','#0033a0','#fff'],
            ['Alliance','ALL','#e31837','#fff'],
          ];
          foreach ($banks as [$name,$code,$bg,$fg]):
          ?>
          <div class="bank-item" onclick="selectBank(this,'<?= $code ?>')" data-bank="<?= $code ?>">
            <div class="bank-logo" style="background:<?= $bg ?>;color:<?= $fg ?>;font-size:9px;width:36px;height:36px;">
              <?= $code ?>
            </div>
            <span><?= $name ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="field" style="display:none;" id="fpxAccountField">
          <label>Account / IC Number</label>
          <input type="text" id="fpxAccount" placeholder="e.g. 900101-14-5678" maxlength="20" oninput="clearError('fpx'); markInput('fpxAccount',false)"/>
        </div>
        <div class="field-error" id="err-fpx">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
          <span id="err-fpx-msg"></span>
        </div>
        <button class="btn-primary" onclick="pay('fpx')" id="fpxBtn" disabled>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Proceed to Bank
        </button>
      </div>

      <!-- CARD PANEL -->
      <div class="panel" id="panel-card">
        <div class="card-num-display" id="cardDisplay">•••• •••• •••• ••••</div>
        <div class="field">
          <label>Cardholder Name</label>
          <input type="text" id="cardName" placeholder="As on card" oninput="updateCardDisplay();clearError('card');markInput('cardName',false)"/>
        </div>
        <div class="field">
          <label>Card Number</label>
          <input type="text" id="cardNum" placeholder="1234 5678 9012 3456" maxlength="19"
            oninput="formatCard(this); updateCardDisplay(); clearError('card'); markInput('cardNum',false)"/>
        </div>
        <div class="field-row">
          <div class="field">
            <label>Expiry</label>
            <input type="text" id="cardExp" placeholder="MM/YY" maxlength="5" oninput="formatExpiry(this); clearError('card'); markInput('cardExp',false)"/>
          </div>
          <div class="field">
            <label>CVV</label>
            <input type="password" id="cardCvv" placeholder="•••" maxlength="4" oninput="clearError('card'); markInput('cardCvv',false)"/>
          </div>
        </div>
        <div class="sec-badges">
          <span class="sec-badge">🔒 SSL Encrypted</span>
          <span class="sec-badge">✓ Visa / Mastercard</span>
        </div>
        <div class="field-error" id="err-card">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
          <span id="err-card-msg"></span>
        </div>
        <div style="margin-top:16px;">
          <button class="btn-primary" onclick="pay('card')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/></svg>
            Pay RM <?= number_format($order['total_price'],2) ?>
          </button>
        </div>
      </div>

            <!-- E-WALLET PANEL -->
      <div class="panel" id="panel-ewallet">
        <p style="font-size:12px;color:var(--muted);margin-bottom:16px;">Select your e-wallet and enter your registered mobile number.</p>
        <div class="wallet-list">
          <?php
          $wallets = [
            ['tng',       'Touch \'n Go eWallet', 'Malaysia\'s most popular e-wallet', '#00c853'],
            ['grab',      'GrabPay',              'Pay with your Grab credits',        '#00b14f'],
            ['boost',     'Boost',                'Reload & pay instantly',            '#ff5a00'],
            ['maybankqr', 'Maybank QRPay',        'Scan & pay with Maybank app',       '#f6c90e'],
          ];
          foreach ($wallets as [$id,$name,$sub,$col]):
          ?>
          <div class="wallet-item" onclick="selectWallet(this,'<?= $id ?>')" data-wallet="<?= $id ?>">
            <div class="wallet-icon" style="background:<?= $col ?>22;border:1px solid <?= $col ?>33;"></div>
            <div>
              <div class="wallet-name"><?= $name ?></div>
              <div class="wallet-sub"><?= $sub ?></div>
            </div>
            <div class="wallet-check">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="field" id="walletPhoneField" style="display:none;">
          <label>Registered Mobile Number</label>
          <input type="tel" id="walletPhone" placeholder="e.g. 012-3456789" maxlength="15" oninput="clearError('ewallet'); markInput('walletPhone',false)"/>
        </div>
        <div class="field-error" id="err-ewallet">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
          <span id="err-ewallet-msg"></span>
        </div>
        <button class="btn-primary" onclick="pay('ewallet')" id="ewalletBtn" disabled>
          Pay RM <?= number_format($order['total_price'],2) ?>
        </button>
      </div>


      <!-- CASH PANEL -->
      <div class="panel" id="panel-cash">
        <div class="cash-info">
          <div class="cash-icon">💵</div>
          <div class="cash-note">
            <strong>Cash on Delivery</strong>
            Pay the exact amount when collecting your order at the restaurant counter. No change guaranteed.
          </div>
        </div>
        <div style="background:rgba(255,255,255,.03);border:1px solid var(--glass-border);border-radius:12px;padding:16px;margin-bottom:20px;">
          <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:8px;">
            <span>Amount to pay</span>
            <span style="color:var(--gold-lt);font-weight:800;font-size:18px;">RM <?= number_format($order['total_price'],2) ?></span>
          </div>
          <div style="font-size:11px;color:var(--muted);margin-top:6px;">Please have the exact amount ready at the counter.</div>
        </div>
        <button class="btn-primary" onclick="pay('cash')">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Confirm Cash Payment
        </button>
      </div>
    </div>

    <!-- RIGHT: ORDER SUMMARY -->
    <div class="summary">
      <div class="order-badge">Order #<?= str_pad($orderId,4,'0',STR_PAD_LEFT) ?></div>
      <div class="summary-title">Order Summary</div>
      <?php
      $orderItems = $conn->query("
          SELECT oi.quantity, oi.unit_price, oi.subtotal, m.name
          FROM order_items oi
          JOIN menu_items m ON oi.menu_item_id = m.id
          WHERE oi.order_id = '$orderId'
      ");
      while ($oi = $orderItems->fetch_assoc()):
      ?>
      <div class="summary-row">
        <span><?= h($oi['name']) ?> × <?= $oi['quantity'] ?></span>
        <span>RM <?= number_format($oi['subtotal'],2) ?></span>
      </div>
      <?php endwhile; ?>
      <hr class="summary-divider"/>
      <div class="summary-row">
        <span>Restaurant</span>
        <span style="color:var(--gold-lt);"><?= h($order['rest_name']) ?></span>
      </div>
      <hr class="summary-divider"/>
      <div class="summary-total">
        <span>Total</span>
        <span>RM <?= number_format($order['total_price'],2) ?></span>
      </div>
      <div class="secure-note">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#5a6a80" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Secured & Encrypted
      </div>
    </div>

  </div>
</div>

<!-- PROCESSING MODAL -->
<div class="modal-overlay" id="processingModal">
  <div class="modal">
    <div class="modal-icon processing">⏳</div>
    <h2>Processing Payment</h2>
    <p>Please wait while we confirm your payment…</p>
    <div class="spinner"></div>
    <p style="font-size:11px;margin-top:4px;">Do not close this window.</p>
  </div>
</div>

<!-- SUCCESS MODAL -->
<div class="modal-overlay" id="successModal">
  <div class="modal">
    <div class="modal-icon success">✓</div>
    <h2>Payment Successful!</h2>
    <div class="amount">RM <?= number_format($order['total_price'],2) ?></div>
    <p>Your order has been confirmed. Redirecting to your receipt…</p>
    <div class="spinner" style="border-top-color:var(--green);"></div>
  </div>
</div>

<script>
const ORDER_ID   = <?= $orderId ?>;
const HANDLER    = 'payment_handler.php';

// ── TAB SWITCHING ──────────────────────────────────────────
function switchTab(name, el) {
  document.querySelectorAll('.method-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('panel-' + name).classList.add('active');
  ['fpx','card','ewallet'].forEach(m => clearError(m));
}

// ── FPX ───────────────────────────────────────────────────
let selectedBank = null;
function selectBank(el, code) {
  document.querySelectorAll('.bank-item').forEach(b => b.classList.remove('selected'));
  el.classList.add('selected');
  selectedBank = code;
  document.getElementById('fpxAccountField').style.display = 'block';
  document.getElementById('fpxBtn').disabled = false;
  clearError('fpx');
}

// ── CARD ──────────────────────────────────────────────────
function formatCard(input) {
  let v = input.value.replace(/\D/g,'').substring(0,16);
  input.value = v.match(/.{1,4}/g)?.join(' ') || v;
}
function formatExpiry(input) {
  let v = input.value.replace(/\D/g,'');
  if (v.length >= 2) v = v.substring(0,2) + '/' + v.substring(2,4);
  input.value = v;
}
function updateCardDisplay() {
  const num  = document.getElementById('cardNum').value || '•••• •••• •••• ••••';
  const disp = num.padEnd(19,'•').substring(0,19);
  document.getElementById('cardDisplay').textContent = disp || '•••• •••• •••• ••••';
}

// ── EWALLET ───────────────────────────────────────────────
let selectedWallet = null;
function selectWallet(el, id) {
  document.querySelectorAll('.wallet-item').forEach(w => w.classList.remove('selected'));
  el.classList.add('selected');
  selectedWallet = id;
  document.getElementById('walletPhoneField').style.display = 'block';
  document.getElementById('ewalletBtn').disabled = false;
  clearError('ewallet');
}

// ── VALIDATION HELPERS ────────────────────────────────────
function showError(panel, msg) {
  const box = document.getElementById('err-' + panel);
  document.getElementById('err-' + panel + '-msg').textContent = msg;
  box.classList.add('visible');
  box.scrollIntoView({ behavior:'smooth', block:'nearest' });
}
function clearError(panel) {
  document.getElementById('err-' + panel)?.classList.remove('visible');
}
function markInput(id, hasErr) {
  const el = document.getElementById(id);
  if (el) el.classList.toggle('input-err', hasErr);
}

// ── PAY ───────────────────────────────────────────────────
async function pay(method) {
  clearError(method);

  // FPX validation
  if (method === 'fpx') {
    if (!selectedBank) { showError('fpx', 'Please select a bank to continue.'); return; }
    const acc = document.getElementById('fpxAccount').value.trim();
    markInput('fpxAccount', !acc);
    if (!acc) { showError('fpx', 'Please enter your account / IC number.'); return; }
  }

  // Card validation
  if (method === 'card') {
    const num  = document.getElementById('cardNum').value.replace(/\s/g,'');
    const exp  = document.getElementById('cardExp').value;
    const cvv  = document.getElementById('cardCvv').value;
    const name = document.getElementById('cardName').value.trim();
    markInput('cardName', !name);
    markInput('cardNum',  num.length < 16);
    markInput('cardExp',  exp.length < 5);
    markInput('cardCvv',  cvv.length < 3);
    if (!name)           { showError('card', 'Please enter the cardholder name.'); return; }
    if (num.length < 16) { showError('card', 'Please enter a valid 16-digit card number.'); return; }
    if (exp.length < 5)  { showError('card', 'Please enter a valid expiry date (MM/YY).'); return; }
    if (cvv.length < 3)  { showError('card', 'Please enter a valid CVV (3–4 digits).'); return; }
  }

  // E-wallet validation
  if (method === 'ewallet') {
    if (!selectedWallet) { showError('ewallet', 'Please select an e-wallet to continue.'); return; }
    const phone = document.getElementById('walletPhone').value.trim();
    markInput('walletPhone', !phone);
    if (!phone) { showError('ewallet', 'Please enter your registered mobile number.'); return; }
  }

  // Show processing
  document.getElementById('processingModal').classList.add('open');

  // Simulate processing delay
  await new Promise(r => setTimeout(r, 2200));

  // POST to handler
  const fd = new FormData();
  fd.append('order_id', ORDER_ID);
  fd.append('method',   method);
  if (method === 'fpx')     fd.append('bank',   selectedBank);
  if (method === 'ewallet') fd.append('wallet', selectedWallet);

  const res  = await fetch(HANDLER, { method:'POST', body:fd });
  const data = await res.json();

  document.getElementById('processingModal').classList.remove('open');

  if (data.ok) {
    document.getElementById('successModal').classList.add('open');
    setTimeout(() => { window.location = 'receipt.php?id=' + ORDER_ID; }, 2500);
  } else {
    alert(data.msg || 'Payment failed. Please try again.');
  }
}
</script>
</body>
</html>