<?php
require_once 'config.php';
requireAdmin();

$filterRole = $_GET['role']         ?? '';
$filterRest = intval($_GET['filter_rest'] ?? 0);
$filterQ    = trim($_GET['q']     ?? '');

$where = "WHERE u.role != 'admin'";
if ($filterRole) $where .= " AND u.role='".$conn->real_escape_string($filterRole)."'";
if ($filterRest) $where .= " AND u.restaurant_id='$filterRest'";
if ($filterQ) {
    $q = $conn->real_escape_string($filterQ);
    $where .= " AND (u.name LIKE '%$q%' OR u.email LIKE '%$q%')";
}

$users = $conn->query("
    SELECT u.*, r.name AS rest_name, r.area AS rest_area
    FROM users u LEFT JOIN restaurants r ON u.restaurant_id=r.id
    $where ORDER BY u.role ASC, u.created_at DESC
");

$restResult    = $conn->query("SELECT id, name, area FROM restaurants ORDER BY area, name");
$restaurants   = [];
while ($rr = $restResult->fetch_assoc()) $restaurants[] = $rr;
$vendorCount   = $conn->query("SELECT COUNT(*) FROM users WHERE role='vendor_staff'")->fetch_row()[0];
$customerCount = $conn->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetch_row()[0];
$unassigned    = $conn->query("SELECT COUNT(*) FROM users WHERE role='vendor_staff' AND (restaurant_id IS NULL OR restaurant_id=0)")->fetch_row()[0];

$pageTitle  = 'Users & Staff';
$activePage = 'users';
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
    <p class="page-title">Users &amp; Staff</p>
    <p class="page-sub">Manage all vendor staff and customer accounts.</p>
  </div>
  <div style="display:flex;gap:8px;">
    <button class="btn-primary" onclick="openOrdModal('addVendorModal')">+ Add Vendor Staff</button>
    <a href="vendor_assignment.php" class="btn-ghost">Assign Staff →</a>
  </div>
</div>

<div style="display:flex;gap:14px;margin-bottom:22px;flex-wrap:wrap;">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px 20px;display:flex;align-items:center;gap:12px;min-width:150px;">
    <div><div style="font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;color:var(--accent2);"><?= $vendorCount ?></div><div style="font-size:12px;color:var(--muted);">Vendor Staff</div></div>
  </div>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px 20px;display:flex;align-items:center;gap:12px;min-width:150px;">
    <div><div style="font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;color:var(--green);"><?= $customerCount ?></div><div style="font-size:12px;color:var(--muted);">Customers</div></div>
  </div>
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px 20px;display:flex;align-items:center;gap:12px;min-width:150px;">
    <div><div style="font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;color:<?= $unassigned>0?'var(--amber)':'var(--green)' ?>;"><?= $unassigned ?></div><div style="font-size:12px;color:var(--muted);">Unassigned</div></div>
  </div>
</div>

<form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;align-items:center;">
  <input type="text" name="q" placeholder="Search name or email…" value="<?= h($filterQ) ?>"
    style="padding:8px 12px;background:var(--surface);border:1.5px solid var(--border2);border-radius:8px;color:var(--text);font-size:13px;outline:none;min-width:220px;">
  <select name="role" style="padding:8px 12px;background:var(--surface);border:1.5px solid var(--border2);border-radius:8px;color:var(--text);font-size:13px;outline:none;">
    <option value="" style="background:var(--navy); color:var(--muted);">All Roles</option>
    <option value="vendor_staff" style="background:var(--navy); color:var(--muted); <?= $filterRole==='vendor_staff'?'selected':'' ?>">Vendor Staff</option>
    <option value="customer" style="background:var(--navy); color:var(--muted); <?= $filterRole==='customer'?'selected':'' ?>">Customer</option>
  </select>
  <select name="filter_rest" style="padding:8px 12px;background:var(--surface);border:1.5px solid var(--border2);border-radius:8px;color:var(--text);font-size:13px;outline:none;">
    <option value="" style="background:var(--navy); color:var(--muted);">All Restaurants</option>
    <?php foreach ($restaurants as $r): ?>
    <option value="<?= $r['id'] ?>" style="background:var(--navy); color:var(--muted);"<?= $filterRest==$r['id']?'selected':'' ?>><?= h($r['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn-primary">Filter</button>
  <a href="admin_users.php" class="btn-ghost">Reset</a>
</form>

<div class="dash-card">
  <div style="overflow-x:auto;">
    <table class="table" style="margin:0;">
      <thead>
        <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Restaurant</th><th>Joined</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php if ($users && $users->num_rows > 0):
        while ($u = $users->fetch_assoc()):
          $isVendor = $u['role'] === 'vendor_staff';
      ?>
      <tr>
        <td style="color:var(--muted);font-size:12px;">#<?= $u['id'] ?></td>
        <td><strong><?= h($u['name']) ?></strong></td>
        <td style="color:var(--muted);font-size:12px;"><?= h($u['email']) ?></td>
        <td>
          <?php if ($isVendor): ?>
          <span style="background:var(--accent-glow);color:var(--accent2);padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;border:1px solid rgba(124,106,247,.25);">Vendor</span>
          <?php else: ?>
          <span style="background:var(--green-bg);color:var(--green);padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;border:1px solid rgba(34,197,94,.2);">Customer</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($u['rest_name']): ?>
          <span style="font-size:12px;color:var(--accent2);"><?= h($u['rest_name']) ?></span>
          <?php elseif ($isVendor): ?>
          <span style="font-size:11px;color:var(--amber);font-weight:600;">Not assigned</span>
          <?php else: ?>
          <span style="color:var(--muted);font-size:12px;">—</span>
          <?php endif; ?>
        </td>
        <td style="font-size:12px;color:var(--muted);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        <td>
          <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <?php if ($isVendor): ?>
            <button class="btn-edit-sm" onclick="openEdit(<?= $u['id'] ?>,'<?= h(addslashes($u['name'])) ?>','<?= h(addslashes($u['email'])) ?>','<?= $u['restaurant_id']??'' ?>')">Edit</button>
            <button class="btn-edit-sm" style="background:var(--accent-glow);color:var(--accent2);border-color:rgba(124,106,247,.25);"
              onclick="openAssign(<?= $u['id'] ?>,'<?= h(addslashes($u['name'])) ?>','<?= $u['restaurant_id']??'' ?>')">Assign</button>
            <?php endif; ?>
            <button class="btn-danger-sm" onclick="confirmDelete(<?= $u['id'] ?>,'<?= h(addslashes($u['name'])) ?>')">Delete</button>
          </div>
        </td>
      </tr>
      <?php endwhile; else: ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted);">No users found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="ord-overlay" id="addVendorModal">
  <div class="ord-modal">
    <p class="ord-modal-title"> + Add Vendor Staff</p>
    <form method="POST" action="admin_handler.php">
      <input type="hidden" name="action" value="add_vendor_staff">
      <input type="hidden" name="redirect" value="admin_users.php">
      <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" minlength="6" required><div class="form-hint">Minimum 6 characters.</div></div>
      <div class="form-group">
        <label class="form-label">Assign Restaurant</label>
        <select name="restaurant_id" class="form-control">
          <option value="">— Assign later —</option>
          <?php foreach ($restaurants as $r): ?>
          <option value="<?= $r['id'] ?>"><?= h($r['name']) ?> (<?= h($r['area']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="ord-modal-actions">
        <button type="button" class="btn-ghost" onclick="closeOrdModal('addVendorModal')">Cancel</button>
        <button type="submit" class="btn-primary">Create Staff</button>
      </div>
    </form>
  </div>
</div>

<div class="ord-overlay" id="editVendorModal">
  <div class="ord-modal">
    <p class="ord-modal-title">Edit Vendor Staff</p>
    <form method="POST" action="admin_handler.php">
      <input type="hidden" name="action" value="edit_vendor_staff">
      <input type="hidden" name="redirect" value="admin_users.php">
      <input type="hidden" name="user_id" id="editUserId">
      <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" id="editName" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" id="editEmail" class="form-control" required></div>
      <div class="form-group"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" minlength="6"><div class="form-hint">Leave blank to keep current password.</div></div>
      <div class="form-group">
        <label class="form-label">Assigned Restaurant</label>
        <select name="restaurant_id" id="editRestId" class="form-control">
          <option value="">— Remove assignment —</option>
          <?php foreach ($restaurants as $r): ?>
          <option value="<?= $r['id'] ?>"><?= h($r['name']) ?> (<?= h($r['area']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="ord-modal-actions">
        <button type="button" class="btn-ghost" onclick="closeOrdModal('editVendorModal')">Cancel</button>
        <button type="submit" class="btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<div class="ord-overlay" id="assignModal">
  <div class="ord-modal" style="max-width:380px;">
    <p class="ord-modal-title">Assign Restaurant</p>
    <p id="assignStaffName" style="font-size:13px;color:var(--muted);margin-bottom:16px;"></p>
    <form method="POST" action="admin_handler.php">
      <input type="hidden" name="action" value="assign_restaurant">
      <input type="hidden" name="redirect" value="admin_users.php">
      <input type="hidden" name="user_id" id="assignUserId">
      <div class="form-group">
        <label class="form-label">Restaurant</label>
        <select name="restaurant_id" id="assignRestId" class="form-control">
          <option value="">— Remove assignment —</option>
          <?php foreach ($restaurants as $r): ?>
          <option value="<?= $r['id'] ?>"><?= h($r['name']) ?> (<?= h($r['area']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="ord-modal-actions">
        <button type="button" class="btn-ghost" onclick="closeOrdModal('assignModal')">Cancel</button>
        <button type="submit" class="btn-primary">Assign</button>
      </div>
    </form>
  </div>
</div>

<div class="ord-overlay" id="deleteModal">
  <div class="ord-modal" style="max-width:380px;">
    <p class="ord-modal-title">Delete User?</p>
    <p id="deleteBody" style="font-size:13px;color:var(--muted);margin-bottom:20px;"></p>
    <form method="POST" action="admin_handler.php">
      <input type="hidden" name="action" value="delete_user">
      <input type="hidden" name="redirect" value="admin_users.php">
      <input type="hidden" name="user_id" id="deleteUserId">
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
  o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); });
});
function openEdit(id, name, email, restId) {
  document.getElementById('editUserId').value = id;
  document.getElementById('editName').value   = name;
  document.getElementById('editEmail').value  = email;
  document.getElementById('editRestId').value = restId || '';
  openOrdModal('editVendorModal');
}
function openAssign(userId, name, restId) {
  document.getElementById('assignUserId').value = userId;
  document.getElementById('assignRestId').value  = restId || '';
  document.getElementById('assignStaffName').textContent = 'Staff: ' + name;
  openOrdModal('assignModal');
}
function confirmDelete(userId, name) {
  document.getElementById('deleteUserId').value = userId;
  document.getElementById('deleteBody').textContent = 'Delete "' + name + '"? This cannot be undone.';
  openOrdModal('deleteModal');
}
document.querySelectorAll('.toast-msg').forEach(t => setTimeout(() => t.style.display='none', 4000));
</script>

<?php require_once 'includes/footer.php'; ?>