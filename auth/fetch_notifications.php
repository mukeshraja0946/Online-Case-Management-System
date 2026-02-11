<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if action is mark_read
if (isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    if (isset($_POST['id'])) {
        $notif_id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notif_id, $user_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    echo json_encode(['success' => true]);
    exit();
}

// Fetch notifications
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
$unread_count = 0;

while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => $row['id'],
        'message' => $row['message'],
        'type' => $row['type'],
        'is_read' => (bool)$row['is_read'],
        'time' => date('d M, H:i', strtotime($row['created_at']))
    ];
    if (!$row['is_read']) {
        $unread_count++;
    }
}

echo json_encode([
    'notifications' => $notifications,
    'unread_count' => $unread_count
]);
?>
