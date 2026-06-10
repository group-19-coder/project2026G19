<?php

require_once 'config.php';
requireAdmin();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'assign') {
        $userId = intval($_POST['user_id']);
        $restId = intval($_POST['restaurant_id']);
        $restVal = $restId > 0 ? "'$restId'" : "NULL";
        $conn->query("UPDATE users SET restaurant_id=$restVal WHERE id='$userId' AND role='vendor_staff'");
        $_SESSION['toast'] = "✅ Assignment updated.";
    }

    if ($action === 'unassign') {
        $userId = intval($_POST['user_id']);
        $conn->query("UPDATE users SET restaurant_id=NULL WHERE id='$userId' AND role='vendor_staff'");
        $_SESSION['toast'] = "✅ Staff unassigned.";
    }

    if ($action === 'add_staff') {
        $name    = $conn->real_escape_string(trim($_POST['name']));
        $email   = $conn->real_escape_string(trim($_POST['email']));
        $password = $_POST['password'] ?? '';
        $restId  = intval($_POST['restaurant_id'] ?? 0);
        $restVal = $restId > 0 ? "'$restId'" : "NULL";

        if ($name && $email && $password) {
            $chk = $conn->query("SELECT id FROM users WHERE email='$email'");
            if ($chk->num_rows > 0) {
                $_SESSION['toast_error'] = "Email '$email' already exists.";
            } else {
                $hash = $conn->real_escape_string(password_hash($password, PASSWORD_DEFAULT));
                $conn->query("INSERT INTO users (name,email,password,role,restaurant_id) VALUES ('$name','$email','$hash','vendor_staff',$restVal)");
                $_SESSION['toast'] = "✅ Vendor staff '$name' created!";
            }
        }
    }

    header("Location: vendor_assignment.php"); exit();
}

