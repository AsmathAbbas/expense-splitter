<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $group_name = trim($_POST['group_name']);
    $user_id = $_SESSION['user_id'];

    if ($group_name) {
        $stmt = $conn->prepare("INSERT INTO expense_groups (group_name, created_by) VALUES (?, ?)");
        $stmt->bind_param("si", $group_name, $user_id);
        $stmt->execute();
        $group_id = $stmt->insert_id;

        $stmt2 = $conn->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
        $stmt2->bind_param("ii", $group_id, $user_id);
        $stmt2->execute();

        header("Location: group.php?id=" . $group_id);
        exit();
    }
}
header("Location: dashboard.php");
exit();
?>