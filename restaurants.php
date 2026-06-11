<?php
require_once 'config.php';
requireAdmin();

$filterArea = trim($_GET['area'] ?? '');
$filterQ    = trim($_GET['q'] ?? '');

$where = "WHERE 1=1";
if ($filterArea) $where .= " AND r.area='" . $conn->real_escape_string($filterArea) . "'";
if ($filterQ) {
    $q = $conn->real_escape_string($filterQ);
    $where .= " AND (r.name LIKE '%$q%' OR r.address LIKE '%$q%')";
}

$restaurants = $conn->query("
    SELECT r.*,
           COUNT(DISTINCT m.id)  AS menu_count,
           COUNT(DISTINCT vs.id) AS staff_count,
           COUNT(DISTINCT o.id)  AS order_count,
           COALESCE(SUM(CASE WHEN o.status='completed' THEN o.total_price ELSE 0 END), 0) AS revenue
    FROM restaurants r
    LEFT JOIN menu_items m  ON m.restaurant_id = r.id
    LEFT JOIN users vs      ON vs.restaurant_id = r.id AND vs.role = 'vendor_staff'
    LEFT JOIN orders o      ON o.restaurant_id = r.id
    $where
    GROUP BY r.id
    ORDER BY r.area, r.name
");

$totalRests   = $conn->query("SELECT COUNT(*) FROM restaurants")->fetch_row()[0];
$totalRevenue = $conn->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status='completed'")->fetch_row()[0];
$areas        = $conn->query("SELECT DISTINCT area FROM restaurants ORDER BY area");
$areaList     = [];
while ($a = $areas->fetch_row()) $areaList[] = $a[0];

$pageTitle  = 'Restaurants';
$activePage = 'restaurants';
require_once 'admin_header.php';
?>

<?php if (isset($_SESSION['admin_toast'])): ?>
<div class="toast-container-custom"><div class="toast-msg success"><?= h($_SESSION['admin_toast']) ?></div></div>
<?php unset($_SESSION['admin_toast']); endif; ?>
<?php if (isset($_SESSION['admin_error'])): ?>
<div class="toast-container-custom"><div class="toast-msg" style="border-color:var(--red);color:var(--red);"><?= h($_SESSION['admin_error']) ?></div></div>
<?php unset($_SESSION['admin_error']); endif; ?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <p class="page-title">Restaurants</p>
    <p class="page-sub">Manage all restaurants, their menus and staff assignments.</p>
  </div>
  <button class="btn-primary" onclick="openOrdModal('addRestModal')">+ Add Restaurant</button>
</div>

<!-- Stats -->
<div style="display:flex;gap:14px;margin-bottom:22px;flex-wrap:wrap;">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px 20px;min-width:150px;">
    <div style="font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;color:var(--accent2);"><?= $totalRests ?></div>
    <div style="font-size:12px;color:var(--muted);">Total Restaurants</div>
  </div>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px 20px;min-width:150px;">
    <div style="font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;color:var(--green);">RM <?= number_format($totalRevenue, 2) ?></div>
    <div style="font-size:12px;color:var(--muted);">Total Revenue</div>
  </div>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px 20px;min-width:150px;">
    <div style="font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;color:var(--blue);"><?= count($areaList) ?></div>
    <div style="font-size:12px;color:var(--muted);">Areas Covered</div>
  </div>
</div>

<!-- Filter -->
<form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;align-items:center;">
  <input type="text" name="q" placeholder="Search name or address…" value="<?= h($filterQ) ?>"
    style="padding:8px 12px;background:var(--surface);border:1.5px solid var(--border2);border-radius:8px;color:var(--text);font-size:13px;outline:none;min-width:220px;">
  <select name="area" style="padding:8px 12px;background:var(--surface);border:1.5px solid var(--border2);border-radius:8px;color:var(--text);font-size:13px;outline:none;">
    <option value="" style="background:var(--navy); color:var(--muted);">All Areas</option>
    <?php foreach ($areaList as $area): ?>
    <option value="<?= h($area) ?>" style="background:var(--navy); color:var(--muted);"  <?= $filterArea===$area?'selected':'' ?>><?= h($area) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn-primary">Filter</button>
  <a href="restaurants.php" class="btn-ghost">Reset</a>
</form>

<!-- Table -->
<div class="dash-card">
  <div style="overflow-x:auto;">
    <table class="table" style="margin:0;">
      <thead>
        <tr>
          <th>#</th>
          <th>Restaurant</th>
          <th>Area</th>
          <th>Address</th>
          <th>Menus</th>
          <th>Staff</th>
          <th>Orders</th>
          <th>Revenue</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($restaurants && $restaurants->num_rows > 0):
        while ($r = $restaurants->fetch_assoc()):
      ?>
      <tr>
        <td style="color:var(--muted);font-size:12px;">#<?= $r['id'] ?></td>
        <td><strong><?= h($r['name']) ?></strong></td>
        <td><span class="badge-pill bp-blue"><?= h($r['area']) ?></span></td>
        <td style="font-size:12px;color:var(--muted);max-width:180px;"><?= h($r['address'] ?: '—') ?></td>
        <td>
          <a href="manage_menu.php?restaurant=<?= $r['id'] ?>" class="btn-edit-sm"><?= $r['menu_count'] ?> items</a>
        </td>
        <td>
          <?php if ($r['staff_count'] > 0): ?>
          <span style="color:var(--green);font-weight:600;"><?= $r['staff_count'] ?></span>
          <?php else: ?>
          <span style="color:var(--amber);font-size:11px;">None</span>
          <?php endif; ?>
        </td>
        <td><?= $r['order_count'] ?></td>
        <td style="color:var(--green);font-weight:600;">RM <?= number_format($r['revenue'], 2) ?></td>
        <td>
          <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <button class="btn-edit-sm"
              onclick="openEdit(<?= $r['id'] ?>,'<?= h(addslashes($r['name'])) ?>','<?= h(addslashes($r['area'])) ?>','<?= h(addslashes($r['address'] ?? '')) ?>')">
              Edit
            </button>
            <button class="btn-danger-sm"
              onclick="confirmDelete(<?= $r['id'] ?>,'<?= h(addslashes($r['name'])) ?>')">
              Delete
            </button>
          </div>
        </td>
      </tr>
      <?php endwhile; else: ?>
      <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--muted);">No restaurants found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Restaurant Modal -->
