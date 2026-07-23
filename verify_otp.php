<?php
require 'config.php';
require 'email_helper.php';

$error = "";
$success = "";

if (!isset($_SESSION['pending_email'])) {
    header("Location: register.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['resend'])) {
        $otp = strval(random_int(100000, 999999));
        $_SESSION['pending_otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + 600;
        $emailSent = sendOtpEmail($_SESSION['pending_email'], $_SESSION['pending_name'], $otp);
        $success = $emailSent ? "A new code has been sent to your email." : "";
        if (!$emailSent) {
            $error = "Couldn't resend the email. Check your SendGrid setup in config.php.";
        }

    } else {
        $enteredOtp = trim($_POST['otp'] ?? '');

        if (time() > $_SESSION['otp_expiry']) {
            $error = "This code has expired. Please request a new one.";
        } elseif ($enteredOtp !== $_SESSION['pending_otp']) {
            $error = "Incorrect code. Please try again.";
        } else {
            $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $checkStmt->bind_param("s", $_SESSION['pending_email']);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $error = "This email was just registered by someone else. Please start again.";
            } else {
                $insert = $conn->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
                $insert->bind_param(
                    "sss",
                    $_SESSION['pending_name'],
                    $_SESSION['pending_email'],
                    $_SESSION['pending_password_hash']
                );
                $insert->execute();

                unset($_SESSION['pending_name']);
                unset($_SESSION['pending_email']);
                unset($_SESSION['pending_password_hash']);
                unset($_SESSION['pending_otp']);
                unset($_SESSION['otp_expiry']);

                header("Location: login.php?registered=1");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Email - SplitEase</title>
<link rel="stylesheet" href="css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="auth-body">
  <div class="auth-card">
    <div class="nav-brand center">Split<span>Ease</span></div>
    <h1>Check your email</h1>
    <p class="muted center">We sent a 6-digit code to <?php echo htmlspecialchars($_SESSION['pending_email']); ?></p>
    <?php if ($error): ?><p class="msg error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <?php if ($success): ?><p class="msg success"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>
    <form method="POST" class="stacked-form">
      <input type="text" name="otp" placeholder="Enter 6-digit code" required maxlength="6" pattern="[0-9]{6}">
      <button type="submit">Verify & Create Account</button>
    </form>
    <form method="POST" class="stacked-form">
      <button type="submit" name="resend" value="1" class="link-button">Resend code</button>
    </form>
  </div>
</body>
</html>