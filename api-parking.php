<?php
require_once 'config/database.php';
header('Content-Type: application/json');

// Kapag ang Arduino o ang Serial node ay nagpadala ng POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $incomingSlots = isset($_POST['slots']) ? intval($_POST['slots']) : null;
    
    if ($incomingSlots !== null && $incomingSlots >= 0 && $incomingSlots <= 4) {
        $stmt = $pdo->prepare("UPDATE parking_status SET slots_available = ? WHERE id = 1");
        $stmt->execute([$incomingSlots]);
        echo json_encode(["status" => "success", "msg" => "Database node sync accepted"]);
        exit;
    }
    echo json_encode(["status" => "error", "msg" => "Invalid constraints array data payload"]);
    exit;
}

// Kapag binabasa ng Dashboard (GET Request)
$stmt = $pdo->query("SELECT slots_available, max_slots, DATE_FORMAT(updated_at, '%r') as updated_at FROM parking_status WHERE id = 1");
$status = $stmt->fetch();

echo json_encode($status ? $status : ["slots_available" => 4, "max_slots" => 4, "updated_at" => "00:00:00"]);
exit;