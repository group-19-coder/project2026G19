<?php
require_once 'config.php';

if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $email           = $_SESSION['reset_email'];
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);

        if ($stmt->execute()) {
            session_unset();
            session_destroy();
            header("Location: signup_login.php?reset=success");
            exit();
        } else {
            $error = "Error updating password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reset Password — Orderly</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

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

  --error: #c0392b;
}

body {
  font-family: 'Poppins', sans-serif;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;

  background: linear-gradient(135deg, #0b1f3a, #132d52, #1e4175);
  padding: 20px;
  position: relative;
  overflow: hidden;
}

body::before,
body::after {
  display: none;
}

body::before {
  width: 520px;
  height: 520px;
  background: radial-gradient(circle, rgba(200,150,62,0.15), transparent 70%);
  top: -140px;
  right: -140px;
}

body::after {
  width: 420px;
  height: 420px;
  background: radial-gradient(circle, rgba(30,65,117,0.6), transparent 70%);
  bottom: -140px;
  left: -140px;
}

.card {
  background: rgba(255,255,255,0.95);
  backdrop-filter: blur(14px);

  border-radius: 18px;
  box-shadow: 0 24px 60px rgba(0,0,0,0.25);

  padding: 40px 36px;
  width: 100%;
  max-width: 420px;

  border: 1px solid rgba(255,255,255,0.3);

  animation: fadeUp .5s ease both;
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.logo {
  text-align: center;
  margin-bottom: 28px;
}

.logo h1 {
  font-size: 28px;
  font-weight: 700;
  color: var(--navy);
}

.logo h1 span {
  color: var(--gold);
}

.logo p {
  color: var(--muted);
  font-size: 0.88rem;
  margin-top: 4px;
}

.icon-wrap {
  width: 56px;
  height: 56px;
  border-radius: 14px;

  background: rgba(200,150,62,0.12);

  display: flex;
  align-items: center;
  justify-content: center;

  margin: 0 auto 20px;
}

.icon-wrap svg {
  width: 26px;
  height: 26px;
  stroke: var(--gold);
}

h2 {
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--navy);
  text-align: center;
  margin-bottom: 6px;
}

.subtitle {
  text-align: center;
  color: var(--muted);
  font-size: 0.85rem;
  margin-bottom: 28px;
  line-height: 1.6;
}

.alert {
  border-radius: 10px;
  padding: 12px 14px;
  font-size: 0.85rem;
  font-weight: 500;

  margin-bottom: 20px;

  display: flex;
  align-items: center;
  gap: 8px;
}

.alert.error {
  background: rgba(192,57,43,0.08);
  border: 1px solid rgba(192,57,43,0.2);
  color: #b42318;
}

.form-group {
  margin-bottom: 18px;
}

label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--navy);
  margin-bottom: 7px;
}

.input-wrap {
  position: relative;
}

input[type=password] {
  width: 100%;
  padding: 12px 42px 12px 14px;

  background: var(--cream);
  border: 1px solid var(--cream-dk);
  border-radius: 10px;

  font-family: 'Poppins', sans-serif;
  font-size: 0.9rem;
  color: var(--text);

  outline: none;
  transition: 0.2s ease;
}

input[type=password]:focus {
  border-color: var(--gold);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(200,150,62,0.15);
}

.toggle-pw {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);

  background: none;
  border: none;

  cursor: pointer;
  color: var(--muted);

  display: flex;
  align-items: center;
  transition: 0.2s ease;
}

.toggle-pw:hover {
  color: var(--gold);
}

.strength-bar {
  display: flex;
  gap: 4px;
  margin-top: 8px;
}

.strength-bar span {
  flex: 1;
  height: 4px;
  border-radius: 4px;
  background: var(--cream-dk);
  transition: 0.3s ease;
}

.strength-label {
  font-size: 0.75rem;
  color: var(--muted);
  margin-top: 4px;
}

button {
  width: 100%;
  padding: 13px;

  background: var(--gold);
  border: none;
  border-radius: 10px;

  color: white;

  font-family: 'Poppins', sans-serif;
  font-size: 0.95rem;
  font-weight: 600;

  cursor: pointer;

  box-shadow: 0 8px 24px rgba(200,150,62,0.35);

  transition: all 0.25s ease;
}

button:hover {
  background: var(--gold-lt);
  transform: translateY(-2px);
  box-shadow: 0 12px 30px rgba(200,150,62,0.4);
}


.links {
  text-align: center;
  margin-top: 22px;
  font-size: 0.85rem;
  color: var(--muted);
}

.links a {
  color: var(--gold);
  text-decoration: none;
  font-weight: 500;
}

.links a:hover {
  text-decoration: underline;
}
  </style>
</head>
<body>
<div class="card">
  <div class="logo"><h1>Orderly</h1><p>Set New Password</p></div>
  <div class="icon-wrap">
    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
  </div>
  <h2>New Password</h2>
  <p class="subtitle">Choose a strong password for your account.</p>

  <?php if (isset($error)): ?>
    <div class="alert error">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label for="password">New Password</label>
      <div class="input-wrap">
        <input type="password" name="password" id="password" placeholder="Min. 6 characters" required/>
        
      </div>
      <div class="strength-bar"><span></span><span></span><span></span><span></span></div>
      <div class="strength-label" id="strengthLabel"></div>
    </div>
    <div class="form-group">
      <label for="confirm_password">Confirm Password</label>
      <div class="input-wrap">
        <input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat your password" required/>
        
      </div>
    </div>
    <button type="submit">Reset Password</button>
  </form>
  <div class="links"><a href="forgot_password.php">Start Over</a></div>
</div>
<script>
 function togglePw(id, btn) {
  const inp = document.getElementById(id);
  const isHidden = inp.type === 'password';

  inp.type = isHidden ? 'text' : 'password';

  const eyeOpen = btn.querySelector('.eye-open');
  const eyeOff = btn.querySelector('.eye-off');

  if (isHidden) {
    eyeOpen.style.display = 'none';
    eyeOff.style.display = 'block';
  } else {
    eyeOpen.style.display = 'block';
    eyeOff.style.display = 'none';
  }
}
</script>
</body>
</html>
