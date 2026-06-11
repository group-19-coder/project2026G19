<?php


require_once 'config.php';


function budgetToDb(string $b): string {
    return match($b) {
        'low'     => 'Cheap',
        'mid'     => 'Moderate',
        'high', 'premium' => 'Expensive',
        default   => ''
    };
}


function calcMatch(array $item, array $prefs): int {
    $score = 60; // base score

    $dietMap = [
        'vegan'       => ['col' => 'is_vegan',        'weight' => 25],
        'vegetarian'  => ['col' => 'is_vegetarian',   'weight' => 20],
        'halal'       => ['col' => 'is_halal',         'weight' => 20],
        'non_halal'   => ['col' => 'is_non_halal',     'weight' => 20],
        'high_protein'=> ['col' => 'is_high_protein',  'weight' => 15],
        'spicy'       => ['col' => 'is_spicy',         'weight' => 10],
        'non_spicy'   => ['col' => 'is_non_spicy',     'weight' => 10],
    ];

    foreach ($prefs['diet'] as $dietPref) {
        if (isset($dietMap[$dietPref])) {
            $col    = $dietMap[$dietPref]['col'];
            $weight = $dietMap[$dietPref]['weight'];
            if ($item[$col]) {
                $score += $weight;
            } else {
                $score -= intval($weight * 0.5);
            }
        }
    }

    // Penalty: conflicting diets (item is spicy but user wants non-spicy, etc.)
    if (in_array('non_spicy', $prefs['diet']) && $item['is_spicy'])   $score -= 20;
    if (in_array('spicy',     $prefs['diet']) && $item['is_non_spicy'])$score -= 10;
    if (in_array('vegan',     $prefs['diet']) && !$item['is_vegan'])   $score -= 15;
    if (in_array('vegetarian',$prefs['diet']) && !$item['is_vegetarian'])$score -= 10;
    if (in_array('halal',     $prefs['diet']) && $item['is_non_halal'])$score -= 30;
    if (in_array('non_halal', $prefs['diet']) && $item['is_halal'])    $score -= 10;

    return max(0, min(100, $score));
}


function matchLabel(int $score): array {
    if ($score >= 90) return ['Excellent Match', '#27ae60', '#e8fff2', '#a0e0bc'];
    if ($score >= 75) return ['Great Match',     '#7494ec', '#eef1fc', '#c9d6ff'];
    if ($score >= 60) return ['Good Match',      '#e67e22', '#fff3e0', '#ffe0b2'];
    return                   ['Partial Match',   '#8a90a8', '#f5f6fa', '#dde1f0'];
}


