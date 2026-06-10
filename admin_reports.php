<?php

require_once 'config.php';
requireAdmin();

$revenueByRest = $conn->query("
    SELECT r.name, r.area, 
           COUNT(DISTINCT o.id) AS order_count,
           COALESCE(SUM(o.total_price),0) AS revenue
    FROM restaurants r
    LEFT JOIN orders o ON o.restaurant_id = r.id AND o.status = 'completed'
    GROUP BY r.id
    ORDER BY revenue DESC
");

$ordersByStatus = $conn->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status ORDER BY cnt DESC");
$statusData = [];
while ($r = $ordersByStatus->fetch_assoc()) $statusData[$r['status']] = $r['cnt'];
$totalOrders = array_sum($statusData);

$topMenus = $conn->query("
    SELECT m.name, m.price, m.category, r.name AS rest_name,
           COUNT(oi.id) AS order_count,
           COALESCE(SUM(oi.subtotal), 0) AS total_revenue
    FROM menu_items m
    JOIN order_items oi ON oi.menu_item_id = m.id
    JOIN restaurants r ON m.restaurant_id = r.id
    GROUP BY m.id
    ORDER BY order_count DESC
    LIMIT 10
");


$catBreak = $conn->query("SELECT category, COUNT(*) AS cnt FROM menu_items GROUP BY category ORDER BY cnt DESC");


$budBreak = $conn->query("SELECT budget_category, COUNT(*) AS cnt FROM menu_items GROUP BY budget_category");
$budData = ['Cheap'=>0,'Moderate'=>0,'Expensive'=>0];
while ($b = $budBreak->fetch_assoc()) $budData[$b['budget_category']] = (int)$b['cnt'];
$totalMenus = array_sum($budData);

$userGrowth = $conn->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') AS month,
           SUM(role='customer') AS customers,
           SUM(role='vendor_staff') AS vendors
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY MIN(created_at)
");

$totalRevenue  = $conn->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status='completed'")->fetch_row()[0];
$totalCustomers= $conn->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetch_row()[0];
$totalMenuCount= $conn->query("SELECT COUNT(*) FROM menu_items")->fetch_row()[0];
$avgOrderValue = $totalOrders > 0 ? (float)$totalRevenue / max(1, ($statusData['completed'] ?? 1)) : 0;


$dietStats = $conn->query("
    SELECT 
      SUM(is_halal) AS halal, SUM(is_non_halal) AS non_halal,
      SUM(is_vegetarian) AS vegetarian, SUM(is_vegan) AS vegan,
      SUM(is_high_protein) AS high_protein,
      SUM(is_spicy) AS spicy, SUM(is_non_spicy) AS non_spicy
    FROM menu_items
")->fetch_assoc();

$allergyStats = $conn->query("
    SELECT
      SUM(has_peanut) AS peanut, SUM(has_seafood) AS seafood,
      SUM(has_soy) AS soy, SUM(has_milk) AS milk, SUM(has_gluten) AS gluten
    FROM menu_items
")->fetch_assoc();

// ── Cancellation reasons breakdown ────────────────────────────────────────────
$cancelReasons = $conn->query("
    SELECT cancellation_reason, COUNT(*) AS cnt
    FROM orders
    WHERE status = 'cancelled' AND cancellation_reason IS NOT NULL AND cancellation_reason != ''
    GROUP BY cancellation_reason
    ORDER BY cnt DESC
    LIMIT 10
");
$totalCancelled = $statusData['cancelled'] ?? 0;

$pageTitle  = 'Reports';
$activePage = 'reports';
require_once 'admin_header.php';
?>

<style>

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.kpi {
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius);
  padding: 24px;
  border-top: 3px solid var(--kpi-color, var(--gold));
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), border-color 0.3s;
}

.kpi:hover {
  transform: translateY(-5px);
  border-color: var(--kpi-color, var(--gold-lt));
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
}

.kpi-icon-text {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  margin-bottom: 6px;
  font-weight: 600;
}

.kpi-val {
  font-size: 26px;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: -0.5px;
}

.kpi-lbl {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 6px;
  font-weight: 500;
}

.grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 24px;
}

.grid-3c {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 24px;
  margin-bottom: 24px;
}

@media(max-width: 991.98px) {
  .grid-2, .grid-3c {
    grid-template-columns: 1fr;
  }
}


.card {
  background: rgba(255, 255, 255, 0.03) !important;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
  border-radius: var(--radius) !important;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  margin-bottom: 0;
}

.card-header {
  padding: 18px 24px !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
  background: rgba(11, 31, 58, 0.3) !important;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.card-title {
  font-size: 15px;
  font-weight: 600;
  color: #ffffff !important;
  letter-spacing: 0.3px;
}

.card-body {
  padding: 24px !important;
  color: var(--text-light) !important;
}

.tbl-wrap {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.data-table th {
  padding: 14px 18px;
  font-size: 11px;
  font-weight: 600;
  color: var(--gold-lt);
  text-transform: uppercase;
  letter-spacing: .08em;
  border-bottom: 2px solid rgba(255, 255, 255, 0.08);
  background: rgba(11, 31, 58, 0.4);
  text-align: left;
}

.data-table td {
  padding: 14px 18px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  vertical-align: middle;
  color: #ffffff !important;
}

.data-table tr:last-child td {
  border-bottom: none;
}

.data-table tr:hover td {
  background: rgba(255, 255, 255, 0.02);
}

/* Contextual Badges */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
}

.badge-green {
  background: rgba(22, 163, 74, 0.15);
  color: var(--success);
  border: 1px solid rgba(22, 163, 74, 0.25);
}

.badge-blue {
  background: rgba(37, 99, 235, 0.15);
  color: var(--info);
  border: 1px solid rgba(37, 99, 235, 0.25);
}

.progress-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
}

.progress-row:last-child {
  margin-bottom: 0;
}

.progress-label {
  font-size: 12px;
  font-weight: 500;
  min-width: 90px;
  color: #ffffff !important;
}

.progress-bar-wrap {
  flex: 1;
  height: 8px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.04);
}

.progress-bar {
  height: 100%;
  border-radius: 6px;
  transition: width .6s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.progress-val {
  font-size: 12px;
  color: var(--text-muted);
  min-width: 65px;
  text-align: right;
  font-weight: 500;
}

.chip-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.chip {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  padding: 6px 14px;
  font-size: 12px;
  color: #ffffff;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: var(--transition);
}

.chip:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: var(--gold-lt);
}

.chip-num {
  font-weight: 600;
  color: var(--gold-lt);
}

.report-timestamp {
  font-size: 12px;
  color: var(--text-muted);
  margin-bottom: 24px;
  font-weight: 500;
}

.report-footer {
  margin-top: 24px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: var(--radius-sm);
  font-size: 12px;
  color: var(--text-muted);
  text-align: center;
}
</style>

    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
      <div class="report-timestamp" style="margin-bottom:0;">
        Generated: <?= date('d F Y, H:i') ?> · Operational Scope: Admin Session (<?= h($_SESSION['name']) ?>)
      </div>
      <button onclick="generatePDF()" id="pdfBtn" style="
        display:inline-flex;align-items:center;gap:8px;
        padding:10px 20px;border-radius:9px;border:none;cursor:pointer;
        background:linear-gradient(135deg,#c8963e,#e8b84b);
        color:#0b1f3a;font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;
        box-shadow:0 4px 16px rgba(200,150,62,.3);transition:all .2s;
      "
      onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 22px rgba(200,150,62,.45)'"
      onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 16px rgba(200,150,62,.3)'">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/>
        </svg>
        Download PDF Report
      </button>
    </div>

    <div class="stats-grid">
      <div class="kpi" style="--kpi-color: var(--success);">
        <div class="kpi-icon-text">Revenue</div>
        <div class="kpi-val">RM <?= number_format($totalRevenue, 2) ?></div>
        <div class="kpi-lbl">Total Revenue (Completed)</div>
      </div>
      <div class="kpi" style="--kpi-color: var(--info);">
        <div class="kpi-icon-text">Volume</div>
        <div class="kpi-val"><?= number_format($totalOrders) ?></div>
        <div class="kpi-lbl">Total Orders Logged</div>
      </div>
      <div class="kpi" style="--kpi-color: var(--warning);">
        <div class="kpi-icon-text">Accounts</div>
        <div class="kpi-val"><?= number_format($totalCustomers) ?></div>
        <div class="kpi-lbl">Registered Customers</div>
      </div>
      <div class="kpi" style="--kpi-color: var(--gold);">
        <div class="kpi-icon-text">Catalog</div>
        <div class="kpi-val"><?= number_format($totalMenuCount) ?></div>
        <div class="kpi-lbl">Active Menu Database Items</div>
      </div>
    </div>

    <div class="grid-2">

      <div class="card">
        <div class="card-header"><span class="card-title">Restaurant Performance &amp; Revenue Metrics</span></div>
        <div class="tbl-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Restaurant Name</th>
                <th>Regional Area</th>
                <th>Orders Filled</th>
                <th>Gross Revenue</th>
              </tr>
            </thead>
            <tbody>
            <?php $revenueByRest->data_seek(0); while ($r = $revenueByRest->fetch_assoc()): ?>
            <tr>
              <td><strong style="color: #ffffff; font-weight: 500;"><?= h($r['name']) ?></strong></td>
              <td><span class="badge <?= $r['area']==='Setapak' ? 'badge-green' : 'badge-blue' ?>"><?= h($r['area']) ?></span></td>
              <td style="color: var(--text-muted);"><?= number_format($r['order_count']) ?></td>
              <td><strong style="color: var(--gold-lt); font-weight: 600;">RM <?= number_format($r['revenue'], 2) ?></strong></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><span class="card-title">Real-Time Order Processing Breakdown</span></div>
        <div class="card-body">
          <?php
          // Colors mapped explicitly to fit the requested theme variables
          $statusColors = [
            'pending'   => 'var(--warning)',
            'confirmed' => 'var(--info)',
            'preparing' => 'var(--preparing)',
            'ready'     => 'var(--success)',
            'completed' => 'var(--gold)',
            'cancelled' => 'var(--danger)'
          ];
          foreach ($statusData as $status => $cnt):
            $pct = $totalOrders > 0 ? round($cnt / $totalOrders * 100) : 0;
            $col = $statusColors[$status] ?? 'var(--text-muted)';
          ?>
          <div class="progress-row">
            <span class="progress-label" style="text-transform: capitalize;"><?= h($status) ?></span>
            <div class="progress-bar-wrap">
              <div class="progress-bar" style="width: <?= $pct ?>%; background: <?= $col ?>;"></div>
            </div>
            <span class="progress-val"><?= number_format($cnt) ?> <span style="font-size: 11px; opacity: 0.7;">(<?= $pct ?>%)</span></span>
          </div>
          <?php endforeach; ?>
          <?php if (!$totalOrders): ?>
          <p style="font-size: 13px; color: var(--text-muted); text-align: center; padding: 24px 0;">No order logs present in the current cycle.</p>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <div class="grid-3c">

      <div class="card">
        <div class="card-header"><span class="card-title">Top 10 High-Velocity Menu Items</span></div>
        <div class="tbl-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Rank</th>
                <th>Menu Item Specification</th>
                <th>Restaurant Entity</th>
                <th>Volume</th>
                <th>Yield Contribution</th>
              </tr>
            </thead>
            <tbody>
            <?php if ($topMenus && $topMenus->num_rows > 0):
              $rank = 1;
              while ($m = $topMenus->fetch_assoc()):
            ?>
            <tr>
              <td style="font-weight: 600; color: var(--gold-lt); font-size: 14px;"><?= $rank++ ?></td>
              <td>
                <div style="font-weight: 500; color: #ffffff;"><?= h($m['name']) ?></div>
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 1px;"><?= h($m['category']) ?></div>
              </td>
              <td style="font-size: 12px; color: var(--text-muted);"><?= h($m['rest_name']) ?></td>
              <td><strong style="color: #ffffff; font-weight: 500;"><?= number_format($m['order_count']) ?>×</strong></td>
              <td style="color: var(--success); font-weight: 600;">RM <?= number_format($m['total_revenue'], 2) ?></td>
            </tr>
            <?php endwhile;
            else: ?>
            <tr><td colspan="5" style="text-align: center; padding: 32px; color: var(--text-muted);">No menu performance metric records discovered.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><span class="card-title">Menu Tiers &amp; Pricing Spreads</span></div>
        <div class="card-body">
          <?php
          $budColors = ['Cheap' => 'var(--success)', 'Moderate' => 'var(--warning)', 'Expensive' => 'var(--danger)'];
          foreach ($budData as $label => $count):
            $pct = $totalMenus > 0 ? round($count / $totalMenus * 100) : 0;
          ?>
          <div class="progress-row">
            <span class="progress-label"><?= h($label) ?></span>
            <div class="progress-bar-wrap">
              <div class="progress-bar" style="width: <?= $pct ?>%; background: <?= $budColors[$label] ?? 'var(--gold)' ?>;"></div>
            </div>
            <span class="progress-val" style="color: #ffffff;"><?= number_format($count) ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="card-header" style="border-top: 1px solid rgba(255, 255, 255, 0.08);"><span class="card-title">Lifestyle &amp; Dietary Tags Summary</span></div>
        <div class="card-body">
          <div class="chip-grid">
            <?php
            $dietLabels = [
              'halal'        => 'Halal',
              'non_halal'    => 'Non-Halal',
              'vegetarian'   => 'Veg',
              'vegan'        => 'Vegan',
              'high_protein' => 'Hi-Protein',
              'spicy'        => 'Spicy',
              'non_spicy'    => 'Mild'
            ];
            foreach ($dietLabels as $key => $label): ?>
            <div class="chip"><?= h($label) ?> <span class="chip-num"><?= (int)($dietStats[$key] ?? 0) ?></span></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><span class="card-title">Menu Category Distribution</span></div>
        <div class="card-body">
          <?php $catBreak->data_seek(0); while ($c = $catBreak->fetch_assoc()):
            $pct = $totalMenus > 0 ? round($c['cnt'] / $totalMenus * 100) : 0;
          ?>
          <div class="progress-row">
            <span class="progress-label" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= h($c['category']) ?></span>
            <div class="progress-bar-wrap">
              <div class="progress-bar" style="width: <?= $pct ?>%; background: var(--gold-lt);"></div>
            </div>
            <span class="progress-val" style="color: #ffffff;"><?= number_format($c['cnt']) ?></span>
          </div>
          <?php endwhile; ?>
        </div>

        <div class="card-header" style="border-top: 1px solid rgba(255, 255, 255, 0.08);"><span class="card-title">Micro Allergen Matrix Track</span></div>
        <div class="card-body">
          <div class="chip-grid">
            <?php
            $allergyLabels = [
              'peanut'  => 'Peanut',
              'seafood' => 'Seafood',
              'soy'     => 'Soy',
              'milk'    => 'Milk',
              'gluten'  => 'Gluten'
            ];
            foreach ($allergyLabels as $key => $label): ?>
            <div class="chip">
              <?= h($label) ?> 
              <span class="chip-num" style="color: var(--danger); font-weight: 600;">
                <?= (int)($allergyStats[$key] ?? 0) ?>
              </span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>

    <?php if ($totalCancelled > 0): ?>
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header">
        <span class="card-title">Cancellation Reasons Breakdown</span>
        <span style="font-size:12px;color:var(--text-muted);"><?= number_format($totalCancelled) ?> cancelled order<?= $totalCancelled !== 1 ? 's' : '' ?> total</span>
      </div>
      <div class="card-body">
        <?php
        $reasonRows = [];
        if ($cancelReasons && $cancelReasons->num_rows > 0) {
            while ($r = $cancelReasons->fetch_assoc()) $reasonRows[] = $r;
        }
        $knownMax = !empty($reasonRows) ? $reasonRows[0]['cnt'] : 1;
        if (!empty($reasonRows)):
        ?>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <?php foreach ($reasonRows as $rr):
            $pct = $totalCancelled > 0 ? round($rr['cnt'] / $totalCancelled * 100) : 0;
          ?>
          <div class="progress-row" style="align-items:center;">
            <span class="progress-label" style="min-width:170px;max-width:170px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;" title="<?= h($rr['cancellation_reason']) ?>">
              <?= h($rr['cancellation_reason']) ?>
            </span>
            <div class="progress-bar-wrap">
              <div class="progress-bar" style="width:<?= round($rr['cnt'] / $knownMax * 100) ?>%;background:var(--danger);opacity:.75;"></div>
            </div>
            <span class="progress-val" style="color:#f87171;min-width:70px;text-align:right;">
              <?= number_format($rr['cnt']) ?> <span style="font-size:11px;opacity:.7;">(<?= $pct ?>%)</span>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="font-size:13px;color:var(--text-muted);text-align:center;padding:20px 0;">No cancellation reasons recorded yet.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="report-footer">
      SaaS Operational Audit Metrics Ledger · Core Orderly Engine Layer · Audited on <?= date('d F Y \a\t H:i') ?>
    </div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script>
function generatePDF() {
  const btn = document.getElementById('pdfBtn');
  btn.textContent = 'Generating…';
  btn.disabled = true;

  const { jsPDF } = window.jspdf;
  const doc  = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
  const W    = doc.internal.pageSize.getWidth();

  // Colour palette
  const navy    = [11, 31, 58];
  const gold    = [200, 150, 62];
  const goldLt  = [232, 184, 75];
  const white   = [255, 255, 255];
  const muted   = [140, 160, 185];
  const success = [34, 197, 94];
  const danger  = [239, 68, 68];
  const info    = [59, 130, 246];

  const now = new Date().toLocaleString('en-MY', { dateStyle:'long', timeStyle:'short' });

  doc.setFillColor(...navy);
  doc.rect(0, 0, W, 36, 'F');
  doc.setFillColor(...gold);
  doc.rect(0, 36, W, 2, 'F');

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(22);
  doc.setTextColor(...goldLt);
  doc.text('Orderly', 14, 18);

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(10);
  doc.setTextColor(...muted);
  doc.text('Smart Food Ordering System', 14, 26);

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(13);
  doc.setTextColor(...white);
  doc.text('Admin Summary Report', W - 14, 16, { align: 'right' });

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(9);
  doc.setTextColor(...muted);
  doc.text('Generated: ' + now, W - 14, 23, { align: 'right' });
  doc.text('By: <?= addslashes(h($_SESSION['name'])) ?>', W - 14, 30, { align: 'right' });

  let y = 48;

  function checkPage(needed = 20) {
    if (y + needed > 274) {
      doc.addPage();
      doc.setFillColor(...navy);
      doc.rect(0, 0, W, 10, 'F');
      doc.setFillColor(...gold);
      doc.rect(0, 10, W, 1, 'F');
      y = 18;
    }
  }

  function sectionTitle(title) {
    checkPage(18);
    doc.setFillColor(22, 44, 74);
    doc.roundedRect(12, y, W - 24, 10, 2, 2, 'F');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    doc.setTextColor(...goldLt);
    doc.text(title, 17, y + 7);
    y += 14;
  }

  sectionTitle('Key Performance Indicators');

  const kpis = [
    { label: 'Total Revenue',   value: 'RM <?= number_format($totalRevenue,2) ?>',  color: success },
    { label: 'Total Orders',    value: '<?= number_format($totalOrders) ?>',          color: info },
    { label: 'Customers',       value: '<?= number_format($totalCustomers) ?>',       color: [234,179,8] },
    { label: 'Avg Order Value', value: 'RM <?= number_format($avgOrderValue,2) ?>',  color: [168,85,247] },
    { label: 'Menu Items',      value: '<?= number_format($totalMenuCount) ?>',       color: [...goldLt] },
  ];

  const boxW = (W - 28 - 16) / 5;
  kpis.forEach((k, i) => {
    const x = 14 + i * (boxW + 4);
    doc.setFillColor(20, 40, 68);
    doc.roundedRect(x, y, boxW, 22, 2, 2, 'F');
    doc.setFillColor(...k.color);
    doc.rect(x, y, boxW, 1.5, 'F');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    doc.setTextColor(...white);
    doc.text(k.value, x + boxW / 2, y + 12, { align: 'center' });
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(7);
    doc.setTextColor(...muted);
    doc.text(k.label, x + boxW / 2, y + 18.5, { align: 'center' });
  });
  y += 30;


  sectionTitle('Order Status Breakdown');

  const statusData = [
    <?php
    $statusColors2 = [
      'pending'   => '[234,179,8]',
      'confirmed' => '[59,130,246]',
      'preparing' => '[249,115,22]',
      'ready'     => '[34,197,94]',
      'completed' => '[200,150,62]',
      'cancelled' => '[239,68,68]',
    ];
    foreach ($statusData as $st => $cnt):
      $pct = $totalOrders > 0 ? round($cnt / $totalOrders * 100) : 0;
      $col = $statusColors2[$st] ?? '[160,175,195]';
      echo "{ label: '" . ucfirst($st) . "', cnt: $cnt, pct: $pct, col: $col },\n";
    endforeach;
    ?>
  ];

  const barMaxW = W - 28 - 45 - 25;
  statusData.forEach(s => {
    checkPage(12);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    doc.setTextColor(...white);
    doc.text(s.label, 14, y + 4.5);
    doc.setFillColor(30, 50, 80);
    doc.roundedRect(52, y, barMaxW, 6, 1, 1, 'F');
    if (s.pct > 0) {
      doc.setFillColor(...s.col);
      doc.roundedRect(52, y, Math.max((s.pct / 100) * barMaxW, 2), 6, 1, 1, 'F');
    }
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.setTextColor(...white);
    doc.text(s.cnt + '  (' + s.pct + '%)', W - 14, y + 4.5, { align: 'right' });
    y += 11;
  });
  y += 6;

  
  checkPage(40);
  sectionTitle('Restaurant Performance');

  const restRows = [
    <?php
    $revenueByRest->data_seek(0);
    while ($r = $revenueByRest->fetch_assoc()) {
      echo "['" . addslashes($r['name']) . "', '" . addslashes($r['area']) . "', " . (int)$r['order_count'] . ", 'RM " . number_format($r['revenue'],2) . "'],\n";
    }
    ?>
  ];

  doc.autoTable({
    startY: y,
    head: [['Restaurant', 'Area', 'Orders', 'Revenue']],
    body: restRows,
    theme: 'plain',
    styles: { font:'helvetica', fontSize:9, textColor:white, cellPadding:4 },
    headStyles: { fillColor:[22,44,74], textColor:goldLt, fontStyle:'bold', fontSize:8 },
    alternateRowStyles: { fillColor:[18,36,60] },
    columnStyles: {
      0: { cellWidth:68 },
      1: { cellWidth:32 },
      2: { cellWidth:24, halign:'center' },
      3: { cellWidth:36, halign:'right', textColor:goldLt, fontStyle:'bold' }
    },
    tableWidth: W - 28,
    margin: { left:14 },
  });
  y = doc.lastAutoTable.finalY + 10;

  checkPage(40);
  sectionTitle('Top 10 Menu Items by Orders');

  const menuRows = [
    <?php
    if ($topMenus && $topMenus->num_rows > 0) {
      $topMenus->data_seek(0); $rank = 1;
      while ($m = $topMenus->fetch_assoc()) {
        echo "[" . $rank++ . ", '" . addslashes($m['name']) . "', '" . addslashes($m['rest_name']) . "', " . (int)$m['order_count'] . ", 'RM " . number_format($m['total_revenue'],2) . "'],\n";
      }
    }
    ?>
  ];

  doc.autoTable({
    startY: y,
    head: [['#', 'Item', 'Restaurant', 'Orders', 'Revenue']],
    body: menuRows,
    theme: 'plain',
    styles: { font:'helvetica', fontSize:9, textColor:white, cellPadding:4 },
    headStyles: { fillColor:[22,44,74], textColor:goldLt, fontStyle:'bold', fontSize:8 },
    alternateRowStyles: { fillColor:[18,36,60] },
    columnStyles: {
      0: { cellWidth:10, halign:'center', textColor:goldLt, fontStyle:'bold' },
      1: { cellWidth:58 },
      2: { cellWidth:50 },
      3: { cellWidth:20, halign:'center' },
      4: { cellWidth:34, halign:'right', textColor:[34,197,94], fontStyle:'bold' },
    },
    tableWidth: W - 28,
    margin: { left:14 },
  });
  y = doc.lastAutoTable.finalY + 10;


  checkPage(60);
  sectionTitle('Dietary Flags & Allergen Matrix');

  const dietRows = [
    <?php
    $dMap = ['Halal'=>'halal','Non-Halal'=>'non_halal','Vegetarian'=>'vegetarian','Vegan'=>'vegan','Hi-Protein'=>'high_protein','Spicy'=>'spicy','Mild'=>'non_spicy'];
    foreach ($dMap as $label => $key) echo "['" . $label . "', " . (int)($dietStats[$key] ?? 0) . "],\n";
    ?>
  ];
  const allergenRows = [
    <?php
    $aMap = ['Peanut'=>'peanut','Seafood'=>'seafood','Soy'=>'soy','Milk'=>'milk','Gluten'=>'gluten'];
    foreach ($aMap as $label => $key) echo "['" . $label . "', " . (int)($allergyStats[$key] ?? 0) . "],\n";
    ?>
  ];

  const halfW = (W - 32) / 2;
  const savedY = y;

  doc.autoTable({
    startY: savedY,
    head: [['Dietary Tag', 'Items']],
    body: dietRows,
    theme: 'plain',
    styles: { font:'helvetica', fontSize:9, textColor:white, cellPadding:3.5 },
    headStyles: { fillColor:[22,44,74], textColor:goldLt, fontStyle:'bold', fontSize:8 },
    alternateRowStyles: { fillColor:[18,36,60] },
    columnStyles: { 1: { halign:'center', fontStyle:'bold', textColor:goldLt } },
    tableWidth: halfW,
    margin: { left:14 },
  });
  const leftY = doc.lastAutoTable.finalY;

  doc.autoTable({
    startY: savedY,
    head: [['Allergen', 'Items']],
    body: allergenRows,
    theme: 'plain',
    styles: { font:'helvetica', fontSize:9, textColor:white, cellPadding:3.5 },
    headStyles: { fillColor:[22,44,74], textColor:danger, fontStyle:'bold', fontSize:8 },
    alternateRowStyles: { fillColor:[18,36,60] },
    columnStyles: { 1: { halign:'center', fontStyle:'bold', textColor:danger } },
    tableWidth: halfW,
    margin: { left: 18 + halfW },
  });
  y = Math.max(leftY, doc.lastAutoTable.finalY) + 10;

  <?php if ($totalCancelled > 0 && !empty($reasonRows)): ?>
  checkPage(40);
  sectionTitle('Cancellation Reasons (<?= number_format($totalCancelled) ?> Cancelled)');

  const cancelRows = [
    <?php foreach ($reasonRows as $rr):
      $pct = $totalCancelled > 0 ? round($rr['cnt'] / $totalCancelled * 100) : 0;
      echo "['" . addslashes($rr['cancellation_reason']) . "', " . (int)$rr['cnt'] . ", '" . $pct . "%'],\n";
    endforeach; ?>
  ];

  doc.autoTable({
    startY: y,
    head: [['Reason', 'Count', '% of Cancelled']],
    body: cancelRows,
    theme: 'plain',
    styles: { font:'helvetica', fontSize:9, textColor:white, cellPadding:4 },
    headStyles: { fillColor:[22,44,74], textColor:danger, fontStyle:'bold', fontSize:8 },
    alternateRowStyles: { fillColor:[18,36,60] },
    columnStyles: {
      0: { cellWidth:110 },
      1: { cellWidth:24, halign:'center', fontStyle:'bold', textColor:danger },
      2: { cellWidth:36, halign:'right',  textColor:muted },
    },
    tableWidth: W - 28,
    margin: { left:14 },
  });
  y = doc.lastAutoTable.finalY + 10;
  <?php endif; ?>

 
  const totalPages = doc.internal.getNumberOfPages();
  for (let p = 1; p <= totalPages; p++) {
    doc.setPage(p);
    const ph = doc.internal.pageSize.getHeight();
    doc.setFillColor(...navy);
    doc.rect(0, ph - 12, W, 12, 'F');
    doc.setFillColor(...gold);
    doc.rect(0, ph - 13, W, 1, 'F');
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(...muted);
    doc.text('Orderly Admin Report  ·  Confidential', 14, ph - 4.5);
    doc.text('Page ' + p + ' of ' + totalPages, W - 14, ph - 4.5, { align:'right' });
  }

  doc.save('orderly_report_<?= date('Ymd_Hi') ?>.pdf');

  btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg> Download PDF Report';
  btn.disabled = false;
}
</script>

<?php require_once 'includes/footer.php'; ?>