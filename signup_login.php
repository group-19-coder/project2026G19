<?php
session_start();

$errors = [
    'login'  => $_SESSION['login_error']  ?? '',
    'signup' => $_SESSION['signup_error'] ?? '',
];
$active_form = $_SESSION['active_form'] ?? 'login';
session_unset();

function showError($e){ return !empty($e) ? "<p class='error-message'>".htmlspecialchars($e)."</p>" : ""; }
function isActiveForm($f,$a){ return $f === $a ? "active" : ""; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Orderly</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;font-family:"Poppins",sans-serif;}
body{display:flex;justify-content:center;align-items:center;min-height:100vh;background:linear-gradient(135deg,#0b1f3a,#132d52,#1e4175);color:#1a2535;overflow:hidden;}
body::before{content:'';position:absolute;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(200,150,62,.15),transparent 70%);top:-120px;right:-120px;pointer-events:none;}
body::after{content:'';position:absolute;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(30,65,117,.6),transparent 70%);bottom:-120px;left:-120px;pointer-events:none;}
.container{margin:0 15px;position:relative;z-index:1;}
.form-box{width:100%;max-width:450px;padding:36px;background:rgba(255,255,255,.95);backdrop-filter:blur(12px);border-radius:18px;box-shadow:0 24px 60px rgba(0,0,0,.25);display:none;border:1px solid rgba(255,255,255,.3);}
.form-box.active{display:block;}
h2{font-size:32px;text-align:center;margin-bottom:20px;font-weight:700;color:#0b1f3a;}
span{color:#c8963e;}
input,select{width:100%;padding:12px 14px;background:#f7f4ef;border-radius:10px;border:1px solid #ede8e0;font-size:15px;color:#1a2535;margin-bottom:18px;transition:.2s ease;}
input:focus,select:focus{outline:none;border-color:#1e4175;box-shadow:0 0 0 3px rgba(30,65,117,.15);background:white;}
button{width:100%;padding:12px;background:#c8963e;border:none;border-radius:10px;color:white;font-size:16px;font-weight:600;cursor:pointer;transition:all .25s ease;}
button:hover{background:#e0b96a;transform:translateY(-2px);box-shadow:0 10px 25px rgba(200,150,62,.3);}
p{text-align:center;font-size:14px;margin-bottom:10px;color:#5a6a80;}
p a{color:#c8963e;text-decoration:none;font-weight:500;}
p a:hover{text-decoration:underline;}
.error-message{background:rgba(200,62,62,.1);color:#b42318;padding:12px;border-radius:10px;font-size:14px;margin-bottom:18px;text-align:center;border:1px solid rgba(180,35,24,.2);}
.vendor-link{text-align:center;margin-top:16px;padding-top:16px;border-top:1px solid #ede8e0;}
.vendor-link a{color:#1e4175;font-size:13px;font-weight:600;text-decoration:none;}
.vendor-link a:hover{text-decoration:underline;}
</style>
</head>
<body>
<div class="container">
  <div class="form-box <?= isActiveForm('login',$active_form) ?>" id="login-form">
    <form action="login_register.php" method="post">
      <h2>Log<span>in</span></h2>
      <?= showError($errors['login']) ?>
      <input type="hidden" name="action" value="login">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
      <p><a href="forgot_password.php">Forgot password?</a></p>
      <p>Don't have an account? <a href="#" onclick="showForm('signup-form')">Sign Up</a></p>
      <div class="vendor-link"><a href="vendor_login.php">Vendor Staff/ Admin Login</a></div>
    </form>
  </div>

  <div class="form-box <?= isActiveForm('signup',$active_form) ?>" id="signup-form">
    <form action="login_register.php" method="post">
      <h2>Sign<span> Up</span></h2>
      <?= showError($errors['signup']) ?>
      <input type="hidden" name="action" value="signup">
      <input type="text" name="name" placeholder="Name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <select name="role" required>
        <option value="">-- Select Role --</option>
        <option value="customer">Customer</option>
      </select>
      <button type="submit">Sign Up</button>
      <p>Already have an account? <a href="#" onclick="showForm('login-form')">Login</a></p>
    </form>
  </div>
</div>
<script>
function showForm(id){
  document.querySelectorAll('.form-box').forEach(f=>f.classList.remove('active'));
  document.getElementById(id).classList.add('active');
}

if(!document.querySelector('.form-box.active')) showForm('login-form');
</script>
</body>
</html>
