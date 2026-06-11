<?php
date_default_timezone_set('Asia/Kuala_Lumpur');
require_once 'config.php';
requireCustomer();

$customerId = intval($_SESSION['user_id']);
$pageTitle  = 'My Orders';

// Ensure payment columns exist
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'unpaid'");

// ── Handle cancellation AJAX ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    header('Content-Type: application/json');

    $orderId = intval($_POST['order_id'] ?? 0);
    $reason  = trim($_POST['reason'] ?? '');

    if (!$orderId || !$reason) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
    }

    // Fetch the order – must belong to this customer and be cancellable
    $stmt = $conn->prepare("SELECT id, status FROM orders WHERE id = ? AND customer_id = ?");
    $stmt->bind_param('ii', $orderId, $customerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    if (!in_array($row['status'], ['pending', 'confirmed'])) {
        echo json_encode(['success' => false, 'message' => 'This order can no longer be cancelled.']);
        exit;
    }

    // Add cancellation_reason column if it doesn't exist yet
    $conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancellation_reason TEXT DEFAULT NULL");

    $upd = $conn->prepare("UPDATE orders SET status = 'cancelled', cancellation_reason = ? WHERE id = ? AND customer_id = ?");
    $upd->bind_param('sii', $reason, $orderId, $customerId);
    $upd->execute();

    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully.']);
    exit;
}

$orders = $conn->query("
    SELECT o.*, r.name AS rest_name, r.area, COUNT(oi.id) AS item_count
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.customer_id = '$customerId'
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

$statusCfg = [
    'pending'   => ['Pending',   '#f59e0b', 'rgba(245,158,11,.12)',  'rgba(245,158,11,.3)'],
    'confirmed' => ['Confirmed', '#60a5fa', 'rgba(96,165,250,.12)',  'rgba(96,165,250,.3)'],
    'preparing' => ['Preparing', '#fb923c', 'rgba(251,146,60,.12)',  'rgba(251,146,60,.3)'],
    'ready'     => ['Ready',     '#4ade80', 'rgba(74,222,128,.12)',  'rgba(74,222,128,.3)'],
    'completed' => ['Done',      '#c8963e', 'rgba(200,150,62,.12)',  'rgba(200,150,62,.3)'],
    'cancelled' => ['Cancelled', '#f87171', 'rgba(248,113,113,.12)', 'rgba(248,113,113,.3)'],
];

require_once 'customer_header.php';
?>

<style>
/* ── Order cards ─────────────────────────────────────────────────────────── */
.order-card {
  background: var(--glass-bg);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border: 1px solid var(--glass-border);
  border-radius: 18px;
  overflow: hidden;
  transition: all .25s;
  animation: fadeUp .4s ease both;
}
.order-card:hover {
  border-color: rgba(200,150,62,.2);
  box-shadow: 0 8px 32px rgba(0,0,0,.3), 0 0 0 1px rgba(200,150,62,.08);
  transform: translateY(-2px);
}
.order-card.is-cancelling {
  border-color: rgba(248,113,113,.35);
  box-shadow: 0 0 0 2px rgba(248,113,113,.12);
}
.order-card-body   { padding: 20px 22px; }
.order-card-footer {
  padding: 12px 22px;
  background: rgba(255,255,255,.025);
  border-top: 1px solid rgba(255,255,255,.06);
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 10px;
}

/* ── Badges ──────────────────────────────────────────────────────────────── */
.status-badge {
  display: inline-flex; align-items: center;
  padding: 3px 11px; border-radius: 20px;
  font-size: 11px; font-weight: 700; letter-spacing: .02em;
}
.pay-badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 20px;
  font-size: 10px; font-weight: 700; letter-spacing: .03em;
}
.pay-badge.paid   { background: rgba(74,222,128,.1);  color: #4ade80; border: 1px solid rgba(74,222,128,.25); }
.pay-badge.unpaid { background: rgba(248,113,113,.1); color: #f87171; border: 1px solid rgba(248,113,113,.25); }

/* ── Progress bar ────────────────────────────────────────────────────────── */
.progress-wrap {
  padding: 14px 22px 16px;
  border-top: 1px solid rgba(255,255,255,.05);
  background: rgba(255,255,255,.015);
}
.progress-track { display: flex; align-items: flex-start; }
.step-dot {
  width: 26px; height: 26px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 700; flex-shrink: 0; transition: all .3s;
}
.step-line  { flex: 1; height: 2px; margin-top: 13px; }
.step-label { font-size: 9px; font-weight: 600; margin-top: 5px; white-space: nowrap; text-align: center; }

/* ── Misc labels ─────────────────────────────────────────────────────────── */
.order-id    { font-size: 15px; font-weight: 800; color: var(--gold-lt); letter-spacing: -.01em; }
.method-label { font-size: 11px; color: var(--muted); display: flex; align-items: center; gap: 4px; }

/* ── Action buttons ──────────────────────────────────────────────────────── */
.receipt-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 14px;
  background: rgba(200,150,62,.1); color: var(--gold-lt);
  border: 1px solid rgba(200,150,62,.28);
  border-radius: 8px; font-size: 12px; font-weight: 600;
  text-decoration: none; transition: all .2s;
}
.receipt-btn:hover { background: rgba(200,150,62,.2); transform: translateY(-1px); }

.pay-now-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 14px;
  background: linear-gradient(135deg, var(--gold), var(--gold-lt));
  color: #0b1f3a; border: none;
  border-radius: 8px; font-size: 12px; font-weight: 700;
  text-decoration: none; transition: all .2s; cursor: pointer;
  box-shadow: 0 3px 12px rgba(200,150,62,.3);
}
.pay-now-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(200,150,62,.45); }