$results   = [];
$submitted = false;
$prefs     = ['location' => '', 'budget' => '', 'diet' => [], 'allergy' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = true;

    $location = $conn->real_escape_string(trim($_POST['location'] ?? ''));
    $budget   = trim($_POST['budget'] ?? '');
    $budgetDb = budgetToDb($budget);

    $dietPrefs   = array_map([$conn,'real_escape_string'], $_POST['diet']   ?? []);
    $allergyInfo = array_map([$conn,'real_escape_string'], $_POST['allergy']?? []);

    $prefs = [
        'location' => $location,
        'budget'   => $budget,
        'budgetDb' => $budgetDb,
        'diet'     => $dietPrefs,
        'allergy'  => $allergyInfo,
    ];

    
    $where = "WHERE m.availability = 1";

    if ($location) {
        $where .= " AND m.location = '$location'";
    }
    if ($budgetDb) {
        $where .= " AND m.budget_category = '$budgetDb'";
    }

   
    $allergyColMap = [
        'peanut'  => 'has_peanut',
        'seafood' => 'has_seafood',
        'soy'     => 'has_soy',
        'milk'    => 'has_milk',
        'gluten'  => 'has_gluten',
    ];
    foreach ($allergyInfo as $allergen) {
        if (isset($allergyColMap[$allergen])) {
            $where .= " AND m.{$allergyColMap[$allergen]} = 0";
        }
    }

    $rows = $conn->query("SELECT m.*, r.name AS rest_name, r.area
                           FROM menu_items m
                           JOIN restaurants r ON m.restaurant_id = r.id
                           $where");

    if ($rows) {
        while ($row = $rows->fetch_assoc()) {
            $score        = calcMatch($row, $prefs);
            $row['score'] = $score;
            $results[]    = $row;
        }
        // Sort by score descending
        usort($results, fn($a,$b) => $b['score'] - $a['score']);
    }
}

// Session user info (if logged in as customer)
$email  = $_SESSION['email'] ?? '';
$dbUser = null;
if ($email) {
    $r = $conn->query("SELECT id, name, email FROM users WHERE email = '".($conn->real_escape_string($email))."'");
    $dbUser = ($r && $r->num_rows) ? $r->fetch_assoc() : null;
}
$nameParts = explode(' ', trim($dbUser['name'] ?? 'Guest'), 2);
$initials  = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Orderly — Food Recommendations</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<style>

:root {
  --navy:     #0b1f3a;
  --navy-md:  #132d52;
  --navy-lt:  #1e4175;
  --gold:     #c8963e;
  --gold-lt:  #e0b96a;
  --cream:    #f7f4ef;
  --cream-dk: #ede8e0;
  --text:     #1a2535;
  --muted:    #5a6a80;
  --success:  #16a34a;
  --danger:   #dc2626;
  --warning:  #f59e0b;
  --info:     #2563eb;

  
  --glass-bg:     rgba(255,255,255,0.07);
  --glass-border: rgba(200,150,62,0.22);
  --glass-shadow: 0 8px 40px rgba(0,0,0,0.35);
}


*,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Poppins', sans-serif;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  color: var(--cream);

  
  background-color: var(--navy);
  background-image:
    radial-gradient(ellipse 80% 60% at 10% 0%,   rgba(30,65,117,0.7) 0%, transparent 60%),
    radial-gradient(ellipse 60% 50% at 90% 100%,  rgba(200,150,62,0.15) 0%, transparent 55%),
    radial-gradient(ellipse 50% 40% at 50% 50%,   rgba(11,31,58,0.9) 0%, transparent 80%);
}


@keyframes fadeUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes glowPulse {
  0%,100% { box-shadow: 0 0 20px rgba(200,150,62,0.2); }
  50%      { box-shadow: 0 0 40px rgba(200,150,62,0.4); }
}


.navbar {
  background: rgba(11,31,58,0.75);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(200,150,62,0.18);
  padding: 0 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 4px 24px rgba(0,0,0,0.3);
}

.logo {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
  font-size: 17px;
  color: var(--gold-lt);
  text-decoration: none;
  letter-spacing: 0.02em;
  transition: color .2s;
}
.logo:hover { color: var(--cream); }

.logo-icon {
  width: 34px;
  height: 34px;
  background: linear-gradient(135deg, var(--gold), var(--gold-lt));
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 10px rgba(200,150,62,0.35);
}
.logo-icon svg { width: 17px; height: 17px; fill: var(--navy); }


.nav-right { display: flex; align-items: center; gap: 10px; }

.nav-btn {
  padding: 7px 15px;
  border-radius: 9px;
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  border: 1.5px solid;
  transition: all .2s;
  letter-spacing: 0.02em;
}
.nav-btn-orders {
  color: var(--gold-lt);
  border-color: rgba(200,150,62,0.35);
  background: rgba(200,150,62,0.08);
}
.nav-btn-orders:hover { background: rgba(200,150,62,0.18); border-color: var(--gold); }

.nav-btn-cart {
  position: relative;
  color: var(--cream);
  border-color: rgba(255,255,255,0.15);
  background: rgba(255,255,255,0.06);
}
.nav-btn-cart:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.3); }

.nav-btn-login {
  color: var(--navy);
  border-color: var(--gold);
  background: linear-gradient(135deg, var(--gold), var(--gold-lt));
  box-shadow: 0 3px 12px rgba(200,150,62,0.3);
}
.nav-btn-login:hover { transform: translateY(-1px); box-shadow: 0 5px 18px rgba(200,150,62,0.45); }


.nav-btn-my-orders {
  color: var(--gold-lt);
  border-color: rgba(200,150,62,0.35);
  background: rgba(200,150,62,0.08);
}
.nav-btn-my-orders:hover { background: rgba(200,150,62,0.18); border-color: var(--gold); }

