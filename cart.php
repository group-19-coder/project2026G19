<?php
require_once 'config.php';
requireCustomer();

$customerId = intval($_SESSION['user_id']);
$pageTitle  = 'My Cart';

// Ensure cart table
$conn->query("CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart (customer_id, menu_item_id)
)");

$cartItems = $conn->query("
    SELECT c.id AS cart_id, c.quantity, m.id AS item_id, m.name, m.price, m.image,
           m.category, m.is_halal, m.is_vegan, m.is_spicy, r.name AS rest_name, r.id AS rest_id
    FROM cart c
    JOIN menu_items m ON c.menu_item_id = m.id
    JOIN restaurants r ON m.restaurant_id = r.id
    WHERE c.customer_id = '$customerId'
    ORDER BY c.added_at ASC
");

$items   = [];
$total   = 0;
$restId  = null;
$restName= '';
while ($row = $cartItems->fetch_assoc()) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $total += $row['subtotal'];
    $restId   = $row['rest_id'];
    $restName = $row['rest_name'];
    $items[]  = $row;
}

require_once 'customer_header.php';
?>

<style>



.card-header {
  padding: 16px 22px;
  border-bottom: 1px solid var(--glass-border);
  display: flex; justify-content: space-between; align-items: center;
}


.cart-row {
  display: flex; align-items: center; gap: 16px;
  padding: 18px 22px;
  border-bottom: 1px solid rgba(255,255,255,.05);
  transition: background .2s;
}
.cart-row:last-child { border-bottom: none; }
.cart-row:hover { background: rgba(255,255,255,.03); }


.food-img {
  width: 66px; height: 66px;
  border-radius: 12px; object-fit: cover; flex-shrink: 0;
  border: 1px solid var(--glass-border);
}
.food-img-placeholder {
  width: 66px; height: 66px;
  border-radius: 12px; flex-shrink: 0;
  background: rgba(255,255,255,.05);
  display: flex; align-items: center; justify-content: center;
  font-size: 28px;
  border: 1px solid var(--glass-border);
}