<div class="ord-overlay" id="addRestModal">
  <div class="ord-modal" style="max-width:420px;">
    <p class="ord-modal-title">Add Restaurant</p>
    <form method="POST" action="admin_handler.php">
      <input type="hidden" name="action" value="add_restaurant">
      <input type="hidden" name="redirect" value="restaurants.php">
      <div class="form-group">
        <label class="form-label">Restaurant Name *</label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Area *</label>
        <input type="text" name="area" class="form-control" placeholder="e.g. Setapak, Wangsa Maju" required>
      </div>
      <div class="form-group">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control" placeholder="Optional full address">
      </div>
      <div class="ord-modal-actions">
        <button type="button" class="btn-ghost" onclick="closeOrdModal('addRestModal')">Cancel</button>
        <button type="submit" class="btn-primary">Add Restaurant</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Restaurant Modal -->
<div class="ord-overlay" id="editRestModal">
  <div class="ord-modal" style="max-width:420px;">
    <p class="ord-modal-title">Edit Restaurant</p>
    <form method="POST" action="admin_handler.php">
      <input type="hidden" name="action" value="edit_restaurant">
      <input type="hidden" name="redirect" value="restaurants.php">
      <input type="hidden" name="restaurant_id" id="editRestId">
      <div class="form-group">
        <label class="form-label">Restaurant Name *</label>
        <input type="text" name="name" id="editRestName" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Area *</label>
        <input type="text" name="area" id="editRestArea" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Address</label>
        <input type="text" name="address" id="editRestAddress" class="form-control">
      </div>
      <div class="ord-modal-actions">
        <button type="button" class="btn-ghost" onclick="closeOrdModal('editRestModal')">Cancel</button>
        <button type="submit" class="btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Modal -->
<div class="ord-overlay" id="deleteModal">
  <div class="ord-modal" style="max-width:380px;">
    <p class="ord-modal-title">Delete Restaurant?</p>
    <p id="deleteBody" style="font-size:13px;color:var(--muted);margin-bottom:20px;"></p>
    <form method="POST" action="admin_handler.php">
      <input type="hidden" name="action" value="delete_restaurant">
      <input type="hidden" name="redirect" value="restaurants.php">
      <input type="hidden" name="restaurant_id" id="deleteRestId">
      <div class="ord-modal-actions">
        <button type="button" class="btn-ghost" onclick="closeOrdModal('deleteModal')">Cancel</button>
        <button type="submit" class="btn-primary" style="background:var(--red);">Yes, Delete</button>
      </div>
    </form>
  </div>
</div>

<script>
function openOrdModal(id)  { document.getElementById(id).classList.add('open'); }
function closeOrdModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.ord-overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});
function openEdit(id, name, area, address) {
  document.getElementById('editRestId').value      = id;
  document.getElementById('editRestName').value    = name;
  document.getElementById('editRestArea').value    = area;
  document.getElementById('editRestAddress').value = address;
  openOrdModal('editRestModal');
}
function confirmDelete(id, name) {
  document.getElementById('deleteRestId').value = id;
  document.getElementById('deleteBody').textContent = 'Delete "' + name + '"? This will also affect linked menus and staff assignments.';
  openOrdModal('deleteModal');
}
document.querySelectorAll('.toast-msg').forEach(t => setTimeout(() => t.style.display='none', 4000));
</script>

<?php require_once 'includes/footer.php'; ?>