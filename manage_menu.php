<?php
require_once 'config.php';
requireVendor();

$vendorRestId = getVendorRestaurantId(); 


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $did = intval($_POST['delete_id']);
   
    $row = $conn->query("SELECT image, restaurant_id FROM menu_items WHERE id='$did'")->fetch_assoc();
    if ($row) {
        if ($vendorRestId > 0) enforceRestaurantScope((int)$row['restaurant_id']);
        $img = $row['image'] ?? '';
        if ($img && file_exists('uploads/food_images/'.$img)) unlink('uploads/food_images/'.$img);
        $conn->query("DELETE FROM menu_items WHERE id='$did'");
        $_SESSION['toast'] = 'Menu item deleted.';
    }
    header("Location: manage_menu.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $tid = intval($_POST['toggle_id']);
    $row = $conn->query("SELECT restaurant_id, availability FROM menu_items WHERE id='$tid'")->fetch_assoc();
    if ($row) {
        if ($vendorRestId > 0) enforceRestaurantScope((int)$row['restaurant_id']);
        $avail = intval($row['availability']) ? 0 : 1;
        $conn->query("UPDATE menu_items SET availability='$avail' WHERE id='$tid'");
    }
    header("Location: manage_menu.php" . ($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : ''));
    exit();
}


$fRest   = $vendorRestId > 0 ? $vendorRestId : intval($_GET['restaurant'] ?? 0);
$fBudget = $conn->real_escape_string($_GET['budget']   ?? '');
$fLoc    = $conn->real_escape_string($_GET['location'] ?? '');
$fCat    = $conn->real_escape_string($_GET['category'] ?? '');
$fAvail  = $_GET['availability'] ?? '';

$where = "WHERE 1=1";
if ($fRest)   $where .= " AND m.restaurant_id='$fRest'";
if ($fBudget) $where .= " AND m.budget_category='$fBudget'";
if ($fLoc)    $where .= " AND m.location='$fLoc'";
if ($fCat)    $where .= " AND m.category='$fCat'";
if ($fAvail !== '') $where .= " AND m.availability='".intval($fAvail)."'";