.nav-btn-cart-link {
  position: relative;
  color: var(--cream);
  border-color: rgba(255,255,255,0.15);
  background: rgba(255,255,255,0.06);
}
.nav-btn-cart-link:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.3); }


.avatar-wrap { position: relative; }
.avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--navy-lt), var(--navy-md));
  border: 2px solid var(--gold);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  color: var(--gold-lt);
  cursor: pointer;
  user-select: none;
  transition: all .2s;
  box-shadow: 0 2px 10px rgba(200,150,62,0.2);
}
.avatar:hover { transform: scale(1.05); box-shadow: 0 4px 16px rgba(200,150,62,0.35); }

.dropdown {
  position: absolute;
  top: 48px;
  right: 0;
  background: rgba(13,29,52,0.97);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(200,150,62,0.25);
  border-radius: 14px;
  width: 230px;
  box-shadow: 0 16px 48px rgba(0,0,0,0.5);
  z-index: 200;
  display: none;
  overflow: hidden;
}
.dropdown.open { display: block; animation: fadeUp .2s ease; }

.dd-header {
  padding: 16px 18px;
  border-bottom: 1px solid rgba(200,150,62,0.15);
  background: rgba(200,150,62,0.05);
}
.dd-name  { font-size: 14px; font-weight: 600; color: var(--cream); }
.dd-email { font-size: 11px; color: var(--muted); margin-top: 2px; }

.dd-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px 18px;
  font-size: 13px;
  color: var(--cream-dk);
  text-decoration: none;
  transition: background .15s, color .15s;
}
.dd-item:hover { background: rgba(200,150,62,0.1); color: var(--gold-lt); }
.dd-item.danger { color: #f87171; }
.dd-item.danger:hover { background: rgba(220,38,38,0.12); color: #fca5a5; }
.dd-item svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; opacity: .8; }
.dd-divider { height: 1px; background: rgba(200,150,62,0.12); }


.page {
  display: grid;
  grid-template-columns: 360px 1fr;
  gap: 24px;
  padding: 32px;
  max-width: 1320px;
  margin: 0 auto;
  width: 100%;
}
@media(max-width:960px)  { .page { grid-template-columns: 1fr; } }
@media(max-width:600px)  { .page { padding: 16px; gap: 16px; } }


.card {
  background: var(--glass-bg);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border: 1px solid var(--glass-border);
  border-radius: 18px;
  box-shadow: var(--glass-shadow);
  padding: 28px;
  animation: fadeUp .45s cubic-bezier(.16,1,.3,1) both;
  transition: box-shadow .3s;
}
.card:hover { box-shadow: 0 12px 50px rgba(0,0,0,0.4), 0 0 0 1px rgba(200,150,62,0.15); }


.panel-title {
  font-size: 18px;
  font-weight: 700;
  color: var(--cream);
  margin-bottom: 4px;
  letter-spacing: 0.01em;
}
.panel-sub {
  font-size: 12.5px;
  color: var(--muted);
  margin-bottom: 24px;
  line-height: 1.6;
}


.form-divider {
  height: 1px;
  background: linear-gradient(to right, rgba(200,150,62,0.3), transparent);
  margin-bottom: 24px;
}

.field { margin-bottom: 18px; }

label.flabel {
  display: block;
  font-size: 11px;
  font-weight: 600;
  color: var(--gold-lt);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 8px;
}
label.flabel .req { color: var(--gold); }

select.fselect {
  width: 100%;
  padding: 11px 14px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(200,150,62,0.25);
  border-radius: 10px;
  font-family: 'Poppins', sans-serif;
  font-size: 13px;
  color: var(--cream);
  outline: none;
  transition: border-color .2s, box-shadow .2s, background .2s;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23c8963e' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  cursor: pointer;
}
select.fselect option { background: var(--navy-md); color: var(--cream); }
select.fselect:focus {
  border-color: var(--gold);
  background-color: rgba(200,150,62,0.06);
  box-shadow: 0 0 0 3px rgba(200,150,62,0.12);
}
select.fselect:hover { border-color: rgba(200,150,62,0.45); }


.check-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; }

