<?php
require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Not logged in."]);
    exit();
}

$group_id = intval($_POST['group_id']);
$description = trim($_POST['description']);
$amount = floatval($_POST['amount']);
$category = $_POST['category'];
$paid_by = intval($_POST['paid_by']);
$split_type = $_POST['split_type'];

if ($amount <= 0 || $description === "") {
    echo json_encode(["success" => false, "message" => "Invalid expense details."]);
    exit();
}

$membersStmt = $conn->prepare("SELECT user_id FROM group_members WHERE group_id = ?");
$membersStmt->bind_param("i", $group_id);
$membersStmt->execute();
$memberIds = array_column($membersStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'user_id');

if (!in_array($paid_by, $memberIds)) {
    echo json_encode(["success" => false, "message" => "Payer is not a member of this group."]);
    exit();
}

$splits = [];

if ($split_type === 'equal') {
    $included = $_POST['include'] ?? [];
    $included = array_map('intval', $included);
    $included = array_values(array_intersect($included, $memberIds));
    $count = count($included);

    if ($count === 0) {
        echo json_encode(["success" => false, "message" => "Select at least one member."]);
        exit();
    }

    $share = round($amount / $count, 2);
    $runningTotal = 0;
    foreach ($included as $i => $uid) {
        $isLast = ($i === $count - 1);
        $thisShare = $isLast ? round($amount - $runningTotal, 2) : $share;
        $splits[$uid] = $thisShare;
        $runningTotal += $thisShare;
    }

} elseif ($split_type === 'percentage') {
    $totalPercent = 0;
    foreach ($memberIds as $uid) {
        $p = floatval($_POST['percent_' . $uid] ?? 0);
        if ($p > 0) {
            $totalPercent += $p;
            $splits[$uid] = round($amount * ($p / 100), 2);
        }
    }
    if (round($totalPercent, 2) != 100.00) {
        echo json_encode(["success" => false, "message" => "Percentages must add up to 100%. Currently: {$totalPercent}%"]);
        exit();
    }

} elseif ($split_type === 'custom') {
    $totalCustom = 0;
    foreach ($memberIds as $uid) {
        $c = floatval($_POST['custom_' . $uid] ?? 0);
        if ($c > 0) {
            $splits[$uid] = round($c, 2);
            $totalCustom += $c;
        }
    }
    if (round($totalCustom, 2) != round($amount, 2)) {
        echo json_encode(["success" => false, "message" => "Custom amounts must add up to ₹{$amount}. Currently: ₹{$totalCustom}"]);
        exit();
    }

} else {
    echo json_encode(["success" => false, "message" => "Invalid split type."]);
    exit();
}

$stmt = $conn->prepare("INSERT INTO expenses (group_id, paid_by, amount, description, category, split_type) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iidsss", $group_id, $paid_by, $amount, $description, $category, $split_type);
$stmt->execute();
$expense_id = $stmt->insert_id;

$splitStmt = $conn->prepare("INSERT INTO expense_splits (expense_id, user_id, share_amount) VALUES (?, ?, ?)");
foreach ($splits as $uid => $share) {
    $splitStmt->bind_param("iid", $expense_id, $uid, $share);
    $splitStmt->execute();
}

echo json_encode(["success" => true, "message" => "Expense added successfully."]);
?>