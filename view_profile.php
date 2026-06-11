<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

$email  = $_SESSION['email'];
$result = $conn->query("SELECT id, name, email, role FROM users WHERE email = '$email'");

if (!$result) die("Query error: " . $conn->error);
$user = $result->fetch_assoc();
if (!$user) die("User not found.");

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name          = trim($_POST['name'] ?? '');
    $new_email     = trim($_POST['email'] ?? '');
    $password      = $_POST['password'] ?? '';
    $confirm_pass  = $_POST['confirm_password'] ?? '';

    if (empty($name)) {
        $error = 'Name is required.';
    } elseif (strlen($name) < 2) {
        $error = 'Name must be at least 2 characters.';
    } elseif (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'A valid email is required.';
    } elseif ($new_email !== $email) {
        $check = $conn->query("SELECT email FROM users WHERE email = '$new_email' AND id != {$user['id']}");
        if ($check->num_rows > 0) $error = 'Email already in use by another account.';
    }

    if (!$error && !empty($password)) {
        if ($password !== $confirm_pass) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        }
    }

    if (!$error) {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET name='$name', email='$new_email', password='$hashed' WHERE id={$user['id']}");
        } else {
            $conn->query("UPDATE users SET name='$name', email='$new_email' WHERE id={$user['id']}");
        }
        if ($new_email !== $email) $_SESSION['email'] = $new_email;
        $_SESSION['name'] = $name;
        $success = 'Profile updated successfully!';
        $user['name']  = $name;
        $user['email'] = $new_email;
        $email = $new_email;
    }
}

$roleLabel = ucfirst(str_replace('_', ' ', $user['role']));
$backLink  = $user['role'] === 'admin' ? 'admin_page.php' : ($user['role'] === 'vendor_staff' ? 'vendor_staff_page.php' : 'recommendation.php');
$initials  = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Profile — Orderly</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
  --bg-dark:   #0b1f3a;
  --bg-mid:    #132d52;
  --bg-light:  #1e4175;

  --surface: rgba(255,255,255,0.95);
  --border: rgba(255,255,255,0.25);

  --accent:    #c8963e;
  --accent-dk: #b8862f;
  --accent-lt: #f5e7c7;

  --text:      #1a2535;
  --muted:     #6c7a8f;

  --error-bg:  rgba(200, 62, 62, 0.1);
  --error-br:  rgba(180,35,24,0.2);
  --error-tx:  #b42318;

  --ok-bg:     rgba(46, 125, 50, 0.1);
  --ok-br:     rgba(46,125,50,0.2);
  --ok-tx:     #2e7d32;

  --warn:      #c8963e;

  --radius:    14px;
  --shadow:    0 24px 60px rgba(0,0,0,0.25);
}

    body {
  background: linear-gradient(
    135deg,
    var(--bg-dark),
    var(--bg-mid),
    var(--bg-light)
  );

  font-family: 'Poppins', sans-serif;
  color: var(--text);
  min-height: 100vh;
  padding: 40px 16px 60px;
  overflow-x: hidden;
  position: relative;
}


body::before,
body::after {
  content: "";
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}

body::before {
  width: 500px;
  height: 500px;
  background: radial-gradient(
    circle,
    rgba(200,150,62,0.15),
    transparent 70%
  );
  top: -120px;
  right: -120px;
}

