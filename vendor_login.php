<?php
require_once 'config.php';


if (isVendorOrAdmin()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $conn->prepare("SELECT id, name, email, password, role, restaurant_id FROM users WHERE email = ? AND role IN ('vendor_staff','admin')");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $u = $result->fetch_assoc();
        if (password_verify($password, $u['password'])) {
            $_SESSION['user_id']       = $u['id'];
            $_SESSION['name']          = $u['name'];
            $_SESSION['email']         = $u['email'];
            $_SESSION['role']          = $u['role'];
            $_SESSION['restaurant_id'] = $u['restaurant_id'] ? intval($u['restaurant_id']) : null;
            header("Location: dashboard.php");
            exit();
        }
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Orderly — Vendor Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{
    box-sizing:border-box;
    margin:0;
    padding:0;
    font-family:"Poppins",sans-serif;
}

body{
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
    background:linear-gradient(135deg,#0b1f3a,#132d52,#1e4175);
    color:#1a2535;
    overflow:hidden;
    position:relative;
}

body::before{
    content:'';
    position:absolute;
    width:500px;
    height:500px;
    border-radius:50%;
    background:radial-gradient(circle,rgba(200,150,62,.15),transparent 70%);
    top:-120px;
    right:-120px;
    pointer-events:none;
}

body::after{
    content:'';
    position:absolute;
    width:400px;
    height:400px;
    border-radius:50%;
    background:radial-gradient(circle,rgba(30,65,117,.6),transparent 70%);
    bottom:-120px;
    left:-120px;
    pointer-events:none;
}

.card{
    position:relative;
    z-index:1;
    width:100%;
    max-width:450px;
    padding:36px;
    background:rgba(255,255,255,.95);
    backdrop-filter:blur(12px);
    border-radius:18px;
    box-shadow:0 24px 60px rgba(0,0,0,.25);
    border:1px solid rgba(255,255,255,.3);
}

.logo{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:12px;
    margin-bottom:10px;
}

.logo-icon{
    width:48px;
    height:48px;
    background:#c8963e;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 8px 20px rgba(200,150,62,.25);
}

.logo-icon svg{
    width:24px;
    height:24px;
    fill:#fff;
}

.logo-text{
    font-size:30px;
    font-weight:700;
    color:#0b1f3a;
}

.subtitle{
    text-align:center;
    font-size:14px;
    color:#5a6a80;
    margin-bottom:26px;
}

.badge{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    background:#f7f4ef;
    color:#c8963e;
    font-size:12px;
    font-weight:700;
    padding:8px 14px;
    border-radius:20px;
    border:1px solid #ede8e0;
    margin:0 auto 24px;
    width:fit-content;
}

label{
    display:block;
    font-size:13px;
    font-weight:600;
    color:#1a2535;
    margin-bottom:7px;
}

input{
    width:100%;
    padding:12px 14px;
    background:#f7f4ef;
    border-radius:10px;
    border:1px solid #ede8e0;
    font-size:15px;
    color:#1a2535;
    margin-bottom:18px;
    transition:.2s ease;
}

input:focus{
    outline:none;
    border-color:#1e4175;
    box-shadow:0 0 0 3px rgba(30,65,117,.15);
    background:white;
}

.btn{
    width:100%;
    padding:12px;
    background:#c8963e;
    border:none;
    border-radius:10px;
    color:white;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
    transition:all .25s ease;
}

.btn:hover{
    background:#e0b96a;
    transform:translateY(-2px);
    box-shadow:0 10px 25px rgba(200,150,62,.3);
}

.error{
    background:rgba(200,62,62,.1);
    color:#b42318;
    padding:12px;
    border-radius:10px;
    font-size:14px;
    margin-bottom:18px;
    text-align:center;
    border:1px solid rgba(180,35,24,.2);
}

.back{
    text-align:center;
    margin-top:18px;
    font-size:14px;
    color:#5a6a80;
}

.back a{
    color:#c8963e;
    text-decoration:none;
    font-weight:500;
}

.back a:hover{
    text-decoration:underline;
}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></div>
    <span class="logo-text">Orderly</span>
  </div>
  <p class="subtitle">Vendor Staff &amp; Admin Portal</p>
  <div class="badge">Staff &amp; Admin Access Only</div>

  <?php if ($error): ?>
  <div class="error"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <label>Email Address</label>
    <input type="email" name="email" placeholder="staff/admin@gmail.com" required autofocus/>
    <label>Password</label>
    <input type="password" name="password" placeholder="Enter your password" required/>
    <div style="text-align:center;margin-top:-10px;margin-bottom:18px;">
      <a href="forgot_password.php" style="font-size:13px;color:#c8963e;text-decoration:none;font-weight:500;">Forgot password?</a>
    </div>
    <button class="btn" type="submit">Sign In to Dashboard</button>
  </form>
  <p class="back"><a href="signup_login.php">Customer Login</a></p>
</div>
</body>
</html>