.check-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 11px;
  border-radius: 10px;
  border: 1px solid rgba(200,150,62,0.18);
  background: rgba(255,255,255,0.03);
  cursor: pointer;
  transition: border-color .15s, background .15s, transform .15s;
}
.check-item:hover {
  border-color: var(--gold);
  background: rgba(200,150,62,0.1);
  transform: translateY(-1px);
}
.check-item input {
  accent-color: var(--gold);
  width: 14px;
  height: 14px;
  cursor: pointer;
}
.check-item label {
  font-size: 11.5px;
  font-weight: 500;
  color: var(--cream-dk);
  cursor: pointer;
}
.check-item.checked {
  border-color: var(--gold);
  background: rgba(200,150,62,0.13);
}
.check-item.checked label { color: var(--gold-lt); }


.form-card { height: fit-content; }


.btn-submit {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, var(--gold), var(--gold-lt));
  border: none;
  border-radius: 11px;
  color: var(--navy);
  font-family: 'Poppins', sans-serif;
  font-size: 14px;
  font-weight: 700;
  letter-spacing: 0.03em;
  cursor: pointer;
  transition: all .25s;
  box-shadow: 0 4px 18px rgba(200,150,62,0.35);
  margin-top: 22px;
}
.btn-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 28px rgba(200,150,62,0.5);
  background: linear-gradient(135deg, var(--gold-lt), var(--gold));
}
.btn-submit:active { transform: translateY(0); }


.results-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 22px;
  flex-wrap: wrap;
  gap: 10px;
}
.results-title {
  font-size: 18px;
  font-weight: 700;
  color: var(--cream);
}
.results-sub {
  font-size: 12.5px;
  color: var(--muted);
  margin-top: 3px;
}

.badge-count {
  background: rgba(200,150,62,0.15);
  color: var(--gold-lt);
  font-size: 11px;
  font-weight: 700;
  padding: 4px 13px;
  border-radius: 20px;
  border: 1px solid rgba(200,150,62,0.3);
  letter-spacing: 0.05em;
}


.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
  gap: 16px;
}


.item-card {
  position: relative;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(200,150,62,0.18);
  border-radius: 15px;
  overflow: hidden;
  transition: transform .25s, box-shadow .25s, border-color .25s;
  animation: fadeUp .4s cubic-bezier(.16,1,.3,1) both;
}
.item-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 16px 40px rgba(0,0,0,0.4), 0 0 0 1px rgba(200,150,62,0.35);
  border-color: rgba(200,150,62,0.4);
}
.item-card.best {
  border-color: var(--gold);
  box-shadow: 0 0 0 1.5px rgba(200,150,62,0.5), 0 8px 32px rgba(0,0,0,0.3);
  animation: glowPulse 3s ease infinite, fadeUp .4s cubic-bezier(.16,1,.3,1) both;
}

.item-img {
  width: 100%;
  height: 128px;
  object-fit: cover;
}
.item-img-placeholder {
  width: 100%;
  height: 128px;
  background: linear-gradient(135deg, rgba(30,65,117,0.6), rgba(11,31,58,0.8));
  display: flex;
  align-items: center;
  justify-content: center;
}
.item-img-placeholder svg {
  width: 38px;
  height: 38px;
  stroke: rgba(200,150,62,0.35);
  fill: none;
  stroke-width: 1.5;
  stroke-linecap: round;
  stroke-linejoin: round;
}


.best-badge {
  position: absolute;
  top: 10px;
  left: 10px;
  background: linear-gradient(135deg, var(--gold), var(--gold-lt));
  color: var(--navy);
  font-size: 9px;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 20px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  box-shadow: 0 2px 8px rgba(200,150,62,0.4);
}

.item-body { padding: 13px 15px 15px; }

.item-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--cream);
  margin-bottom: 2px;
  line-height: 1.3;
}
.item-rest {
  font-size: 11px;
  color: var(--muted);
  margin-bottom: 10px;
}
.item-price {
  font-size: 15px;
  font-weight: 700;
  color: var(--gold-lt);
}


.match-badge {
  font-size: 9.5px;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 12px;
  border: 1px solid;
  letter-spacing: 0.03em;
}


.score-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 9px;
}
.score-label { font-size: 10.5px; color: var(--muted); }
.score-val   { font-size: 14px; font-weight: 700; }