body::after {
  width: 400px;
  height: 400px;
  background: radial-gradient(
    circle,
    rgba(30,65,117,0.6),
    transparent 70%
  );
  bottom: -120px;
  left: -120px;
}

    
    .page-header {
      max-width: 680px;
      margin: 0 auto 28px;
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .page-header a {
      display: flex; align-items: center; gap: 6px;
      color: var(--muted); font-size: 0.88rem;
      text-decoration: none; transition: color .2s;
    }
    .page-header a:hover { color: var(--accent); }
    .page-header a svg { width: 16px; height: 16px; }
    .page-title {
  font-size: 1.45rem;
  font-weight: 700;
  color: #fff;
  margin-left: auto;
}

   
    .card {
  max-width: 680px;
  margin: 0 auto;

  background: var(--surface);
  backdrop-filter: blur(12px);

  border: 1px solid var(--border);
  border-radius: 20px;

  box-shadow: var(--shadow);

  overflow: hidden;
  animation: fadeUp .5s cubic-bezier(.16,1,.3,1) both;
}
    @keyframes fadeUp {
      from { opacity:0; transform:translateY(20px); }
      to   { opacity:1; transform:translateY(0); }
    }

    
    .banner {
  background: linear-gradient(
    135deg,
    #0b1f3a 0%,
    #1e4175 100%
  );

  padding: 32px 32px 56px;
  position: relative;
}
    .avatar {
  width: 80px;
  height: 80px;

  border-radius: 50%;

  background: rgba(200,150,62,0.25);
  border: 3px solid rgba(255,255,255,0.7);

  display: flex;
  align-items: center;
  justify-content: center;

  font-size: 2rem;
  font-weight: 700;
  color: #fff;

  position: absolute;
  bottom: -40px;
  left: 32px;

  box-shadow: 0 8px 20px rgba(200,150,62,0.35);
}
    .banner-name {
      color: rgba(255,255,255,0.95);
      font-size: 1.1rem; font-weight: 600;
    }
    .banner-role {
      display: inline-block;
      background: rgba(255,255,255,0.2);
      color: #fff;
      font-size: 0.75rem; font-weight: 500;
      padding: 3px 12px; border-radius: 20px;
      margin-top: 6px;
    }

   
    .form-body {
      padding: 60px 32px 32px;
    }

    
    .alert {
      border-radius: 8px; padding: 12px 16px;
      font-size: 0.88rem; font-weight: 500;
      margin-bottom: 24px;
      display: flex; align-items: center; gap: 10px;
    }
    .alert svg { width: 18px; height: 18px; flex-shrink: 0; }
    .alert.error   { background: var(--error-bg); border: 1px solid var(--error-br); color: var(--error-tx); }
    .alert.success { background: var(--ok-bg);    border: 1px solid var(--ok-br);    color: var(--ok-tx); }

    
    .section-title {
      font-size: 0.72rem; font-weight: 600;
      letter-spacing: .08em; text-transform: uppercase;
      color: var(--muted); margin-bottom: 16px;
      padding-bottom: 8px;
      border-bottom: 1px solid var(--border);
    }

    
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-bottom: 28px;
    }
    .form-grid .full { grid-column: 1 / -1; }

    .form-group { display: flex; flex-direction: column; gap: 6px; }
    label {
      font-size: 0.8rem; font-weight: 600;
      color: var(--text); letter-spacing: .02em;
    }
    input[type="text"],
input[type="email"],
input[type="password"] {
  width: 100%;
  background: #f7f4ef;
  border: 1px solid #ede8e0;
  border-radius: 10px;

  color: var(--text);
  font-family: 'Poppins', sans-serif;
  font-size: 0.9rem;

  padding: 11px 14px;
  outline: none;

  transition: all .2s ease;
}

