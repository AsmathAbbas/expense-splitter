<?php
// Any page that includes this file is a "protected" page:
// if there's no logged-in session, bounce to login.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SplitEase</title>
<link rel="stylesheet" href="css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<nav class="navbar">
  <div class="nav-brand">Split<span>Ease</span></div>
  <div class="nav-links">
    <a href="dashboard.php">Dashboard</a>
    <a href="profile.php">Profile</a>
    <a href="logout.php">Logout</a>
  </div>
</nav>
<main class="container">
