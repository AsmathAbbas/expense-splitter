<?php
require 'config.php';
require 'captcha_helper.php';
require 'email_helper.php';
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $captchaResponse = $_POST['g-recaptcha-response'] ?? '';

    if (!verifyRecaptcha($captchaResponse)) {
        $error = "Please complete the CAPTCHA to prove you're human.";
    } elseif ($name && $email && $password) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $otp = strval(random_int(100000, 999999));

            $_SESSION['pending_name'] = $name;
            $_SESSION['pending_email'] = $email;
            $_SESSION['pending_password_hash'] = $hash;
            $_SESSION['pending_otp'] = $otp;
            $_SESSION['otp_expiry'] = time() + 600;

            $emailSent = sendOtpEmail($email, $name, $otp);

            if ($emailSent) {
                header("Location: verify_otp.php");
                exit();
            } else {
                $error = "Couldn't send the verification email. Please check your SendGrid API key or sender email in config.php, or try again.";
            }
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - SplitEase</title>
<link rel="stylesheet" href="css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="auth-body">
  <div class="auth-card">
    <div class="nav-brand center">Split<span>Ease</span></div>
    <h1>Create your account</h1>
    <?php if ($error): ?><p class="msg error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <form method="POST" class="stacked-form">
      <input type="text" name="name" placeholder="Full name" required>
      <input type="email" name="email" placeholder="Email address" required>
      <input type="password" name="password" placeholder="Password" required minlength="6">
      <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars(RECAPTCHA_SITE_KEY); ?>"></div>
      <button type="submit">Register</button>
    </form>
    <p class="muted center">Already have an account? <a href="login.php">Log in</a></p>
  </div>
</body>
</html>