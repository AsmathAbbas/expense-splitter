<?php
require 'config.php';
header('Content-Type: application/json');

// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/otp_errors.log');

// Log the incoming request
error_log("=== respond_invite.php called ===");
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));

if (!isset($_SESSION['user_id'])) {
    error_log("ERROR: User not logged in");
    echo json_encode(["success" => false, "message" => "Not logged in."]);
    exit();
}

$user_id = $_SESSION['user_id'];
$invite_id = intval($_POST['invite_id'] ?? 0);
$action = $_POST['action'] ?? '';

error_log("Processing: user_id=$user_id, invite_id=$invite_id, action=$action");

if ($invite_id === 0 || !in_array($action, ['accept', 'decline'])) {
    error_log("ERROR: Invalid request parameters");
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit();
}

// Verify this invite belongs to the user and is still pending
$check = $conn->prepare("SELECT group_id FROM group_invites WHERE invite_id = ? AND invited_user_id = ? AND status = 'pending'");
$check->bind_param("ii", $invite_id, $user_id);
$check->execute();
$result = $check->get_result();
$invite = $result->fetch_assoc();

if (!$invite) {
    error_log("ERROR: Invite not found or not pending for this user");
    echo json_encode(["success" => false, "message" => "This invite no longer exists or you don't have permission."]);
    exit();
}

error_log("Found invite: group_id = " . $invite['group_id']);

if ($action === 'accept') {
    // Add user to group members
    $addMember = $conn->prepare("INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)");
    $addMember->bind_param("ii", $invite['group_id'], $user_id);
    if (!$addMember->execute()) {
        error_log("ERROR adding member: " . $conn->error);
        echo json_encode(["success" => false, "message" => "Could not add you to the group: " . $conn->error]);
        exit();
    }
    error_log("Added user $user_id to group " . $invite['group_id']);

    // Update invite status
    $update = $conn->prepare("UPDATE group_invites SET status = 'accepted' WHERE invite_id = ?");
    $update->bind_param("i", $invite_id);
    if (!$update->execute()) {
        error_log("ERROR updating invite: " . $conn->error);
        echo json_encode(["success" => false, "message" => "Could not update invite status"]);
        exit();
    }
    error_log("Invite $invite_id marked as accepted");

    echo json_encode(["success" => true, "message" => "You joined the group!"]);

} elseif ($action === 'decline') {
    $update = $conn->prepare("UPDATE group_invites SET status = 'declined' WHERE invite_id = ?");
    $update->bind_param("i", $invite_id);
    if (!$update->execute()) {
        error_log("ERROR updating invite: " . $conn->error);
        echo json_encode(["success" => false, "message" => "Could not decline invite"]);
        exit();
    }
    error_log("Invite $invite_id marked as declined");

    echo json_encode(["success" => true, "message" => "Invite declined."]);
} else {
    error_log("ERROR: Unknown action: $action");
    echo json_encode(["success" => false, "message" => "Invalid action."]);
}
?>