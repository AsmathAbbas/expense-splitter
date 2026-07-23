<?php
require 'config.php';
require 'includes/header.php';

$user_id = $_SESSION['user_id'];

$userStmt = $conn->prepare("SELECT name, email, created_at FROM users WHERE user_id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userInfo = $userStmt->get_result()->fetch_assoc();

$paidStmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) as total FROM expenses WHERE paid_by = ?");
$paidStmt->bind_param("i", $user_id);
$paidStmt->execute();
$totalPaid = floatval($paidStmt->get_result()->fetch_assoc()['total']);

$owedStmt = $conn->prepare("SELECT COALESCE(SUM(share_amount),0) as total FROM expense_splits WHERE user_id = ?");
$owedStmt->bind_param("i", $user_id);
$owedStmt->execute();
$totalOwed = floatval($owedStmt->get_result()->fetch_assoc()['total']);

$net = $totalPaid - $totalOwed;
?>

<h1>Your Profile</h1>
<div class="card">
  <p><strong>Name:</strong> <?php echo htmlspecialchars($userInfo['name']); ?></p>
  <p><strong>Email:</strong> <?php echo htmlspecialchars($userInfo['email']); ?></p>
  <p><strong>Member since:</strong> <?php echo date("d M Y", strtotime($userInfo['created_at'])); ?></p>
</div>

<div class="card stats-grid">
  <div class="stat">
    <span class="stat-label">Total Paid</span>
    <span class="stat-value">₹<?php echo number_format($totalPaid, 2); ?></span>
  </div>
  <div class="stat">
    <span class="stat-label">Total Share</span>
    <span class="stat-value">₹<?php echo number_format($totalOwed, 2); ?></span>
  </div>
  <div class="stat">
    <span class="stat-label">Net Balance</span>
    <span class="stat-value <?php echo $net >= 0 ? 'positive' : 'negative'; ?>">
      ₹<?php echo number_format(abs($net), 2); ?> <?php echo $net >= 0 ? '(you are owed)' : '(you owe)'; ?>
    </span>
  </div>
</div>

<?php require 'includes/footer.php'; ?>