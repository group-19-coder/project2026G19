<?php
require_once 'config.php';
requireVendor();

$vendorRestId = getVendorRestaurantId();

$editId = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$isEdit = $editId > 0;
$item   = [];
$errors = [];

// Load item for editing with scope check
if ($isEdit) {
    $r = $conn->query("SELECT * FROM menu_items WHERE id='$editId'");
    if ($r && $r->num_rows) {
        $item = $r->fetch_assoc();
        // Vendor staff can only edit their restaurant's items
        if ($vendorRestId > 0) enforceRestaurantScope((int)$item['restaurant_id']);
    } else {
        header("Location: manage_menu.php"); exit();
    }
}

// Restaurants: admin sees all, vendor sees only theirs
if (isAdmin()) {
    $restaurants = $conn->query("SELECT id, name, area FROM restaurants ORDER BY name");
} else {
    $restaurants = $vendorRestId
        ? $conn->query("SELECT id, name, area FROM restaurants WHERE id='$vendorRestId'")
        : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vendor staff can only post to their restaurant
    $restId = $vendorRestId > 0 ? $vendorRestId : intval($_POST['restaurant_id'] ?? 0);
    if ($vendorRestId > 0 && $restId !== $vendorRestId) {
        die('Unauthorized.');
    }

    $name       = trim($_POST['name'] ?? '');
    $price      = floatval($_POST['price'] ?? 0);
    $category   = trim($_POST['category'] ?? '');
    $desc       = trim($_POST['description'] ?? '');
    $avail      = isset($_POST['availability']) ? 1 : 0;
    $location   = trim($_POST['location'] ?? '');
    $budget     = trim($_POST['budget_category'] ?? '');
    $isVeg      = isset($_POST['is_vegetarian'])  ? 1 : 0;
    $isVegan    = isset($_POST['is_vegan'])        ? 1 : 0;
    $isHP       = isset($_POST['is_high_protein']) ? 1 : 0;
    $isHalal    = isset($_POST['is_halal'])        ? 1 : 0;
    $isNonHalal = isset($_POST['is_non_halal'])    ? 1 : 0;
    $isSpicy    = isset($_POST['is_spicy'])        ? 1 : 0;
    $isNonSpicy = isset($_POST['is_non_spicy'])    ? 1 : 0;
    $aPeanut    = isset($_POST['has_peanut'])   ? 1 : 0;
    $aSeafood   = isset($_POST['has_seafood'])  ? 1 : 0;
    $aSoy       = isset($_POST['has_soy'])      ? 1 : 0;
    $aMilk      = isset($_POST['has_milk'])     ? 1 : 0;
    $aGluten    = isset($_POST['has_gluten'])   ? 1 : 0;

    if (!$restId)     $errors[] = 'Please select a restaurant.';
    if (!$name)       $errors[] = 'Menu name is required.';
    if ($price <= 0)  $errors[] = 'Price must be greater than 0.';
    if (!$category)   $errors[] = 'Category is required.';
    if (!$location)   $errors[] = 'Location is required.';
    if (!$budget)     $errors[] = 'Budget category is required.';

    $imageName = $item['image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $errors[] = 'Image must be JPG, PNG, GIF or WebP.';
        } elseif ($_FILES['image']['size'] > 3*1024*1024) {
            $errors[] = 'Image must be under 3 MB.';
        } else {
            $imageName = uniqid('food_').'.'.$ext;
            move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/food_images/'.$imageName);
        }
    }

    if (empty($errors)) {
        $nameE = $conn->real_escape_string($name);
        $descE = $conn->real_escape_string($desc);
        $catE  = $conn->real_escape_string($category);
        $locE  = $conn->real_escape_string($location);
        $budE  = $conn->real_escape_string($budget);
        $imgE  = $conn->real_escape_string($imageName ?? '');

        if ($isEdit) {
            $conn->query("UPDATE menu_items SET
                restaurant_id='$restId', name='$nameE', price='$price', category='$catE',
                description='$descE', image=" . ($imgE ? "'$imgE'" : "image") . ",
                availability='$avail', location='$locE', budget_category='$budE',
                is_vegetarian='$isVeg', is_vegan='$isVegan', is_high_protein='$isHP',
                is_halal='$isHalal', is_non_halal='$isNonHalal',
                is_spicy='$isSpicy', is_non_spicy='$isNonSpicy',
                has_peanut='$aPeanut', has_seafood='$aSeafood', has_soy='$aSoy',
                has_milk='$aMilk', has_gluten='$aGluten'
                WHERE id='$editId'");
            $_SESSION['toast'] = "{$name} updated!";
        } else {
            $conn->query("INSERT INTO menu_items
                (restaurant_id,name,price,category,description,image,availability,location,budget_category,
                 is_vegetarian,is_vegan,is_high_protein,is_halal,is_non_halal,is_spicy,is_non_spicy,
                 has_peanut,has_seafood,has_soy,has_milk,has_gluten)
                VALUES ('$restId','$nameE','$price','$catE','$descE','$imgE','$avail','$locE','$budE',
                '$isVeg','$isVegan','$isHP','$isHalal','$isNonHalal','$isSpicy','$isNonSpicy',
                '$aPeanut','$aSeafood','$aSoy','$aMilk','$aGluten')");
            $_SESSION['toast'] = "{$name} added!";
        }
        header("Location: manage_menu.php"); exit();
    }
}

$pageTitle  = $isEdit ? 'Edit Menu Item' : 'Add Menu Item';
$activePage = 'add_menu';
if (isAdmin()) { require_once 'admin_header.php'; }
else           { require_once 'vendor_header.php'; }

$v = fn($k) => h($item[$k] ?? '');
$checked = fn($k) => !empty($item[$k]) ? 'checked' : '';
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
  font-family: 'Poppins', sans-serif;
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

.saas-glass-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.6);
  border-color: rgba(200, 150, 62, 0.2);
}

