<?php
require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in."]);
    exit();
}

$group_id = intval($_POST['group_id']);
$email = trim($_POST['email']);
$invited_by = $_SESSION['user_id'];

$userStmt = $conn->prepare("SELECT user_id, name FROM users WHERE email = ?");
$userStmt->bind_param("s", $email);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(["success" => false, "message" => "No account found with that email. They need to register first."]);
    exit();
}

$checkStmt = $conn->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
$checkStmt->bind_param("ii", $group_id, $user['user_id']);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    echo json_encode(["success" => false, "message" => $user['name'] . " is already in this group."]);
    exit();
}

$invite = $conn->prepare("INSERT INTO group_invites (group_id, invited_user_id, invited_by, status)
                          VALUES (?, ?, ?, 'pending')
                          ON DUPLICATE KEY UPDATE status = 'pending', invited_by = VALUES(invited_by), created_at = CURRENT_TIMESTAMP");
$invite->bind_param("iii", $group_id, $user['user_id'], $invited_by);
$invite->execute();

echo json_encode(["success" => true, "message" => "Invite sent to " . $user['name'] . ". They'll need to accept it before joining."]);
?>