.cancel-btn {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 6px 14px;
  background: rgba(248,113,113,.08); color: #f87171;
  border: 1px solid rgba(248,113,113,.25);
  border-radius: 8px; font-size: 12px; font-weight: 600;
  cursor: pointer; transition: all .2s;
}
.cancel-btn:hover { background: rgba(248,113,113,.18); border-color: rgba(248,113,113,.45); transform: translateY(-1px); }
.cancel-btn:disabled { opacity: .45; cursor: not-allowed; transform: none; }

/* ── Cancellation reason inline panel ────────────────────────────────────── */
.cancel-panel {
  display: none;
  padding: 14px 22px 18px;
  border-top: 1px solid rgba(248,113,113,.18);
  background: rgba(248,113,113,.04);
  animation: slideDown .25s ease;
}
.cancel-panel.open { display: block; }

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-8px); }
  to   { opacity: 1; transform: translateY(0); }
}

.cancel-panel-title {
  font-size: 13px; font-weight: 700; color: #f87171; margin-bottom: 10px;
  display: flex; align-items: center; gap: 6px;
}

.reason-chips { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 12px; }
.reason-chip {
  padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;
  border: 1px solid rgba(248,113,113,.22);
  background: rgba(248,113,113,.06); color: #f87171;
  cursor: pointer; transition: all .18s; user-select: none;
}
.reason-chip:hover,
.reason-chip.selected {
  background: rgba(248,113,113,.22);
  border-color: rgba(248,113,113,.5);
}

.reason-textarea {
  width: 100%; box-sizing: border-box;
  padding: 9px 12px; border-radius: 10px;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(248,113,113,.2);
  color: var(--cream); font-size: 12px; resize: none;
  transition: border-color .2s;
  font-family: inherit;
  display: none;
}
.reason-textarea:focus { outline: none; border-color: rgba(248,113,113,.45); }
.reason-textarea.visible { display: block; }

.cancel-actions { display: flex; gap: 8px; margin-top: 12px; justify-content: flex-end; }

