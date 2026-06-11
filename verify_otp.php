<?php
require_once 'config.php';

if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$status = null;
if (isset($_SESSION['status'])) {
    $status = $_SESSION['status'];
    unset($_SESSION['status']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = trim($_POST['otp']);
    $stored_otp  = $_SESSION['reset_otp'];
    $otp_time    = $_SESSION['otp_time'];

    if (time() - $otp_time > 600) {
        $error = "OTP has expired. Please request a new one.";
        unset($_SESSION['reset_otp'], $_SESSION['otp_time']);
    } elseif ($entered_otp == $stored_otp) {
        $_SESSION['otp_verified'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        $error = "Invalid OTP. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Verify OTP — Orderly</title>
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

  --success: #1e7e4a;
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
  content: "";
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
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
  margin-bottom: 18px;
  line-height: 1.6;
}


.email-badge {
  display: inline-block;
  background: rgba(200,150,62,0.12);
  color: var(--gold);
  font-size: 0.82rem;
  font-weight: 600;

  padding: 5px 14px;
  border-radius: 999px;

  margin-bottom: 24px;
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

.alert.success {
  background: rgba(30,126,74,0.08);
  border: 1px solid rgba(30,126,74,0.2);
  color: var(--success);
}


.otp-wrap {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin-bottom: 6px;
}

.otp-wrap input {
  width: 48px;
  height: 56px;

  text-align: center;
  font-size: 1.4rem;
  font-weight: 700;

  background: var(--cream);
  border: 1px solid var(--cream-dk);
  border-radius: 12px;

  color: var(--text);

  outline: none;
  transition: 0.2s ease;
}

.otp-wrap input:focus {
  border-color: var(--gold);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(200,150,62,0.15);
  transform: translateY(-2px);
}


#otp {
  display: none;
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


.timer {
  text-align: center;
  font-size: 0.82rem;
  color: var(--muted);
  margin-top: 14px;
}

.timer span {
  color: var(--gold);
  font-weight: 600;
}


.links {
  text-align: center;
  margin-top: 18px;
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
  <div class="logo"><h1>Orderly</h1><p>OTP Verification</p></div>
  <div class="icon-wrap">
    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.35 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.63a16 16 0 0 0 6 6l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
  </div>
  <h2>Enter OTP</h2>
  <p class="subtitle">We sent a 6-digit code to</p>
  <div style="text-align:center;">
    <span class="email-badge"><?= htmlspecialchars($_SESSION['reset_email']) ?></span>
  </div>

  <?php if (isset($error)): ?>
    <div class="alert error">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <?php if ($status): ?>
    <div class="alert success">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      <?= $status ?>
    </div>
  <?php endif; ?>

  <form method="POST" onsubmit="assembleOtp()">
    <div class="form-group">
      <label style="text-align:center;display:block;">6-Digit OTP Code</label>
      <div class="otp-wrap">
        <input type="text" maxlength="1" class="otp-digit" inputmode="numeric" pattern="[0-9]"/>
        <input type="text" maxlength="1" class="otp-digit" inputmode="numeric" pattern="[0-9]"/>
        <input type="text" maxlength="1" class="otp-digit" inputmode="numeric" pattern="[0-9]"/>
        <input type="text" maxlength="1" class="otp-digit" inputmode="numeric" pattern="[0-9]"/>
        <input type="text" maxlength="1" class="otp-digit" inputmode="numeric" pattern="[0-9]"/>
        <input type="text" maxlength="1" class="otp-digit" inputmode="numeric" pattern="[0-9]"/>
      </div>
      <input type="hidden" name="otp" id="otp"/>
    </div>
    <button type="submit">Verify OTP</button>
  </form>

  <div class="timer">Code expires in <span id="countdown">10:00</span></div>
  <div class="links"><a href="forgot_password.php">Request New OTP</a></div>
</div>

<script>
  
  const digits = document.querySelectorAll('.otp-digit');
  digits.forEach((inp, i) => {
    inp.addEventListener('input', () => {
      inp.value = inp.value.replace(/[^0-9]/g, '');
      if (inp.value && i < digits.length - 1) digits[i + 1].focus();
    });
    inp.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !inp.value && i > 0) digits[i - 1].focus();
    });
    inp.addEventListener('paste', e => {
      e.preventDefault();
      const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
      [...pasted].forEach((ch, j) => { if (digits[i + j]) digits[i + j].value = ch; });
      const next = Math.min(i + pasted.length, digits.length - 1);
      digits[next].focus();
    });
  });

  function assembleOtp() {
    document.getElementById('otp').value = [...digits].map(d => d.value).join('');
  }

 
  <?php $remaining = max(0, 600 - (time() - $_SESSION['otp_time'])); ?>
  let secs = <?= $remaining ?>;
  const cd = document.getElementById('countdown');
  const timer = setInterval(() => {
    if (secs <= 0) { clearInterval(timer); cd.textContent = 'Expired'; cd.style.color = '#c0392b'; return; }
    secs--;
    const m = String(Math.floor(secs / 60)).padStart(2, '0');
    const s = String(secs % 60).padStart(2, '0');
    cd.textContent = m + ':' + s;
  }, 1000);
</script>
</body>
</html>
