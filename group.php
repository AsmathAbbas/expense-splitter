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

if (!$groupInfo) {
    die("You don't have access to this group.");
}

$membersStmt = $conn->prepare("SELECT u.user_id, u.name, u.email FROM users u
                                JOIN group_members gm ON gm.user_id = u.user_id
                                WHERE gm.group_id = ?");
$membersStmt->bind_param("i", $group_id);
$membersStmt->execute();
$members = $membersStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$expStmt = $conn->prepare("SELECT e.expense_id, e.amount, e.description, e.category, e.split_type, e.created_at, u.name AS payer_name
                           FROM expenses e JOIN users u ON u.user_id = e.paid_by
                           WHERE e.group_id = ? ORDER BY e.created_at DESC");
$expStmt->bind_param("i", $group_id);
$expStmt->execute();
$expenses = $expStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<h1><?php echo htmlspecialchars($groupInfo['group_name']); ?></h1>
<a href="settlement.php?id=<?php echo $group_id; ?>" class="btn-link">View Settlement Plan →</a>

<div class="grid-2">
  <div class="card">
    <h2>Members</h2>
    <ul class="member-list">
      <?php foreach ($members as $m): ?>
        <li><?php echo htmlspecialchars($m['name']); ?> <span class="muted"><?php echo htmlspecialchars($m['email']); ?></span></li>
      <?php endforeach; ?>
    </ul>
    <form id="addMemberForm" class="stacked-form">
      <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
      <input type="email" name="email" placeholder="Invite by email" required>
      <button type="submit">Send Invite</button>
    </form>
    <p id="memberMsg" class="msg"></p>
  </div>

  <div class="card">
    <h2>Spending by Category</h2>
    <canvas id="categoryChart" height="200"></canvas>
  </div>
</div>

<div class="card">
  <h2>Add an Expense</h2>
  <form id="addExpenseForm" class="expense-form">
    <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">

    <label>Description</label>
    <input type="text" name="description" required placeholder="e.g. Dinner at Cafe">

    <label>Amount (₹)</label>
    <input type="number" name="amount" step="0.01" min="0.01" required id="totalAmount">

    <label>Category</label>
    <select name="category">
      <option>Food</option>
      <option>Travel</option>
      <option>Stay</option>
      <option>Shopping</option>
      <option>General</option>
    </select>

    <label>Paid by</label>
    <select name="paid_by" required>
      <?php foreach ($members as $m): ?>
        <option value="<?php echo $m['user_id']; ?>"><?php echo htmlspecialchars($m['name']); ?></option>
      <?php endforeach; ?>
    </select>

    <label>Split type</label>
    <div class="split-toggle">
      <label><input type="radio" name="split_type" value="equal" checked> Equal</label>
      <label><input type="radio" name="split_type" value="percentage"> Percentage</label>
      <label><input type="radio" name="split_type" value="custom"> Custom Amount</label>
    </div>

    <div id="splitInputs" class="split-inputs">
      <?php foreach ($members as $m): ?>
        <div class="split-row">
          <span class="split-name"><?php echo htmlspecialchars($m['name']); ?></span>
          <label class="equal-only"><input type="checkbox" name="include[]" value="<?php echo $m['user_id']; ?>" checked> Included</label>
          <input type="number" step="0.01" class="percent-input hidden" name="percent_<?php echo $m['user_id']; ?>" placeholder="%">
          <input type="number" step="0.01" class="custom-input hidden" name="custom_<?php echo $m['user_id']; ?>" placeholder="₹ amount">
        </div>
      <?php endforeach; ?>
    </div>
    <p class="muted" id="splitHint">All included members split the amount equally.</p>

    <button type="submit">Add Expense</button>
  </form>
  <p id="expenseMsg" class="msg"></p>
</div>

<div class="card">
  <h2>Expense History</h2>
  <table class="expense-table">
    <thead><tr><th>Date</th><th>Description</th><th>Category</th><th>Paid by</th><th>Amount</th><th>Split</th></tr></thead>
    <tbody>
      <?php foreach ($expenses as $e): ?>
      <tr>
        <td><?php echo date("d M", strtotime($e['created_at'])); ?></td>
        <td><?php echo htmlspecialchars($e['description']); ?></td>
        <td><?php echo htmlspecialchars($e['category']); ?></td>
        <td><?php echo htmlspecialchars($e['payer_name']); ?></td>
        <td>₹<?php echo number_format($e['amount'], 2); ?></td>
        <td><span class="badge"><?php echo htmlspecialchars($e['split_type']); ?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php if (count($expenses) === 0): ?>
      <tr><td colspan="6" class="muted">No expenses yet. Add one above.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
  const GROUP_ID = <?php echo $group_id; ?>;
</script>

<?php require 'includes/footer.php'; ?>