.confirm-cancel-btn {
  padding: 7px 18px;
  background: linear-gradient(135deg, #ef4444, #f87171);
  color: #fff; border: none; border-radius: 8px;
  font-size: 12px; font-weight: 700; cursor: pointer;
  transition: all .2s; box-shadow: 0 3px 10px rgba(239,68,68,.3);
}
.confirm-cancel-btn:hover   { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(239,68,68,.45); }
.confirm-cancel-btn:disabled { opacity: .5; cursor: not-allowed; transform: none; }

.dismiss-cancel-btn {
  padding: 7px 14px;
  background: rgba(255,255,255,.05); color: var(--muted);
  border: 1px solid rgba(255,255,255,.09);
  border-radius: 8px; font-size: 12px; font-weight: 600;
  cursor: pointer; transition: all .2s;
}
.dismiss-cancel-btn:hover { background: rgba(255,255,255,.1); }

/* ── Cancel reason note on cancelled cards ───────────────────────────────── */
.cancel-reason-note {
  font-size: 11px; color: #f87171;
  background: rgba(248,113,113,.07);
  border: 1px solid rgba(248,113,113,.18);
  border-radius: 8px; padding: 6px 11px;
  margin-top: 8px; display: inline-flex; align-items: center; gap: 5px;
  max-width: 100%;
}

/* ── Toast ───────────────────────────────────────────────────────────────── */
#toast-container {
  position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%);
  z-index: 9999; display: flex; flex-direction: column; align-items: center; gap: 8px;
  pointer-events: none;
}
.toast {
  padding: 11px 22px; border-radius: 12px;
  font-size: 13px; font-weight: 600;
  backdrop-filter: blur(16px); pointer-events: none;
  animation: toastIn .3s ease;
  box-shadow: 0 8px 28px rgba(0,0,0,.35);
}
.toast.success { background: rgba(74,222,128,.18); color: #4ade80; border: 1px solid rgba(74,222,128,.3); }
.toast.error   { background: rgba(248,113,113,.18); color: #f87171; border: 1px solid rgba(248,113,113,.3); }
@keyframes toastIn  { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
@keyframes toastOut { from { opacity:1; } to { opacity:0; transform:translateY(6px); } }
</style>

<?php
function payMethodLabel($raw) {
    if (!$raw) return ['—', ''];
    if ($raw === 'cash')   return ['💵 Cash on Delivery', ''];
    if ($raw === 'card')   return ['💳 Credit/Debit Card', ''];
    if (str_starts_with($raw, 'fpx_')) {
        $bank = strtoupper(substr($raw, 4));
        return ["🏦 FPX · $bank", ''];
    }
    if (str_starts_with($raw, 'ewallet_')) {
        $map = ['tng'=>"Touch 'n Go",'grab'=>'GrabPay','boost'=>'Boost','maybankqr'=>'MAE QRPay'];
        $key = substr($raw, 8);
        $name = $map[$key] ?? strtoupper($key);
        return ["📱 $name", ''];
    }
    return [h($raw), ''];
}
?>

<div id="toast-container"></div>

<div class="page-wrap">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;" class="fade-up">
    <div>
      <p class="page-title">My Orders</p>
      <p class="page-sub">Track your orders and download receipts.</p>
    </div>
    <a href="recommendation.php" class="btn-ghost">Find Food</a>
  </div>

  <?php if (!$orders || $orders->num_rows === 0): ?>
  <div class="card empty-state fade-up" style="padding:70px 30px;text-align:center;">
    <div style="font-size:60px;margin-bottom:18px;">🍽️</div>
    <p style="font-size:17px;font-weight:700;color:var(--cream);margin-bottom:8px;">No orders yet</p>
    <p style="font-size:13px;color:var(--muted);margin-bottom:24px;">Place your first order to see it here.</p>
    <a href="recommendation.php" class="btn-primary">Find Food</a>
  </div>

  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:14px;" class="stagger">

    <?php while ($order = $orders->fetch_assoc()):
      [$sLabel,$sColor,$sBg,$sBorder] = $statusCfg[$order['status']] ?? ['·','#94a3b8','rgba(148,163,184,.10)','rgba(148,163,184,.25)'];
      $isPaid      = ($order['payment_status'] ?? 'unpaid') === 'paid';
      $isCash      = ($order['payment_method'] ?? '') === 'cash';
      $isUnpaid    = !$isPaid && !in_array($order['status'], ['cancelled']);
      [$methodStr] = payMethodLabel($order['payment_method'] ?? null);

      // Only pending & confirmed orders can be cancelled by the customer
      $canCancel = in_array($order['status'], ['pending', 'confirmed']);
    ?>
    <div class="order-card" id="order-card-<?= $order['id'] ?>">

      <!-- CARD BODY -->
      <div class="order-card-body">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">

          <!-- Left -->
          <div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:7px;flex-wrap:wrap;">
              <span class="order-id">#<?= str_pad($order['id'],4,'0',STR_PAD_LEFT) ?></span>
              <span class="status-badge" style="background:<?= $sBg ?>;color:<?= $sColor ?>;border:1px solid <?= $sBorder ?>;">
                <?= $sLabel ?>
              </span>
              <?php if ($isPaid): ?>
                <span class="pay-badge paid">✓ Paid</span>
              <?php elseif ($isCash && !in_array($order['status'],['cancelled'])): ?>
                <span class="pay-badge unpaid">Cash on Delivery</span>
              <?php elseif ($isUnpaid): ?>
                <span class="pay-badge unpaid">Unpaid</span>
              <?php endif; ?>
            </div>

            <div style="font-size:13px;font-weight:600;color:var(--cream);margin-bottom:3px;">
              <?= h($order['rest_name']) ?>
              <span style="color:var(--muted);font-weight:400;"> · <?= h($order['area']) ?></span>
            </div>
            <div style="font-size:12px;color:var(--muted);margin-bottom:4px;">
              <?= $order['item_count'] ?> item<?= $order['item_count']>1?'s':'' ?>
              &nbsp;·&nbsp;
              <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
            </div>
            <?php if ($order['payment_method']): ?>
            <div class="method-label">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/></svg>
              <?= $methodStr ?>
            </div>
            <?php endif; ?>
            <?php if ($order['notes']): ?>
            <div style="font-size:11px;color:var(--muted);margin-top:5px;font-style:italic;max-width:360px;">
              "<?= h($order['notes']) ?>"
            </div>
            <?php endif; ?>

            <!-- Show cancellation reason if cancelled -->
            <?php if ($order['status'] === 'cancelled' && !empty($order['cancellation_reason'])): ?>
            <div class="cancel-reason-note">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Reason: <?= h($order['cancellation_reason']) ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- Right: total + actions -->
          <div style="text-align:right;flex-shrink:0;">
            <div style="font-size:22px;font-weight:800;color:var(--cream);margin-bottom:10px;">
              RM <?= number_format($order['total_price'],2) ?>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
              <?php if ($isUnpaid && !$isCash && $order['status'] !== 'cancelled'): ?>
                <a href="payment.php?order_id=<?= $order['id'] ?>" class="pay-now-btn">
                  Pay Now
                </a>
              <?php endif; ?>

              <!-- Cancel button — only for pending / confirmed -->
              <?php if ($canCancel): ?>
              <button
                class="cancel-btn"
                onclick="toggleCancelPanel(<?= $order['id'] ?>)"
                id="cancel-toggle-<?= $order['id'] ?>"
                title="Cancel this order">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Cancel
              </button>
              <?php endif; ?>

              <a href="receipt.php?id=<?= $order['id'] ?>" class="receipt-btn">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Receipt
              </a>
            </div>

            <?php if ($canCancel): ?>
            <div style="font-size:10px;color:rgba(248,113,113,.5);margin-top:8px;text-align:right;">
              Cancel available · <?= $order['status'] === 'pending' ? 'Pending approval' : 'Confirmed' ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- CANCEL PANEL (inline, slides in under card body) -->
      <?php if ($canCancel): ?>
      <div class="cancel-panel" id="cancel-panel-<?= $order['id'] ?>">
        <div class="cancel-panel-title">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          Why do you want to cancel?
        </div>

        <div class="reason-chips" id="chips-<?= $order['id'] ?>">
          <span class="reason-chip" onclick="selectReason(<?= $order['id'] ?>, this, 'Changed my mind')">Changed my mind</span>
          <span class="reason-chip" onclick="selectReason(<?= $order['id'] ?>, this, 'Ordered by mistake')">Ordered by mistake</span>
          <span class="reason-chip" onclick="selectReason(<?= $order['id'] ?>, this, 'Found a better option')">Found a better option</span>
          <span class="reason-chip" onclick="selectReason(<?= $order['id'] ?>, this, 'Taking too long')">Taking too long</span>
          <span class="reason-chip" onclick="selectReason(<?= $order['id'] ?>, this, 'other')">Other…</span>
        </div>

        <textarea
          class="reason-textarea"
          id="custom-reason-<?= $order['id'] ?>"
          rows="2"
          placeholder="Please describe your reason…"
          maxlength="200"></textarea>

        <div class="cancel-actions">
          <button class="dismiss-cancel-btn" onclick="toggleCancelPanel(<?= $order['id'] ?>)">Keep Order</button>
          <button
            class="confirm-cancel-btn"
            id="confirm-cancel-btn-<?= $order['id'] ?>"
            onclick="submitCancellation(<?= $order['id'] ?>)"
            disabled>
            Confirm Cancellation
          </button>
        </div>
      </div>
      <?php endif; ?>

      <!-- PROGRESS BAR (active orders only) -->
      <?php if (!in_array($order['status'], ['completed','cancelled'])): ?>
      <?php
        $steps   = ['pending','confirmed','preparing','ready','completed'];
        $curStep = array_search($order['status'], $steps);
      ?>
      <div class="progress-wrap">
        <div class="progress-track">
          <?php foreach ($steps as $si => $step):
            $done    = $si <= $curStep;
            $current = $si === $curStep;
            [$sl,$sc] = $statusCfg[$step] ?? ['·','#5a6a80'];
            $dotBg     = $done ? $sc    : 'rgba(255,255,255,.05)';
            $dotColor  = $done ? '#fff' : '#3a4a60';
            $dotBorder = $done ? $sc    : 'rgba(255,255,255,.08)';
            $lineBg    = ($si < $curStep) ? $sc : 'rgba(255,255,255,.06)';
          ?>
          <div style="display:flex;flex-direction:column;align-items:center;flex:<?= $si<count($steps)-1?'1':'0' ?>;">
            <div style="display:flex;align-items:center;width:100%;">
              <div class="step-dot" style="background:<?= $dotBg ?>;border:2px solid <?= $dotBorder ?>;color:<?= $dotColor ?>;<?= $current?'box-shadow:0 0 0 3px rgba(200,150,62,.2);':'' ?>">
                <?= ($done && !$current) ? '✓' : ($si+1) ?>
              </div>
              <?php if ($si < count($steps)-1): ?>
              <div class="step-line" style="background:<?= $lineBg ?>;"></div>
              <?php endif; ?>
            </div>
            <div class="step-label" style="color:<?= $done?$sc:'#3a4a60' ?>;"><?= ucfirst($step) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- CARD FOOTER -->
      <div class="order-card-footer">
        <span style="font-size:11px;color:var(--muted);">
          <?php if ($order['status'] === 'completed'): ?>
            ✓ Order completed
          <?php elseif ($order['status'] === 'cancelled'): ?>
            ✕ Order cancelled
          <?php else: ?>
            Last updated <?= date('d M, H:i', strtotime($order['created_at'])) ?>
          <?php endif; ?>
        </span>
        <span style="font-size:10px;color:rgba(90,106,128,.6);">Order #<?= str_pad($order['id'],4,'0',STR_PAD_LEFT) ?></span>
      </div>

    </div>
    <?php endwhile; ?>

  </div>
  <?php endif; ?>
</div>

<script>
/* ── State per order ───────────────────────────────────────────────────── */
const reasonState = {}; // orderId → selected reason string

function toggleCancelPanel(orderId) {
  const panel  = document.getElementById('cancel-panel-' + orderId);
  const card   = document.getElementById('order-card-' + orderId);
  const toggle = document.getElementById('cancel-toggle-' + orderId);
  const isOpen = panel.classList.contains('open');

  if (isOpen) {
    panel.classList.remove('open');
    card.classList.remove('is-cancelling');
    toggle.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Cancel`;
  } else {
    panel.classList.add('open');
    card.classList.add('is-cancelling');
    toggle.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg> Hide`;
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}

function selectReason(orderId, chipEl, reason) {
  // Deselect all chips in this order
  document.querySelectorAll(`#chips-${orderId} .reason-chip`).forEach(c => c.classList.remove('selected'));
  chipEl.classList.add('selected');

  const textarea = document.getElementById('custom-reason-' + orderId);
  const confirmBtn = document.getElementById('confirm-cancel-btn-' + orderId);

  if (reason === 'other') {
    textarea.classList.add('visible');
    textarea.focus();
    reasonState[orderId] = '';
    textarea.oninput = () => {
      reasonState[orderId] = textarea.value.trim();
      confirmBtn.disabled = !reasonState[orderId];
    };
    confirmBtn.disabled = true;
  } else {
    textarea.classList.remove('visible');
    textarea.value = '';
    reasonState[orderId] = reason;
    confirmBtn.disabled = false;
  }
}

async function submitCancellation(orderId) {
  const reason = reasonState[orderId];
  if (!reason) return;

  const confirmBtn = document.getElementById('confirm-cancel-btn-' + orderId);
  confirmBtn.disabled = true;
  confirmBtn.textContent = 'Cancelling…';

  try {
    const body = new FormData();
    body.append('action', 'cancel_order');
    body.append('order_id', orderId);
    body.append('reason', reason);

    const res  = await fetch(window.location.href, { method: 'POST', body });
    const data = await res.json();

    if (data.success) {
      showToast('Order cancelled successfully.', 'success');

      // Visual update: swap status badge & hide cancel button, progress bar
      const card = document.getElementById('order-card-' + orderId);

      // Update status badge
      const badge = card.querySelector('.status-badge');
      if (badge) {
        badge.textContent = 'Cancelled';
        badge.style.cssText += ';background:rgba(248,113,113,.12);color:#f87171;border:1px solid rgba(248,113,113,.3);';
      }

      // Remove cancel button
      const cancelToggle = document.getElementById('cancel-toggle-' + orderId);
      if (cancelToggle) cancelToggle.remove();

      // Remove cancel panel
      const panel = document.getElementById('cancel-panel-' + orderId);
      if (panel) panel.remove();

      // Remove progress bar
      const progressWrap = card.querySelector('.progress-wrap');
      if (progressWrap) progressWrap.remove();

      // Update footer text
      const footerLeft = card.querySelector('.order-card-footer span:first-child');
      if (footerLeft) footerLeft.textContent = '✕ Order cancelled';

      // Remove card highlight
      card.classList.remove('is-cancelling');

      // Add reason note below pay badges
      const badgeRow = card.querySelector('.order-card-body [style*="flex-wrap"]');
      if (badgeRow) {
        const note = document.createElement('div');
        note.className = 'cancel-reason-note';
        note.innerHTML = `<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> Reason: ${escHtml(reason)}`;
        badgeRow.appendChild(note);
      }

    } else {
      showToast(data.message || 'Failed to cancel order.', 'error');
      confirmBtn.disabled = false;
      confirmBtn.textContent = 'Confirm Cancellation';
    }
  } catch (e) {
    showToast('Something went wrong. Please try again.', 'error');
    confirmBtn.disabled = false;
    confirmBtn.textContent = 'Confirm Cancellation';
  }
}

/* ── Toast helper ─────────────────────────────────────────────────────── */
function showToast(msg, type = 'success') {
  const container = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.animation = 'toastOut .3s ease forwards';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}
</script>

<?php require_once 'customer_footer.php'; ?>