.tag {
  display: inline-flex; align-items: center; gap: 3px;
  padding: 2px 8px;
  border-radius: 20px;
  font-size: 10px; font-weight: 600;
  margin-right: 4px;
}
.tag-halal  { background: rgba(22,163,74,.15);  color: #4ade80;  border: 1px solid rgba(22,163,74,.25); }
.tag-vegan  { background: rgba(16,185,129,.15); color: #34d399;  border: 1px solid rgba(16,185,129,.25); }
.tag-spicy  { background: rgba(239,68,68,.15);  color: #f87171;  border: 1px solid rgba(239,68,68,.25); }


.qty-btn {
  width: 30px; height: 30px;
  border-radius: 8px;
  border: 1px solid var(--glass-border);
  background: rgba(255,255,255,.06);
  color: #c8d5e8; font-size: 16px; font-weight: 600;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all .2s; flex-shrink: 0;
}
.qty-btn:hover {
  background: linear-gradient(135deg, var(--gold), var(--gold-lt));
  color: #0b1f3a;
  border-color: var(--gold);
  transform: scale(1.08);
}


.summary-card {
  position: sticky; top: 80px;
  background: rgba(255,255,255,.05);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(200,150,62,.2);
  border-radius: 18px;
  padding: 24px;
  box-shadow: 0 8px 32px rgba(0,0,0,.3), inset 0 1px 0 rgba(255,255,255,.07);
}


.notes-input {
  width: 100%; padding: 10px 14px;
  background: rgba(255,255,255,.05);
  border: 1px solid var(--glass-border);
  border-radius: 9px;
  color: #e2e8f4;
  font-family: 'Poppins', sans-serif; font-size: 12px;
  resize: vertical; min-height: 72px;
  outline: none;
  transition: border-color .2s;
}
.notes-input::placeholder { color: var(--muted); }
.notes-input:focus { border-color: rgba(200,150,62,.45); }


.empty-state {
  padding: 70px 30px;
  text-align: center;
}


.clear-btn {
  font-size: 12px; color: #f87171;
  background: rgba(220,38,38,.1);
  border: 1px solid rgba(220,38,38,.2);
  border-radius: 7px;
  padding: 5px 12px;
  cursor: pointer; font-weight: 600;
  transition: all .2s;
}
.clear-btn:hover { background: rgba(220,38,38,.22); }


.remove-link {
  font-size: 11px; color: #f87171;
  background: none; border: none;
  cursor: pointer; font-family: 'Poppins',sans-serif;
  margin-top: 4px; transition: color .15s;
}
.remove-link:hover { color: #fca5a5; }


.cart-grid {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 22px;
  align-items: start;
}
@media (max-width: 760px) {
  .cart-grid { grid-template-columns: 1fr; }
  .summary-card { position: static; }
}
</style>

<div class="page-wrap">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;" class="fade-up">
    <div>
      <p class="page-title">My Cart</p>
      <p class="page-sub">
        <?= $restName
          ? 'Items from <strong style="color:var(--gold-lt);">'.h($restName).'</strong>'
          : 'Your cart is empty' ?>
      </p>
    </div>
    <a href="recommendation.php" class="btn-ghost">Back</a>
  </div>

  <?php if (empty($items)): ?>
  
  <div class="card empty-state fade-up">
    <div style="font-size:60px;margin-bottom:18px;"></div>
    <p style="font-size:17px;font-weight:700;color:var(--cream);margin-bottom:8px;">Your cart is empty</p>
    <p style="font-size:13px;color:var(--muted);margin-bottom:24px;">Browse recommendations and add items to get started.</p>
    <a href="recommendation.php" class="btn-primary">Find Food</a>
  </div>

  <?php else: ?>
 
  <div class="cart-grid stagger">

    
    <div class="card" style="overflow:hidden;">
      <div class="card-header">
        <span style="font-size:13px;font-weight:700;color:var(--cream);">
          <?= count($items) ?> item<?= count($items)>1?'s':'' ?>
        </span>
        <button onclick="clearCart()" class="clear-btn">Clear All</button>
      </div>

      <?php foreach ($items as $item): ?>
      <div class="cart-row" id="row_<?= $item['cart_id'] ?>">

        
        <?php if ($item['image'] && file_exists('uploads/food_images/'.$item['image'])): ?>
          <img src="uploads/food_images/<?= h($item['image']) ?>" class="food-img">
        <?php else: ?>
          <div class="food-img-placeholder"></div>
        <?php endif; ?>

        
        <div style="flex:1;min-width:0;">
          <div style="font-size:14px;font-weight:600;color:var(--cream);margin-bottom:4px;"><?= h($item['name']) ?></div>
          <div style="margin-bottom:6px;font-size:11px;color:var(--muted);"><?= h($item['category']) ?></div>
          <div>
            <?php if ($item['is_halal']): ?><span class="tag tag-halal">Halal</span><?php endif; ?>
            <?php if ($item['is_vegan']): ?><span class="tag tag-vegan">Vegan</span><?php endif; ?>
            <?php if ($item['is_spicy']): ?><span class="tag tag-spicy">Spicy</span><?php endif; ?>
          </div>
          <div style="font-size:12px;font-weight:600;color:var(--gold-lt);margin-top:5px;">RM <?= number_format($item['price'],2) ?> each</div>
        </div>

    
        <div style="display:flex;align-items:center;gap:9px;flex-shrink:0;">
          <button onclick="changeQty(<?= $item['cart_id'] ?>, -1, <?= $item['price'] ?>)" class="qty-btn">−</button>
          <span id="qty_<?= $item['cart_id'] ?>" style="font-size:14px;font-weight:700;min-width:22px;text-align:center;color:var(--cream);"><?= $item['quantity'] ?></span>
          <button onclick="changeQty(<?= $item['cart_id'] ?>, +1, <?= $item['price'] ?>)" class="qty-btn">+</button>
        </div>

        
        <div style="text-align:right;flex-shrink:0;min-width:76px;">
          <div id="sub_<?= $item['cart_id'] ?>" style="font-size:14px;font-weight:700;color:var(--gold-lt);">RM <?= number_format($item['subtotal'],2) ?></div>
          <button onclick="removeItem(<?= $item['cart_id'] ?>)" class="remove-link">Remove</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    
    <div class="summary-card">
      <p style="font-size:15px;font-weight:700;color:var(--cream);margin-bottom:18px;letter-spacing:.01em;">
        Order Summary
      </p>

      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:10px;">
        <span>Subtotal</span>
        <span id="summaryTotal" style="color:#c8d5e8;font-weight:600;">RM <?= number_format($total,2) ?></span>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:18px;">
        <span>Restaurant</span>
        <span style="color:var(--gold-lt);font-weight:600;max-width:160px;text-align:right;"><?= h($restName) ?></span>
      </div>

      <div style="border-top:1px solid var(--glass-border);padding-top:16px;display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <span style="font-size:15px;font-weight:700;color:var(--cream);">Total</span>
        <span id="summaryTotalBig" style="font-size:20px;font-weight:800;color:var(--gold-lt);">RM <?= number_format($total,2) ?></span>
      </div>

      <form method="POST" action="place_order.php">
        <div style="margin-bottom:14px;">
          <label style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:6px;">
            Notes (optional)
          </label>
          <textarea name="notes" placeholder="Any special requests…" class="notes-input"></textarea>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:13px;font-size:14px;">
          Place Order
        </button>
      </form>

      <a href="recommendation.php" class="btn-ghost" style="width:100%;justify-content:center;margin-top:10px;padding:11px;">
        Add More Items
      </a>
    </div>

  </div>
  <?php endif; ?>
</div>

<script>
const CART_HANDLER = 'cart_handler.php';

async function post(data) {
  const fd = new FormData();
  Object.entries(data).forEach(([k,v]) => fd.append(k,v));
  const r = await fetch(CART_HANDLER, { method:'POST', body:fd });
  return r.json();
}

async function changeQty(cartId, delta, unitPrice) {
  const qtyEl = document.getElementById('qty_' + cartId);
  const current = parseInt(qtyEl.textContent);
  const newQty = current + delta;

  if (newQty < 1) return;

  const res = await post({ action:'update', cart_id:cartId, qty:newQty });
  if (!res.ok) return;

  qtyEl.textContent = newQty;
  document.getElementById('sub_'+cartId).textContent = 'RM ' + (unitPrice * newQty).toFixed(2);
  updateBadge(res.count);
  const t = 'RM ' + res.total.toFixed(2);
  document.getElementById('summaryTotal').textContent    = t;
  document.getElementById('summaryTotalBig').textContent = t;
}

async function removeItem(cartId) {
  const res = await post({ action:'remove', cart_id:cartId });
  if (res.ok) { document.getElementById('row_'+cartId)?.remove(); updateBadge(res.count); location.reload(); }
}

async function clearCart() {
  if (!confirm('Clear all items from your cart?')) return;
  const res = await post({ action:'clear' });
  if (res.ok) { updateBadge(0); location.reload(); }
}

function updateBadge(count) {
  const b = document.querySelector('.cart-badge');
  if (b) b.textContent = count;
}
</script>

<?php require_once 'customer_footer.php'; ?>