$items = $conn->query("SELECT m.*, r.name AS rest_name FROM menu_items m
                        JOIN restaurants r ON m.restaurant_id = r.id
                        $where ORDER BY m.id DESC");

$restaurants = isAdmin() ? $conn->query("SELECT id, name FROM restaurants ORDER BY name") : null;

$pageTitle  = 'Manage Menu';
$activePage = 'manage_menu';
// Admin uses admin header, vendor uses vendor header
if (isAdmin()) {
    require_once 'admin_header.php';
} else {
    require_once 'vendor_header.php';
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap');

:root {
  --navy: #0b1f3a;
  --navy-md: #132d52;
  --navy-lt: #1e4175;

  --gold: #c8963e;
  --gold-lt: #e0b96a;

  --cream: #f7f4ef;
  --cream-dk: #ede8e0;

  --text: #1a2535;
  --muted: #5a6a80;

  --success: #16a34a;
  --danger: #dc2626;
  --warning: #f59e0b;
  --info: #2563eb;
  
  
  --glass-bg: rgba(255, 255, 255, 0.03);
  --glass-border: rgba(255, 255, 255, 0.07);
  --glass-input-bg: rgba(11, 31, 58, 0.4);
}

body {
  background: radial-gradient(circle at 0% 0%, #112746 0%, #061324 70%) !important;
  font-family: 'Poppins', sans-serif !important;
  color: var(--cream);
}

.animate-fadeUp {
  animation: saasElementFadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes saasElementFadeUp {
  0% { opacity: 0; transform: translateY(20px); filter: blur(5px); }
  100% { opacity: 1; transform: translateY(0); filter: blur(0); }
}


.saas-glass-card {
  background: var(--glass-bg);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid var(--glass-border);
  border-radius: 18px;
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
  transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.3s ease, border-color 0.3s ease;
  overflow: hidden;
}

.saas-input {
  background: var(--glass-input-bg) !important;
  border: 1.5px solid var(--glass-border) !important;
  color: var(--cream) !important;
  font-family: 'Poppins', sans-serif;
  font-size: 13px;
  padding: 10px 14px;
  border-radius: 10px;
  transition: all 0.25s ease !important;
}

.saas-input:focus {
  background: rgba(11, 31, 58, 0.7) !important;
  border-color: var(--gold) !important;
  box-shadow: 0 0 0 4px rgba(200, 150, 62, 0.15) !important;
  outline: none;
}


.btn-saas-primary {
  background: linear-gradient(135deg, var(--gold) 0%, #b38130 100%);
  color: #0b1f3a !important;
  border: none;
  font-family: 'Poppins', sans-serif;
  font-size: 13px;
  font-weight: 600;
  padding: 10px 24px;
  border-radius: 10px;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(200, 150, 62, 0.2);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.btn-saas-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(200, 150, 62, 0.35);
  opacity: 0.95;
  color: #0b1f3a !important;
}

.btn-saas-secondary {
  background: rgba(255, 255, 255, 0.03);
  color: var(--cream-dk) !important;
  border: 1.5px solid var(--glass-border);
  font-family: 'Poppins', sans-serif;
  font-size: 13px;
  font-weight: 500;
  padding: 9px 20px;
  border-radius: 10px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s ease;
  cursor: pointer;
}

.btn-saas-secondary:hover {
  background: rgba(255, 255, 255, 0.07);
  border-color: var(--gold);
  color: var(--cream) !important;
}

.btn-saas-danger {
  background: rgba(220, 38, 36, 0.1);
  color: #f87171 !important;
  border: 1.5px solid rgba(220, 38, 36, 0.25);
  font-family: 'Poppins', sans-serif;
  font-size: 12px;
  font-weight: 500;
  padding: 6px 14px;
  border-radius: 8px;
  text-decoration: none;
  transition: all 0.2s ease;
  cursor: pointer;
}

.btn-saas-danger:hover {
  background: var(--danger);
  color: #fff !important;
  border-color: var(--danger);
}


.saas-table-row td { 
  background: transparent !important;
  border-bottom: 1px solid rgba(255,255,255,0.04) !important;
  transition: background 0.2s ease; 
  vertical-align: middle;
}
.saas-table-row:hover td { 
  background: rgba(30, 65, 117, 0.2) !important; 
}


.saas-badge {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  border-radius: 6px;
  font-family: 'Poppins', sans-serif;
  font-size: 11px;
  font-weight: 500;
  border: 1px solid transparent;
}
.bp-blue { background: rgba(37,99,235,0.08); border-color: rgba(37,99,235,0.2); color: #93c5fd; }
.bp-green { background: rgba(22,163,74,0.08); border-color: rgba(22,163,74,0.2); color: #4ade80; }
.bp-orange { background: rgba(245,158,11,0.08); border-color: rgba(245,158,11,0.2); color: #f59e0b; }
.bp-red { background: rgba(220,38,38,0.08); border-color: rgba(220,38,38,0.2); color: #f87171; }


.avail-btn {
  border: none;
  border-radius: 20px;
  padding: 4px 14px;
  font-size: 11px;
  font-weight: 600;
  font-family: 'Poppins', sans-serif;
  cursor: pointer;
  transition: all 0.2s ease;
}
.avail-on { background: rgba(22, 163, 74, 0.12); color: #4ade80; border: 1px solid rgba(22, 163, 74, 0.25); }
.avail-on:hover { background: rgba(22, 163, 74, 0.2); }
.avail-off { background: rgba(90, 106, 128, 0.15); color: var(--muted); border: 1px solid rgba(255,255,255,0.06); }
.avail-off:hover { background: rgba(255,255,255,0.05); color: var(--cream); }


.confirm-overlay {
  background: rgba(4, 11, 22, 0.75) !important;
  backdrop-filter: blur(8px);
}
.confirm-box {
  background: #0f2442 !important;
  border: 1px solid var(--glass-border) !important;
  border-radius: 18px !important;
  box-shadow: 0 20px 50px rgba(0,0,0,0.6) !important;
  padding: 28px !important;
}
.confirm-title {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 18px;
  font-weight: 700;
  color: var(--cream) !important;
}
.confirm-body {
  font-family: 'Poppins', sans-serif;
  font-size: 13.5px;
  color: var(--muted) !important;
}

.saas-toast-container {
  position: fixed; top: 24px; right: 24px; z-index: 9999;
}
.saas-toast {
  background: rgba(11, 31, 58, 0.85); backdrop-filter: blur(12px); border-left: 4px solid var(--gold);
  color: var(--cream); font-family: 'Poppins', sans-serif; font-size: 13.5px; padding: 14px 24px;
  border-radius: 0 10px 10px 0; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border); border-right: 1px solid var(--glass-border);
}
</style>

<?php if (isset($_SESSION['toast'])): ?>
<div class="saas-toast-container"><div class="saas-toast"><?= h($_SESSION['toast']) ?></div></div>
<?php unset($_SESSION['toast']); endif; ?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 animate-fadeUp">
  <div>
    <p class="page-title" style="font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:28px; color:var(--cream); margin:0; letter-spacing:-0.02em;"><?= isAdmin() ? 'All Menus' : 'My Menu' ?></p>
    <p class="page-sub" style="font-family:'Poppins',sans-serif; font-size:13.5px; color:var(--muted); margin-top:6px;"><?= isAdmin() ? 'View, edit, or remove menu items across all restaurants.' : 'Manage menu items for your restaurant.' ?></p>
  </div>
  <a href="add_menu.php" class="btn-saas-primary">+ Add New Item</a>
</div>

<form method="GET" class="animate-fadeUp" style="animation-delay: 0.1s; background:rgba(255,255,255,0.01); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); border:1px solid var(--glass-border); padding:16px; border-radius:14px; display:flex; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom: 24px;">
  <?php if (isAdmin()): ?>
  <select name="restaurant" class="form-select saas-input" style="max-width:180px;">
    <option value="" style="background:var(--navy); color:var(--muted);">All Restaurants</option>
    <?php if ($restaurants) { $restaurants->data_seek(0); while ($r = $restaurants->fetch_assoc()): ?>
    <option value="<?= $r['id'] ?>" <?= $fRest==$r['id']?'selected':'' ?> style="background:var(--navy); color:var(--cream);"><?= h($r['name']) ?></option>
    <?php endwhile; } ?>
  </select>
  <?php endif; ?>
  <select name="location" class="form-select saas-input" style="max-width:160px;">
    <option value="" style="background:var(--navy); color:var(--muted);">All Locations</option>
    <option value="Setapak"     <?= $fLoc==='Setapak'?'selected':'' ?> style="background:var(--navy); color:var(--cream);">Setapak</option>
    <option value="Wangsa Maju" <?= $fLoc==='Wangsa Maju'?'selected':'' ?> style="background:var(--navy); color:var(--cream);">Wangsa Maju</option>
  </select>
  <select name="budget" class="form-select saas-input" style="max-width:160px;">
    <option value="" style="background:var(--navy); color:var(--muted);">All Budgets</option>
    <option value="Cheap"     <?= $fBudget==='Cheap'?'selected':'' ?> style="background:var(--navy); color:var(--success);">Cheap</option>
    <option value="Moderate"  <?= $fBudget==='Moderate'?'selected':'' ?> style="background:var(--navy); color:var(--warning);">Moderate</option>
    <option value="Expensive" <?= $fBudget==='Expensive'?'selected':'' ?> style="background:var(--navy); color:var(--danger);">Expensive</option>
  </select>
  <select name="category" class="form-select saas-input" style="max-width:160px;">
    <option value="" style="background:var(--navy); color:var(--muted);">All Categories</option>
    <?php foreach (['Rice','Noodles','Bread/Roti','Snacks','Beverages','Desserts','Soups','Salads','Grills','Western','Seafood','Others'] as $cat): ?>
    <option <?= $fCat===$cat?'selected':'' ?> style="background:var(--navy); color:var(--cream);"><?= $cat ?></option>
    <?php endforeach; ?>
  </select>
  <select name="availability" class="form-select saas-input" style="max-width:140px;">
    <option value="" style="background:var(--navy); color:var(--muted);">All Status</option>
    <option value="1" <?= $fAvail==='1'?'selected':'' ?> style="background:var(--navy); color:var(--success);">Available</option>
    <option value="0" <?= $fAvail==='0'?'selected':'' ?> style="background:var(--navy); color:var(--muted);">Unavailable</option>
  </select>
  <button type="submit" class="btn-saas-primary">Filter</button>
  <a href="manage_menu.php" class="btn-saas-secondary">Reset</a>
</form>

<div class="saas-glass-card animate-fadeUp" style="animation-delay: 0.2s;">
  <div class="dash-card-body" style="padding:0;">
    <div style="overflow-x:auto;">
      <table id="menuTable" class="table w-100" style="margin:0; border-collapse:collapse; background:transparent;">
        <thead>
          <tr>
            <th style="width:70px; background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Image</th>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Name</th>
            <?php if (isAdmin()): ?><th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Restaurant</th><?php endif; ?>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Category</th>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Price</th>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Budget</th>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Location</th>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Diet</th>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Status</th>
            <th style="background:rgba(11,31,58,0.5); color:var(--gold-lt); font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:16px 24px; border-bottom:1px solid var(--glass-border);">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($items && $items->num_rows > 0): ?>
          <?php while ($row = $items->fetch_assoc()): ?>
          <tr class="saas-table-row">
            <td style="padding:12px 24px;">
              <?php if ($row['image'] && file_exists('uploads/food_images/'.$row['image'])): ?>
              <img src="uploads/food_images/<?= h($row['image']) ?>" style="width:44px; height:44px; border-radius:8px; object-fit:cover; border:1px solid var(--glass-border);" alt="">
              <?php else: ?>
              <div style="width:44px; height:44px; border-radius:8px; background:rgba(255,255,255,0.02); border:1px dashed var(--glass-border); display:flex; align-items:center; justify-content:center; font-family:'Space Grotesk',sans-serif; font-size:9px; color:var(--muted); text-transform:uppercase; font-weight:700; text-align:center; line-height:1.2;">No Image</div>
              <?php endif; ?>
            </td>
            <td style="padding:12px 24px;"><span style="color:var(--cream); font-weight:600; font-size:14px;"><?= h($row['name']) ?></span></td>
            <?php if (isAdmin()): ?>
            <td style="padding:12px 24px; color:var(--muted); font-size:13px;"><?= h($row['rest_name']) ?></td>
            <?php endif; ?>
            <td style="padding:12px 24px;"><span class="saas-badge bp-blue"><?= h($row['category']) ?></span></td>
            <td style="padding:12px 24px;"><span style="color:var(--cream); font-weight:600;">RM <?= number_format($row['price'],2) ?></span></td>
            <td style="padding:12px 24px;">
              <?php $bMap=['Cheap'=>'bp-green','Moderate'=>'bp-orange','Expensive'=>'bp-red']; ?>
              <span class="saas-badge <?= $bMap[$row['budget_category']]??'bp-blue' ?>"><?= h($row['budget_category']) ?></span>
            </td>
            <td style="padding:12px 24px; color:var(--cream-dk); font-size:13px;"><?= h($row['location']) ?></td>
            <td style="padding:12px 24px;">
              <?php
              $tags = [];
              $dietLabels = [
                'is_halal'         => 'Halal',
                'is_vegetarian'    => 'Veg',
                'is_vegan'         => 'Vegan',
                'is_high_protein'  => 'HiPro',
                'is_spicy'         => 'Spicy'
              ];
              foreach ($dietLabels as $k => $label) {
                if ($row[$k]) {
                  $tags[] = '<span class="saas-badge bp-blue" style="font-size:10px; padding:2px 6px;">'.$label.'</span>';
                }
              }
              echo !empty($tags) ? '<div class="d-flex flex-wrap gap-1">' . implode('', $tags) . '</div>' : '<span style="color:var(--muted); font-size:13px;">—</span>';
              ?>
            </td>
            <td style="padding:12px 24px;">
              <form method="POST" style="display:inline;">
                <input type="hidden" name="toggle_id" value="<?= $row['id'] ?>">
                <input type="hidden" name="current_avail" value="<?= $row['availability'] ?>">
                <button type="submit" class="avail-btn <?= $row['availability']?'avail-on':'avail-off' ?>">
                  <?= $row['availability']?'On':'Off' ?>
                </button>
              </form>
            </td>
            <td style="padding:12px 24px;">
              <div class="d-flex gap-2">
                <a href="add_menu.php?edit=<?= $row['id'] ?>" class="btn-saas-secondary" style="padding:6px 14px; font-size:12px; font-weight:600;">Edit</a>
                <button class="btn-saas-danger delete-btn" data-id="<?= $row['id'] ?>" data-name="<?= h($row['name']) ?>">Delete</button>
                <form id="deleteForm_<?= $row['id'] ?>" method="POST" style="display:none;">
                  <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                </form>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
          <?php else: ?>
          <tr>
            <td colspan="11" class="text-center py-5" style="color:var(--muted); font-family:'Poppins',sans-serif; font-size:13.5px;">
              No menu items found. <a href="add_menu.php" style="color:var(--gold-lt); text-decoration:none; font-weight:600; margin-left:4px;">Add one</a>
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="confirm-overlay" id="confirmOverlay">
  <div class="confirm-box">
    <p class="confirm-title">Delete Menu Item</p>
    <p class="confirm-body" id="confirmBody"></p>
    <div class="confirm-actions d-flex gap-2 justify-content-end mt-4">
      <button id="confirmCancel" class="btn-saas-secondary">Cancel</button>
      <button id="confirmDelete" class="btn-saas-danger" style="padding: 9px 20px; font-size:13px; font-weight:600;">Yes, Delete</button>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>