.score-bar-wrap {
  margin-top: 6px;
  background: rgba(255,255,255,0.08);
  border-radius: 6px;
  height: 5px;
  overflow: hidden;
}
.score-bar {
  height: 5px;
  border-radius: 6px;
  transition: width .9s ease;
}


.tags-strip { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 9px; }


.item-price-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}


.add-cart-btn.is-loading { background: rgba(138,144,168,0.2); color: var(--muted); border-color: rgba(138,144,168,0.3); }
.add-cart-btn.is-success  { background: rgba(39,174,96,0.2);  color: #6ee7a0;      border-color: rgba(39,174,96,0.4); }
.add-cart-btn.is-error    { background: rgba(220,38,38,0.15); color: #fca5a5;      border-color: rgba(220,38,38,0.35); }
.mini-tag {
  font-size: 9px;
  padding: 2px 7px;
  border-radius: 10px;
  background: rgba(200,150,62,0.12);
  color: var(--gold-lt);
  font-weight: 600;
  border: 1px solid rgba(200,150,62,0.2);
  letter-spacing: 0.03em;
}


.add-cart-btn {
  margin-top: 11px;
  width: 100%;
  padding: 9px;
  background: rgba(200,150,62,0.15);
  color: var(--gold-lt);
  border: 1.5px solid rgba(200,150,62,0.35);
  border-radius: 9px;
  font-family: 'Poppins', sans-serif;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s;
  letter-spacing: 0.02em;
}
.add-cart-btn:hover {
  background: var(--gold);
  color: var(--navy);
  border-color: var(--gold);
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(200,150,62,0.3);
}


.empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 64px 20px;
  text-align: center;
  color: var(--muted);
}
.empty svg {
  width: 52px;
  height: 52px;
  stroke: rgba(200,150,62,0.4);
  fill: none;
  stroke-width: 1.5;
  margin-bottom: 18px;
}
.empty p { font-size: 13.5px; line-height: 1.7; }
.empty strong { color: var(--gold-lt); }


#cartBadge {
  position: absolute;
  top: -6px;
  right: -6px;
  background: var(--danger);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  text-align: center;
  line-height: 18px;
}
</style>
</head>
<body>

<nav class="navbar">
  <a href="recommendation.php" class="logo">
    <div class="logo-icon"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></div>
    Orderly
  </a>
  <div class="nav-right">
    <a href="my_orders_customer.php" class="nav-btn nav-btn-my-orders">My Orders</a>
    <a href="cart.php" id="cartLink" class="nav-btn nav-btn-cart-link">Cart<span id="cartBadge" style="display:none;"></span></a>
    <?php if ($dbUser): ?>
    <div class="avatar-wrap">
      <div class="avatar" id="avatarBtn"><?= htmlspecialchars($initials) ?></div>
      <div class="dropdown" id="dropdown">
        <div class="dd-header">
          <div class="dd-name"><?= htmlspecialchars($dbUser['name']) ?></div>
          <div class="dd-email"><?= htmlspecialchars($dbUser['email']) ?></div>
        </div>
        <a href="view_profile.php" class="dd-item">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          My Profile
        </a>
        <div class="dd-divider"></div>
        <a href="logout.php" class="dd-item danger">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
          Log out
        </a>
      </div>
    </div>
    <?php else: ?>
    <a href="signup_login.php" class="nav-btn nav-btn-login">Login</a>
    <?php endif; ?>
  </div>
</nav>

