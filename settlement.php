<?php
require 'config.php';
require 'includes/header.php';

$user_id = $_SESSION['user_id'];
$group_id = intval($_GET['id'] ?? 0);

$check = $conn->prepare("SELECT g.group_name FROM expense_groups g
                         JOIN group_members gm ON gm.group_id = g.group_id
                         WHERE g.group_id = ? AND gm.user_id = ?");
$check->bind_param("ii", $group_id, $user_id);
$check->execute();
$groupInfo = $check->get_result()->fetch_assoc();
if (!$groupInfo) { die("You don't have access to this group."); }

$membersStmt = $conn->prepare("SELECT u.user_id, u.name FROM users u JOIN group_members gm ON gm.user_id = u.user_id WHERE gm.group_id = ?");
$membersStmt->bind_param("i", $group_id);
$membersStmt->execute();
$members = $membersStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$names = [];
$balances = [];
foreach ($members as $m) {
    $names[$m['user_id']] = $m['name'];
    $balances[$m['user_id']] = 0.0;
}

$paidStmt = $conn->prepare("SELECT paid_by, SUM(amount) as total_paid FROM expenses WHERE group_id = ? GROUP BY paid_by");
$paidStmt->bind_param("i", $group_id);
$paidStmt->execute();
$paidResult = $paidStmt->get_result();
while ($row = $paidResult->fetch_assoc()) {
    $balances[$row['paid_by']] += floatval($row['total_paid']);
}

$owedStmt = $conn->prepare("SELECT es.user_id, SUM(es.share_amount) as total_owed
                            FROM expense_splits es JOIN expenses e ON e.expense_id = es.expense_id
                            WHERE e.group_id = ? GROUP BY es.user_id");
$owedStmt->bind_param("i", $group_id);
$owedStmt->execute();
$owedResult = $owedStmt->get_result();
while ($row = $owedResult->fetch_assoc()) {
    $balances[$row['user_id']] -= floatval($row['total_owed']);
}

$settledStmt = $conn->prepare("SELECT paid_by, paid_to, amount FROM settlements WHERE group_id = ? AND status = 'completed'");
$settledStmt->bind_param("i", $group_id);
$settledStmt->execute();
$settledResult = $settledStmt->get_result();
while ($row = $settledResult->fetch_assoc()) {
    $balances[$row['paid_by']] += floatval($row['amount']);
    $balances[$row['paid_to']] -= floatval($row['amount']);
}

$creditors = [];
$debtors = [];
foreach ($balances as $uid => $bal) {
    $bal = round($bal, 2);
    if ($bal > 0.01) $creditors[] = ["id" => $uid, "amount" => $bal];
    elseif ($bal < -0.01) $debtors[] = ["id" => $uid, "amount" => -$bal];
}
usort($creditors, fn($a, $b) => $b['amount'] <=> $a['amount']);
usort($debtors, fn($a, $b) => $b['amount'] <=> $a['amount']);

$transactions = [];
$i = 0; $j = 0;
while ($i < count($debtors) && $j < count($creditors)) {
    $settleAmount = round(min($debtors[$i]['amount'], $creditors[$j]['amount']), 2);

    if ($settleAmount > 0.01) {
        $transactions[] = ["from" => $debtors[$i]['id'], "to" => $creditors[$j]['id'], "amount" => $settleAmount];
    }

    $debtors[$i]['amount'] -= $settleAmount;
    $creditors[$j]['amount'] -= $settleAmount;

    if ($debtors[$i]['amount'] < 0.01) $i++;
    if ($creditors[$j]['amount'] < 0.01) $j++;
}
?>

<h1><?php echo htmlspecialchars($groupInfo['group_name']); ?> — Settlement Plan</h1>
<a href="group.php?id=<?php echo $group_id; ?>" class="btn-link">← Back to group</a>

<div class="card">
  <h2>Current Balances</h2>
  <ul class="balance-list">
    <?php foreach ($balances as $uid => $bal): ?>
      <?php $bal = round($bal, 2); ?>
      <li>
        <span><?php echo htmlspecialchars($names[$uid]); ?></span>
        <span class="<?php echo $bal >= 0 ? 'positive' : 'negative'; ?>">
          <?php echo $bal >= 0 ? "is owed ₹" . number_format($bal, 2) : "owes ₹" . number_format(abs($bal), 2); ?>
        </span>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<div class="card">
  <h2>Simplified Settlement <span class="muted">(minimum transactions)</span></h2>
  <?php if (count($transactions) === 0): ?>
    <p class="muted">Everyone is settled up. Nothing to pay.</p>
  <?php else: ?>
    <ul class="settlement-list">
      <?php foreach ($transactions as $t): ?>
        <li>
          <span><strong><?php echo htmlspecialchars($names[$t['from']]); ?></strong> pays <strong><?php echo htmlspecialchars($names[$t['to']]); ?></strong></span>
          <span class="amount">₹<?php echo number_format($t['amount'], 2); ?></span>
          <button class="mark-settled" data-group="<?php echo $group_id; ?>" data-from="<?php echo $t['from']; ?>" data-to="<?php echo $t['to']; ?>" data-amount="<?php echo $t['amount']; ?>">Mark as Paid</button>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>