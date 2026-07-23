<?php
require 'config.php';
require 'captcha_helper.php';
$error = "";
$justRegistered = isset($_GET['registered']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $captchaResponse = $_POST['g-recaptcha-response'] ?? '';

    if (!verifyRecaptcha($captchaResponse)) {
        $error = "Please complete the CAPTCHA to prove you're human.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, name, password_hash FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Incorrect email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - SplitEase</title>
<link rel="stylesheet" href="css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="auth-body">
  <div class="auth-card">
    <div class="nav-brand center">Split<span>Ease</span></div>
    <h1>Welcome back</h1>
    <?php if ($justRegistered): ?><p class="msg success">Account created. Please log in.</p><?php endif; ?>
    <?php if ($error): ?><p class="msg error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <form method="POST" class="stacked-form">
      <input type="email" name="email" placeholder="Email address" required>
      <input type="password" name="password" placeholder="Password" required>
      <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars(RECAPTCHA_SITE_KEY); ?>"></div>
      <button type="submit">Log In</button>
    </form>
    <p class="muted center">New here? <a href="register.php">Create an account</a></p>
  </div>
</body>
</html>