.saas-card-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--glass-border);
  background: rgba(255, 255, 255, 0.01);
}

.saas-card-title {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 13px;
  font-weight: 700;
  color: var(--gold-lt);
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.saas-card-body {
  padding: 24px;
}


.saas-form-label {
  font-family: 'Poppins', sans-serif;
  font-size: 13px;
  font-weight: 500;
  color: var(--cream-dk);
  margin-bottom: 8px;
  display: inline-block;
}

.saas-input {
  background: var(--glass-input-bg) !important;
  border: 1.5px solid var(--glass-border) !important;
  color: var(--cream) !important;
  font-family: 'Poppins', sans-serif;
  font-size: 13.5px;
  padding: 12px 16px;
  border-radius: 10px;
  transition: all 0.25s ease !important;
}

.saas-input:focus {
  background: rgba(11, 31, 58, 0.7) !important;
  border-color: var(--gold) !important;
  color: var(--cream) !important;
  box-shadow: 0 0 0 4px rgba(200, 150, 62, 0.15) !important;
  outline: none;
}

.saas-input::placeholder {
  color: var(--muted) !important;
  opacity: 0.7;
}

.saas-input-disabled {
  background: rgba(255, 255, 255, 0.02) !important;
  border: 1.5px dashed var(--glass-border) !important;
  color: var(--muted) !important;
  font-family: 'Poppins', sans-serif;
  font-size: 13.5px;
  padding: 12px 16px;
  border-radius: 10px;
}


.saas-checkbox-container input[type="checkbox"] {
  background-color: var(--navy-md) !important;
  border-color: var(--glass-border) !important;
  border-radius: 5px;
  width: 17px;
  height: 17px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.saas-checkbox-container input[type="checkbox"]:checked {
  background-color: var(--gold) !important;
  border-color: var(--gold) !important;
}

.saas-checkbox-label {
  font-family: 'Poppins', sans-serif;
  font-size: 13px;
  color: var(--cream-dk);
  cursor: pointer;
  margin-left: 6px;
  user-select: none;
}


.saas-switch input[type="checkbox"] {
  width: 36px;
  height: 20px;
  background-color: var(--navy-lt) !important;
  border-color: transparent !important;
  border-radius: 20px;
  cursor: pointer;
}

.saas-switch input[type="checkbox"]:checked {
  background-color: var(--gold) !important;
}


.btn-saas-primary {
  background: linear-gradient(135deg, var(--gold) 0%, #b38130 100%);
  color: #0b1f3a !important;
  border: none;
  font-family: 'Poppins', sans-serif;
  font-size: 13.5px;
  font-weight: 600;
  padding: 12px 28px;
  border-radius: 10px;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(200, 150, 62, 0.2);
  transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.btn-saas-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(200, 150, 62, 0.35);
  opacity: 0.95;
}

.btn-saas-primary:active {
  transform: translateY(0);
}

.btn-saas-secondary {
  background: rgba(255, 255, 255, 0.03);
  color: var(--cream-dk) !important;
  border: 1.5px solid var(--glass-border);
  font-family: 'Poppins', sans-serif;
  font-size: 13.5px;
  font-weight: 500;
  padding: 11px 26px;
  border-radius: 10px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s ease;
}

.btn-saas-secondary:hover {
  background: rgba(255, 255, 255, 0.07);
  border-color: var(--gold);
  color: var(--cream) !important;
}
</style>

<div class="page-header mb-5 animate-fadeUp">
  <p class="page-title" style="font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:28px; color:var(--cream); margin:0; letter-spacing:-0.02em;"><?= $isEdit ? 'Edit Menu Item' : 'Add New Menu Item' ?></p>
  <p class="page-sub" style="font-family:'Poppins',sans-serif; font-size:13.5px; color:var(--muted); margin-top:6px;"><?= $isEdit ? 'Update the details of this menu item.' : 'Fill in the details to add a new food item.' ?></p>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger rounded-4 mb-4 animate-fadeUp" style="font-size:13.5px; background:rgba(220,38,38,0.08); border:1px solid rgba(220,38,38,0.2); color:#fca5a5; padding:16px 20px;">
  <?php foreach ($errors as $e): ?><div>• <?= h($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="animate-fadeUp" style="animation-delay:0.05s;">
<div class="row g-4">
  <div class="col-lg-8">
    
    <div class="saas-glass-card mb-4">
      <div class="saas-card-header">
        <span class="saas-card-title">Basic Information</span>
      </div>
      <div class="saas-card-body">
        <div class="row g-3">
          
          <div class="col-md-12">
            <label class="saas-form-label">Restaurant <span class="req" style="color:var(--danger);">*</span></label>
            <?php if (isAdmin()): ?>
            <select name="restaurant_id" class="form-select saas-input" required>
              <option value="" style="background:var(--navy); color:var(--muted);">-- Select Restaurant --</option>
              <?php if ($restaurants) { $restaurants->data_seek(0); while ($r = $restaurants->fetch_assoc()): ?>
              <option value="<?= $r['id'] ?>" <?= ($item['restaurant_id'] ?? 0)==$r['id']?'selected':'' ?> style="background:var(--navy); color:var(--cream);">
                <?= h($r['name']) ?> (<?= h($r['area']) ?>)
              </option>
              <?php endwhile; } ?>
            </select>
            <?php else: ?>
              <?php if ($restaurants && $restaurants->num_rows > 0):
                $restaurants->data_seek(0); $r = $restaurants->fetch_assoc(); ?>
              <input type="hidden" name="restaurant_id" value="<?= $r['id'] ?>">
              <input type="text" class="form-control saas-input-disabled" value="<?= h($r['name']) ?> (<?= h($r['area']) ?>)" disabled>
              <?php else: ?>
              <input type="text" class="form-control saas-input-disabled" value="No restaurant assigned" disabled style="color:var(--danger) !important;">
              <?php endif; ?>
            <?php endif; ?>
          </div>

          <div class="col-md-8">
            <label class="saas-form-label">Menu Item Name <span class="req" style="color:var(--danger);">*</span></label>
            <input type="text" name="name" class="form-control saas-input" placeholder="e.g. Spicy Beef Ramen" value="<?= $v('name') ?>" required />
          </div>

          <div class="col-md-4">
            <label class="saas-form-label">Price (RM) <span class="req" style="color:var(--danger);">*</span></label>
            <input type="number" id="price" name="price" class="form-control saas-input" placeholder="0.00" step="0.01" min="0.01" value="<?= $v('price') ?>" required />
          </div>

          <div class="col-md-6">
            <label class="saas-form-label">Category <span class="req" style="color:var(--danger);">*</span></label>
            <select name="category" class="form-select saas-input" required>
              <option value="" style="background:var(--navy); color:var(--muted);">-- Select Category --</option>
              <?php foreach (['Rice','Noodles','Bread/Roti','Snacks','Beverages','Desserts','Soups','Salads','Grills','Western','Seafood','Others'] as $cat): ?>
              <option <?= ($item['category']??'')===$cat?'selected':'' ?> style="background:var(--navy); color:var(--cream);"><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3">
            <label class="saas-form-label">Location <span class="req" style="color:var(--danger);">*</span></label>
            <select name="location" class="form-select saas-input" required>
              <option value="" style="background:var(--navy); color:var(--muted);">-- Select --</option>
              <option value="Setapak"     <?= ($item['location']??'')==='Setapak'?'selected':'' ?> style="background:var(--navy); color:var(--cream);">Setapak</option>
              <option value="Wangsa Maju" <?= ($item['location']??'')==='Wangsa Maju'?'selected':'' ?> style="background:var(--navy); color:var(--cream);">Wangsa Maju</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="saas-form-label">Budget <span class="req" style="color:var(--danger);">*</span></label>
            <select id="budgetCategory" name="budget_category" class="form-select saas-input" required>
              <option value="" style="background:var(--navy); color:var(--muted);">-- Select --</option>
              <option value="Cheap"     <?= ($item['budget_category']??'')==='Cheap'?'selected':'' ?> style="background:var(--navy); color:var(--success);">Cheap</option>
              <option value="Moderate"  <?= ($item['budget_category']??'')==='Moderate'?'selected':'' ?> style="background:var(--navy); color:var(--warning);">Moderate</option>
              <option value="Expensive" <?= ($item['budget_category']??'')==='Expensive'?'selected':'' ?> style="background:var(--navy); color:var(--danger);">Expensive</option>
            </select>
          </div>

          <div class="col-12">
            <label class="saas-form-label">Description</label>
            <textarea name="description" class="form-control saas-input" rows="3" style="resize:none;"><?= $v('description') ?></textarea>
          </div>

          <div class="col-12 mt-3">
            <div class="form-check form-switch saas-switch d-flex align-items-center" style="padding-left: 0;">
              <input class="form-check-input" type="checkbox" name="availability" id="availCheck" value="1" <?= ($item['availability']??1)?'checked':'' ?> style="margin-left: 0; margin-right: 12px;">
              <label class="form-check-label" for="availCheck" style="font-family:'Poppins',sans-serif; font-size:13.5px; font-weight:500; color:var(--cream); cursor:pointer;">Available for order</label>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="saas-glass-card mb-4">
      <div class="saas-card-header">
        <span class="saas-card-title">Diet Preferences</span>
      </div>
      <div class="saas-card-body">
        <div class="row g-3">
          <?php
          $diets = ['is_vegetarian'=>'Vegetarian','is_vegan'=>'Vegan','is_high_protein'=>'High Protein','is_halal'=>'Halal','is_non_halal'=>'Non-Halal','is_spicy'=>'Spicy Lover','is_non_spicy'=>'Non-Spicy'];
          $dietIds = ['is_halal'=>'dietHalal','is_non_halal'=>'dietNonHalal','is_spicy'=>'dietSpicy','is_non_spicy'=>'dietNonSpicy'];
          foreach ($diets as $key => $label):
            $did = $dietIds[$key] ?? $key;
          ?>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="form-check saas-checkbox-container d-flex align-items-center">
              <input class="form-check-input" type="checkbox" name="<?= $key ?>" id="<?= $did ?>" value="1" <?= $checked($key) ?>>
              <label class="saas-checkbox-label" for="<?= $did ?>"><?= $label ?></label>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="saas-glass-card">
      <div class="saas-card-header">
        <span class="saas-card-title">Allergy Information</span>
      </div>
      <div class="saas-card-body">
        <div class="row g-3">
          <?php
          $allergies = ['has_peanut'=>'Peanut','has_seafood'=>'Seafood','has_soy'=>'Soy','has_milk'=>'Milk','has_gluten'=>'Gluten'];
          foreach ($allergies as $key => $label):
          ?>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="form-check saas-checkbox-container d-flex align-items-center">
              <input class="form-check-input" type="checkbox" name="<?= $key ?>" id="<?= $key ?>" value="1" <?= $checked($key) ?>>
              <label class="saas-checkbox-label" for="<?= $key ?>"><?= $label ?></label>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="saas-glass-card mb-4">
      <div class="saas-card-header">
        <span class="saas-card-title">Food Image</span>
      </div>
      <div class="saas-card-body" style="text-align:center;">
        <div class="img-preview-wrap mb-4" id="imgPreviewWrap" style="background:rgba(11, 31, 58, 0.5); border:2px dashed var(--glass-border); border-radius:14px; height:220px; display:flex; align-items:center; justify-content:center; overflow:hidden; transition: border-color 0.25s ease;">
          <div id="imgPreview" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center;">
            <?php if (!empty($item['image']) && file_exists('uploads/food_images/'.$item['image'])): ?>
            <img src="uploads/food_images/<?= h($item['image']) ?>" style="width:100%; height:100%; object-fit:cover;">
            <?php else: ?>
            <div class="img-placeholder" style="color:var(--muted); font-family:'Poppins',sans-serif; font-size:13px;">
              <div class="icon" style="font-size:28px; margin-bottom:8px; color:var(--gold-lt);">📷</div>
              <div>Click to upload image</div>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <input type="file" id="foodImageInput" name="image" accept="image/*" style="display:none;">
        <button type="button" class="btn-saas-secondary w-100" onclick="document.getElementById('foodImageInput').click()">
          <?= $isEdit ? 'Change Image' : 'Upload Image' ?>
        </button>
      </div>
    </div>

    <div class="saas-glass-card" style="background: rgba(200, 150, 62, 0.03); border: 1px dashed rgba(200, 150, 62, 0.25);">
      <div class="saas-card-body" style="padding:20px;">
        <p style="font-family:'Space Grotesk',sans-serif; font-size:12px; font-weight:700; color:var(--gold-lt); margin-bottom:12px; text-transform:uppercase; letter-spacing:0.05em;">Quick Tips</p>
        <ul style="font-family:'Poppins',sans-serif; font-size:12.5px; color:var(--muted); line-height:1.8; padding-left:16px; margin:0;">
          <li>Budget auto-fills based on price parameters</li>
          <li>Halal and Non-Halal are mutually exclusive</li>
          <li>Mark all allergens accurately for user filters</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<div class="mt-5 d-flex gap-3">
  <button type="submit" class="btn-saas-primary">
    <?= $isEdit ? 'Save Changes' : 'Add Menu Item' ?>
  </button>
  <a href="manage_menu.php" class="btn-saas-secondary">Cancel</a>
</div>
</form>

<script>
// Live execution micro frontend image rendering setup preview handling
document.getElementById('foodImageInput')?.addEventListener('change', function(e) {
  const file = e.target.files[0];
  if(file) {
    const reader = new FileReader();
    reader.onload = function(event) {
      const wrap = document.getElementById('imgPreview');
      if(wrap) {
        wrap.innerHTML = `<img src="${event.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
      }
      const container = document.getElementById('imgPreviewWrap');
      if(container) {
        container.style.borderColor = 'var(--gold)';
      }
    };
    reader.readAsDataURL(file);
  }
});
</script>

<?php require_once 'includes/footer.php'; ?>
