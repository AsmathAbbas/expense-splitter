<?php
require 'config.php';
header('Content-Type: application/json');

$group_id = intval($_GET['group_id'] ?? 0);

$stmt = $conn->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE group_id = ? GROUP BY category");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = ["category" => $row['category'], "total" => floatval($row['total'])];
}

echo json_encode($data);
?>