<div class="page">
  
  <div class="card form-card">
    <p class="panel-title">Find Your Food</p>
    <p class="panel-sub">Tell us your preferences and we'll find the best matches in Setapak &amp; Wangsa Maju.</p>

    <form method="POST">
    
      <div class="field">
        <label class="flabel">Location <span class="req">*</span></label>
        <select name="location" class="fselect" required>
          <option value="" disabled <?= !$prefs['location'] ? 'selected' : '' ?>>Select area…</option>
          <option value="Setapak"     <?= $prefs['location']==='Setapak'     ? 'selected' : '' ?>>Setapak</option>
          <option value="Wangsa Maju" <?= $prefs['location']==='Wangsa Maju' ? 'selected' : '' ?>>Wangsa Maju</option>
        </select>
      </div>

      
      <div class="field">
        <label class="flabel">Budget <span class="req">*</span></label>
        <select name="budget" class="fselect" required>
          <option value="" disabled <?= !$prefs['budget'] ? 'selected' : '' ?>>Select budget…</option>
          <option value="low"     <?= $prefs['budget']==='low'     ? 'selected' : '' ?>>Under RM 15 (Cheap)</option>
          <option value="mid"     <?= $prefs['budget']==='mid'     ? 'selected' : '' ?>>RM 15 – 35 (Moderate)</option>
          <option value="high"    <?= $prefs['budget']==='high'    ? 'selected' : '' ?>>RM 35 – 100 (Expensive)</option>
        </select>
      </div>

      
      <div class="field">
        <label class="flabel">Diet Preferences</label>
        <div class="check-grid">
          <?php
          $dietOptions = [
            'vegetarian'=>'Vegetarian','vegan'=>'Vegan','halal'=>'Halal',
            'non_halal'=>'Non-Halal','high_protein'=>'High Protein',
            'spicy'=>'Spicy','non_spicy'=>'Non-Spicy',
          ];
          foreach ($dietOptions as $val => $lbl):
            $chk = in_array($val, $prefs['diet']) ? 'checked' : '';
          ?>
          <div class="check-item <?= $chk ? 'checked' : '' ?>">
            <input type="checkbox" name="diet[]" id="d_<?= $val ?>" value="<?= $val ?>" <?= $chk ?> onchange="this.closest('.check-item').classList.toggle('checked',this.checked)">
            <label for="d_<?= $val ?>"><?= $lbl ?></label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      
      <div class="field">
        <label class="flabel">Allergy / Avoid</label>
        <div class="check-grid">
          <?php
          $allergyOptions = ['peanut'=>'Peanut','seafood'=>'Seafood','soy'=>'Soy','milk'=>'Milk','gluten'=>'Gluten'];
          foreach ($allergyOptions as $val => $lbl):
            $chk = in_array($val, $prefs['allergy']) ? 'checked' : '';
          ?>
          <div class="check-item <?= $chk ? 'checked' : '' ?>">
            <input type="checkbox" name="allergy[]" id="a_<?= $val ?>" value="<?= $val ?>" <?= $chk ?> onchange="this.closest('.check-item').classList.toggle('checked',this.checked)">
            <label for="a_<?= $val ?>"><?= $lbl ?></label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <button type="submit" class="btn-submit">Get Recommendations</button>
    </form>
  </div>

  
  <div class="card">
    <div class="results-header">
      <div>
        <p class="results-title">
          <?php if ($submitted): ?>Recommendations for You
          <?php else: ?>Your Matches<?php endif; ?>
        </p>
        <p class="results-sub">
          <?php if ($submitted && $prefs['location']): ?>
            <?= count($results) ?> item<?= count($results) !== 1 ? 's' : '' ?> found in <?= h($prefs['location']) ?>
          <?php else: ?>Set your preferences to see matches<?php endif; ?>
        </p>
      </div>
      <?php if ($submitted): ?>
      <span class="badge-count"><?= count($results) ?> results</span>
      <?php endif; ?>
    </div>

    <?php if (!$submitted): ?>
    <div class="empty">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
      <p>Set your preferences and click<br><strong>Get Recommendations</strong></p>
    </div>

    <?php elseif (empty($results)): ?>
    <div class="empty">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <p>No items matched your preferences.<br>Try adjusting your filters!</p>
    </div>

    <?php else: ?>
    <div class="items-grid">
      <?php foreach ($results as $i => $item):
        $score = $item['score'];
        [$matchText, $color, $bgColor, $borderColor] = matchLabel($score);
        $isBest = $i === 0;
      ?>
      <div class="item-card <?= $isBest ? 'best' : '' ?>" style="animation-delay:<?= $i * 0.04 ?>s;">
        <?php if ($isBest): ?><div class="best-badge">Best Match</div><?php endif; ?>
        <?php if ($item['image'] && file_exists('uploads/food_images/'.$item['image'])): ?>
        <img src="uploads/food_images/<?= h($item['image']) ?>" class="item-img" alt="<?= h($item['name']) ?>">
        <?php else: ?>
        <div class="item-img-placeholder"><svg viewBox="0 0 24 24"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg></div>
        <?php endif; ?>
        <div class="item-body">
          <div class="item-name"><?= h($item['name']) ?></div>
          <div class="item-rest"><?= h($item['rest_name']) ?></div>
          <div class="item-price-row">
            <span class="item-price">RM <?= number_format($item['price'],2) ?></span>
            <span class="match-badge" style="background:<?= $bgColor ?>;color:<?= $color ?>;border-color:<?= $borderColor ?>;"><?= $matchText ?></span>
          </div>
          <div class="score-row">
            <span class="score-label">Match Score</span>
            <span class="score-val" style="color:<?= $color ?>;"><?= $score ?>%</span>
          </div>
          <div class="score-bar-wrap">
            <div class="score-bar" style="width:<?= $score ?>%;background:<?= $color ?>;"></div>
          </div>
          
          <?php
          $tags = [];
          if ($item['is_halal'])        $tags[] = 'Halal';
          if ($item['is_vegan'])        $tags[] = 'Vegan';
          if ($item['is_vegetarian'])   $tags[] = 'Vegetarian';
          if ($item['is_high_protein']) $tags[] = 'High Protein';
          if ($item['is_spicy'])        $tags[] = 'Spicy';
          if ($item['is_non_spicy'])    $tags[] = 'Mild';
          if (!empty($tags)):
          ?>
          <div class="tags-strip">
            <?php foreach ($tags as $t): ?><span class="mini-tag"><?= $t ?></span><?php endforeach; ?>
          </div>
          <?php endif; ?>
          <button class="add-cart-btn" data-id="<?= $item['id'] ?>" onclick="addToCart(<?= $item['id'] ?>, this)">Add to Cart</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>