// Vendor staff list
$vendors = $conn->query("
    SELECT u.id, u.name, u.email, u.created_at, u.restaurant_id,
           r.name AS rest_name, r.area AS rest_area
    FROM users u LEFT JOIN restaurants r ON u.restaurant_id=r.id
    WHERE u.role='vendor_staff' ORDER BY r.name ASC, u.name ASC
");

$restaurants = $conn->query("SELECT id, name, area FROM restaurants ORDER BY area, name");

$unassignedCount = $conn->query("SELECT COUNT(*) FROM users WHERE role='vendor_staff' AND (restaurant_id IS NULL OR restaurant_id=0)")->fetch_row()[0];
$totalStaff      = $conn->query("SELECT COUNT(*) FROM users WHERE role='vendor_staff'")->fetch_row()[0];

$pageTitle  = 'Vendor Assignment';
$activePage = 'vendor_assignment';
require_once 'admin_header.php';
?>

<?php if (isset($_SESSION['toast'])): ?>
<div class="toast-container-custom"><div class="toast-msg success"><?= h($_SESSION['toast']) ?></div></div>
<?php unset($_SESSION['toast']); endif; ?>
<?php if (isset($_SESSION['toast_error'])): ?>
<div class="toast-container-custom"><div class="toast-msg" style="border-color:var(--red);color:var(--red);"><?= h($_SESSION['toast_error']) ?></div></div>
<?php unset($_SESSION['toast_error']); endif; ?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div>
    <p class="page-title">Vendor Staff Assignment</p>
    <p class="page-sub">Assign vendor staff to restaurants.</p>
  </div>
  <button class="btn-orderly" onclick="openOrdModal('addStaffModal')">+ Add Vendor Staff</button>
</div>


<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card"><div class="stat-val"><?= $totalStaff ?></div><div class="stat-lbl">Total Staff</div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-val" style="color:<?= $unassignedCount>0?'var(--amber)':'var(--green)' ?>;"><?= $unassignedCount ?></div>
      <div class="stat-lbl">Unassigned</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card"><div class="stat-val"><?= $totalStaff-$unassignedCount ?></div><div class="stat-lbl">Assigned</div></div>
  </div>
</div>

<?php if ($unassignedCount > 0): ?>
<div class="dash-card mb-4" style="border-color:rgba(245,158,11,.3);background:rgba(245,158,11,.05);">
  <div class="dash-card-body" style="padding:14px 18px;">
    <span style="color:var(--amber);font-size:13px;font-weight:600;">⚠️ <?= $unassignedCount ?> vendor staff have no restaurant assignment. They cannot access vendor features until assigned.</span>
  </div>
</div>
<?php endif; ?>


<div class="dash-card">
  <div class="dash-card-header"><span class="dash-card-title">All Vendor Staff</span></div>
  <div class="dash-card-body" style="padding:0;">
    <div style="overflow-x:auto;">
      <table class="table w-100">
        <thead>
          <tr><th>#</th><th>Name</th><th>Email</th><th>Assigned Restaurant</th><th>Since</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php if ($vendors->num_rows > 0):
          while ($vs = $vendors->fetch_assoc()): ?>
        <tr>
          <td style="color:var(--muted);font-size:12px;">#<?= $vs['id'] ?></td>
          <td><strong><?= h($vs['name']) ?></strong></td>
          <td style="color:var(--muted);font-size:12px;"><?= h($vs['email']) ?></td>
          <td>
            <?php if ($vs['rest_name']): ?>
            <span class="badge-pill" style="background:var(--accent-glow);color:var(--accent2);border:1px solid rgba(124,106,247,.25);"><?= h($vs['rest_name']) ?></span>
            <span style="font-size:11px;color:var(--muted);margin-left:4px;">· <?= h($vs['rest_area']) ?></span>
            <?php else: ?>
            <span class="badge-pill" style="background:var(--amber-bg);color:var(--amber);border:1px solid rgba(245,158,11,.25);">⚠️ Not assigned</span>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;color:var(--muted);"><?= date('d M Y', strtotime($vs['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <button class="btn-edit-sm" style="background:var(--accent-glow);color:var(--accent2);border-color:rgba(124,106,247,.25);"
                onclick="openAssign(<?= $vs['id'] ?>,'<?= h(addslashes($vs['name'])) ?>','<?= $vs['restaurant_id']??'' ?>')">
                Assign
              </button>
              <?php if ($vs['restaurant_id']): ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="unassign">
                <input type="hidden" name="user_id" value="<?= $vs['id'] ?>">
                <button type="submit" class="btn-danger-sm" onclick="return confirm('Remove assignment?')">Unassign</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">No vendor staff found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>


<div class="ord-overlay" id="assignModal">
  <div class="ord-modal" style="max-width:380px;">
    <p class="ord-modal-title">Assign Restaurant</p>
    <p id="assignStaffName" style="font-size:13px;color:var(--muted);margin-bottom:16px;"></p>
    <form method="POST">
      <input type="hidden" name="action" value="assign">
      <input type="hidden" name="user_id" id="assignUserId">
      <div class="form-group">
        <label class="form-label">Restaurant</label>
        <select name="restaurant_id" id="assignRestId" class="form-control">
          <option value="">— Remove assignment —</option>
          <?php $restaurants->data_seek(0); while ($r = $restaurants->fetch_assoc()): ?>
          <option value="<?= $r['id'] ?>"><?= h($r['name']) ?> (<?= h($r['area']) ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="ord-modal-actions">
        <button type="button" class="btn-ghost" onclick="closeOrdModal('assignModal')">Cancel</button>
        <button type="submit" class="btn-primary">Save Assignment</button>
      </div>
    </form>
  </div>
</div>


<div class="ord-overlay" id="addStaffModal">
  <div class="ord-modal">
    <p class="ord-modal-title">Add Vendor Staff</p>
    <form method="POST">
      <input type="hidden" name="action" value="add_staff">
      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="name" class="form-control" placeholder="e.g. Ahmad bin Ali" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Password *</label>
        <input type="password" name="password" class="form-control" minlength="6" required>
        <div class="form-hint">Minimum 6 characters.</div>
      </div>
      <div class="form-group">
        <label class="form-label">Assign Restaurant</label>
        <select name="restaurant_id" class="form-control">
          <option value="">— Assign later —</option>
          <?php $restaurants->data_seek(0); while ($r = $restaurants->fetch_assoc()): ?>
          <option value="<?= $r['id'] ?>"><?= h($r['name']) ?> (<?= h($r['area']) ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="ord-modal-actions">
        <button type="button" class="btn-ghost" onclick="closeOrdModal('addStaffModal')">Cancel</button>
        <button type="submit" class="btn-primary">Create Staff</button>
      </div>
    </form>
  </div>
</div>

<script>
function openOrdModal(id)  { document.getElementById(id).classList.add('open'); }
function closeOrdModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.ord-overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target===o) o.classList.remove('open'); });
});
function openAssign(userId, name, restId) {
  document.getElementById('assignUserId').value = userId;
  document.getElementById('assignRestId').value = restId || '';
  document.getElementById('assignStaffName').textContent = 'Staff: ' + name;
  openOrdModal('assignModal');
}
document.querySelectorAll('.toast-msg').forEach(t => setTimeout(() => t.style.display='none', 4000));
</script>

<?php require_once 'includes/footer.php'; ?>
