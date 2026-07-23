<?php
require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in."]);
    exit();
}

$group_id = intval($_POST['group_id']);
$from = intval($_POST['from']);
$to = intval($_POST['to']);
$amount = floatval($_POST['amount']);

$stmt = $conn->prepare("INSERT INTO settlements (group_id, paid_by, paid_to, amount, status, settled_at) VALUES (?, ?, ?, ?, 'completed', NOW())");
$stmt->bind_param("iiid", $group_id, $from, $to, $amount);
$stmt->execute();

echo json_encode(["success" => true, "message" => "Marked as paid."]);
?>