<script>

<?php
$custId = $_SESSION['user_id'] ?? 0;
$cartCount = 0;
if ($custId) {
    $conn->query("CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_cart (customer_id, menu_item_id)
    )");
    $cartCount = (int)$conn->query("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE customer_id='$custId'")->fetch_row()[0];
}
?>
const IS_LOGGED_IN = <?= $custId ? 'true' : 'false' ?>;
let cartCount = <?= $cartCount ?>;

function updateBadge(count) {
    cartCount = count;
    const badge = document.getElementById('cartBadge');
    if (!badge) return;
    badge.textContent = count;
    badge.style.display = count > 0 ? 'flex' : 'none';
    badge.style.alignItems = 'center';
    badge.style.justifyContent = 'center';
}
updateBadge(cartCount);

async function addToCart(itemId, btn) {
    if (!IS_LOGGED_IN) {
        window.location.href = 'signup_login.php';
        return;
    }
    const orig = btn.textContent;
    btn.textContent = 'Adding…';
    btn.disabled = true;
    btn.classList.add('is-loading');

    const fd = new FormData();
    fd.append('action', 'add');
    fd.append('item_id', itemId);
    fd.append('qty', 1);

    try {
        const res = await fetch('cart_handler.php', {method:'POST', body:fd});
        const data = await res.json();
        if (data.ok) {
            btn.textContent = 'Added!';
            btn.classList.remove('is-loading');
            btn.classList.add('is-success');
            updateBadge(data.count);
            setTimeout(() => {
                btn.textContent = orig;
                btn.classList.remove('is-success');
                btn.disabled = false;
            }, 1500);
        } else if (data.conflict) {
            if (confirm(data.msg + '\n\nGo to cart to clear it?')) {
                window.location.href = 'cart.php';
            }
            btn.textContent = orig;
            btn.classList.remove('is-loading');
            btn.disabled = false;
        } else {
            btn.textContent = data.msg || 'Error';
            btn.classList.remove('is-loading');
            btn.classList.add('is-error');
            setTimeout(() => {
                btn.textContent = orig;
                btn.classList.remove('is-error');
                btn.disabled = false;
            }, 2000);
        }
    } catch(e) {
        btn.textContent = orig;
        btn.classList.remove('is-loading');
        btn.disabled = false;
    }
}
</script>

<script>
const avatarBtn = document.getElementById('avatarBtn');
const dropdown  = document.getElementById('dropdown');
if (avatarBtn) {
  avatarBtn.addEventListener('click', e => { e.stopPropagation(); dropdown.classList.toggle('open'); });
  document.addEventListener('click', () => dropdown.classList.remove('open'));
}
</script>
</body>
</html>