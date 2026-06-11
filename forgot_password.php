<?php
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $otp = rand(100000, 999999);
        $_SESSION['reset_otp']   = $otp;
        $_SESSION['reset_email'] = $email;
        $_SESSION['otp_time']    = time();

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUsername;
            $mail->Password   = $smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtpPort;

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ]
            ];

            $mail->setFrom($smtpUsername, $smtpFromName);
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP - Orderly';
            $mail->Body    = "
                <div style='font-family:Poppins,sans-serif;max-width:480px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);'>
                    <div style='background:linear-gradient(135deg,#7494ec,#5a7bd8);padding:32px;text-align:center;'>
                        <h1 style='color:#fff;margin:0;font-size:24px;'>Orderly</h1>
                        <p style='color:rgba(255,255,255,0.85);margin:6px 0 0;font-size:14px;'>Password Reset Request</p>
                    </div>
                    <div style='padding:32px;'>
                        <p style='color:#333;font-size:15px;margin-bottom:24px;'>Hi there! Use the OTP below to reset your password. It expires in <strong>10 minutes</strong>.</p>
                        <div style='background:#eef1fc;border-radius:10px;padding:24px;text-align:center;margin-bottom:24px;'>
                            <p style='margin:0;color:#7494ec;font-size:13px;letter-spacing:.05em;text-transform:uppercase;font-weight:600;'>Your OTP Code</p>
                            <p style='margin:10px 0 0;font-size:42px;font-weight:700;color:#2c2f3a;letter-spacing:10px;'>$otp</p>
                        </div>
                        <p style='color:#888;font-size:13px;'>If you didn't request this, you can safely ignore this email.</p>
                    </div>
                    <div style='background:#f5f6fa;padding:16px;text-align:center;'>
                        <p style='margin:0;color:#aaa;font-size:12px;'>© Orderly Support</p>
                    </div>
                </div>
            ";

            $mail->send();
            $_SESSION['status'] = "OTP sent to <strong>$email</strong>. Check your inbox (and spam folder).";
            header("Location: verify_otp.php");
            exit();
        } catch (Exception $e) {
            $error = "Could not send email. Error: {$mail->ErrorInfo}";
        }
    } else {
        $error = "No account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Forgot Password — Orderly</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <style> *, *::before, *::after {
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

input[type=email] {
  width: 100%;
  padding: 12px 14px;

  background: var(--cream);
  border: 1px solid var(--cream-dk);
  border-radius: 10px;

  font-family: 'Poppins', sans-serif;
  font-size: 0.9rem;
  color: var(--text);

  outline: none;
  transition: 0.2s ease;
}

input[type=email]:focus {
  border-color: var(--gold);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(200,150,62,0.15);
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
  transition: all 0.25s ease;

  box-shadow: 0 8px 24px rgba(200,150,62,0.35);
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
  <div class="logo"><h1>Orderly</h1><p>Password Recovery</p></div>
  <div class="icon-wrap">
    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
  </div>
  <h2>Forgot Password?</h2>
  <p class="subtitle">Enter your registered email and we'll send you a 6-digit OTP to reset your password.</p>

  <?php if (isset($error)): ?>
    <div class="alert error">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label for="email">Email Address</label>
      <input type="email" name="email" id="email" placeholder="you@example.com" required/>
    </div>
    <button type="submit">Send OTP</button>
  </form>
  <div class="links"><a href="signup_login.php">Back to Login</a></div>
</div>
</body>
</html>
