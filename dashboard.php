<?php
require 'config.php';
require 'includes/header.php';

$user_id = $_SESSION['user_id'];

$sql = "SELECT g.group_id, g.group_name, g.created_at
        FROM expense_groups g
        JOIN group_members gm ON gm.group_id = g.group_id
        WHERE gm.user_id = ?
        ORDER BY g.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$groups = $stmt->get_result();

$invitesStmt = $conn->prepare("SELECT gi.invite_id, eg.group_name, u.name AS inviter_name
                               FROM group_invites gi
                               JOIN expense_groups eg ON eg.group_id = gi.group_id
                               JOIN users u ON u.user_id = gi.invited_by
                               WHERE gi.invited_user_id = ? AND gi.status = 'pending'
                               ORDER BY gi.created_at DESC");
$invitesStmt->bind_param("i", $user_id);
$invitesStmt->execute();
$invites = $invitesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<h1>Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋</h1>

<?php if (count($invites) > 0): ?>
<div class="card">
  <h2>Pending Invites</h2>
  <ul class="invite-list">
    <?php foreach ($invites as $inv): ?>
      <li>
        <span><strong><?php echo htmlspecialchars($inv['inviter_name']); ?></strong> invited you to <strong><?php echo htmlspecialchars($inv['group_name']); ?></strong></span>
        <span class="invite-actions">
          <button class="respond-invite accept" data-invite="<?php echo $inv['invite_id']; ?>" data-action="accept">Accept</button>
          <button class="respond-invite decline" data-invite="<?php echo $inv['invite_id']; ?>" data-action="decline">Decline</button>
        </span>
      </li>
    <?php endforeach; ?>
  </ul>
  <p id="inviteMsg" class="msg"></p>
</div>
<?php endif; ?>

<div class="card">
  <h2>Create a new group</h2>
  <form action="create_group.php" method="POST" class="stacked-form row-form">
    <input type="text" name="group_name" placeholder="e.g. Goa Trip" required>
    <button type="submit">Create Group</button>
  </form>
</div>

<h2 class="section-heading">Your Groups</h2>
<div class="group-grid">
<?php while ($row = $groups->fetch_assoc()): ?>
  <a href="group.php?id=<?php echo $row['group_id']; ?>" class="group-card">
    <h3><?php echo htmlspecialchars($row['group_name']); ?></h3>
    <p class="muted">Created <?php echo date("d M Y", strtotime($row['created_at'])); ?></p>
  </a>
<?php endwhile; ?>
<?php if ($groups->num_rows === 0): ?>
  <p class="muted">You're not part of any group yet. Create one above to get started.</p>
<?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>