input:focus {
  border-color: #1e4175;
  background: white;
  box-shadow: 0 0 0 3px rgba(30,65,117,0.15);
}

    
    .role-badge {
      display: inline-flex; align-items: center; gap: 7px;
      background: var(--accent-lt);
      border: 1.5px solid var(--border);
      border-radius: 8px;
      padding: 11px 14px;
      font-size: 0.9rem; color: var(--accent-dk); font-weight: 500;
    }
    .role-badge svg { width: 15px; height: 15px; }

   
    .pw-section {
      background: #fffdf4;
      border: 1.5px solid #fde8a0;
      border-radius: 12px;
      padding: 20px 22px;
      margin-bottom: 28px;
    }
    .pw-section .section-title {
      color: var(--warn);
      border-color: #fde8a0;
    }
    .pw-hint {
      font-size: 0.8rem; color: #b8860b;
      margin-bottom: 16px; margin-top: -8px;
    }
    .pw-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    
    .input-wrap { position: relative; }
    .input-wrap input { padding-right: 42px; }
    .toggle-pw {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      color: var(--muted); display: flex; align-items: center;
      padding: 0; transition: color .2s;
    }
    .toggle-pw:hover { color: var(--accent); }
    .toggle-pw svg { width: 17px; height: 17px; }

   
    .btn-row {
      display: flex; gap: 12px; justify-content: flex-end;
      padding-top: 8px;
    }
    .btn {
      padding: 11px 28px;
      border: none; border-radius: 8px;
      font-family: 'Poppins', sans-serif;
      font-size: 0.92rem; font-weight: 600;
      cursor: pointer; text-decoration: none;
      display: inline-flex; align-items: center; gap: 7px;
      transition: background .2s, transform .15s, box-shadow .2s;
    }
    .btn svg { width: 16px; height: 16px; }
    .btn-save {
  background: var(--accent);
  color: #fff;
  box-shadow: 0 10px 25px rgba(200,150,62,0.3);
}

.btn-save:hover {
  background: #e0b96a;
  transform: translateY(-2px);
}

.btn-back {
  background: #f7f4ef;
  color: var(--muted);
  border: 1px solid #ede8e0;
}

.btn-back:hover {
  background: #ede8e0;
  color: var(--text);
}
    @media (max-width: 520px) {
      .form-grid, .pw-grid { grid-template-columns: 1fr; }
      .form-grid .full { grid-column: 1; }
      .banner { padding: 24px 20px 52px; }
      .avatar { left: 20px; }
      .form-body { padding: 60px 20px 24px; }
      .btn-row { flex-direction: column-reverse; }
      .btn { width: 100%; justify-content: center; }
    }
  </style>
</head>
<body>

<div class="page-header">
  <a href="<?= $backLink ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Dashboard
  </a>
  <span class="page-title">My Profile</span>
</div>

<div class="card">

 
  <div class="banner">
    <div class="banner-name"><?= htmlspecialchars($user['name']) ?></div>
    <div class="banner-role"><?= $roleLabel ?></div>
    <div class="avatar"><?= $initials ?></div>
  </div>

 
  <div class="form-body">

    <?php if ($error): ?>
      <div class="alert error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="update_profile" value="1"/>

      <!-- Personal info -->
      <p class="section-title">Personal Information</p>
      <div class="form-grid">
        <div class="form-group">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required/>
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required/>
        </div>
        <div class="form-group full">
          <label>Account Role</label>
          <div class="role-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <?= $roleLabel ?>
          </div>
        </div>
      </div>

      <!-- Password -->
      <div class="pw-section">
        <p class="section-title">Change Password</p>
        <p class="pw-hint">Leave both fields blank to keep your current password.</p>
        <div class="pw-grid">
          <div class="form-group">
            <label for="password">New Password</label>
            <div class="input-wrap">
              <input type="password" id="password" name="password" placeholder="Min. 6 characters"/>
              <button type="button" class="toggle-pw" onclick="togglePw('password',this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12S5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="input-wrap">
              <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat new password"/>
              <button type="button" class="toggle-pw" onclick="togglePw('confirm_password',this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12S5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="btn-row">
        <a href="<?= $backLink ?>" class="btn btn-back">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          Cancel
        </a>
        <button type="submit" class="btn btn-save">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
          Save Changes
        </button>
      </div>

    </form>
  </div>
</div>

<script>
  function togglePw(id, btn) {
    const inp = document.getElementById(id);
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    btn.innerHTML = show
      ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
      : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12S5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>`;
  }
</script>

</body>
</html>