<?php
// control.php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// expect POST: actuator (name) and action (on/off) and optional reason
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false, 'message'=>'Method not allowed']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['actuator']) || empty($data['action'])) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>'Invalid input']);
    exit;
}
$actuator = $data['actuator']; // e.g., 'pump'
$action = strtolower($data['action']) === 'on' ? 1 : 0;
$reason = isset($data['reason']) ? $data['reason'] : null;

// update actuators table
$stmt = $mysqli->prepare("UPDATE actuators SET status=? WHERE name=?");
$stmt->bind_param('is', $action, $actuator);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    // log
    $stmt2 = $mysqli->prepare("SELECT id FROM actuators WHERE name=? LIMIT 1");
    $stmt2->bind_param('s', $actuator);
    $stmt2->execute();
    $row = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
    $act_id = $row ? (int)$row['id'] : null;
    if ($act_id) {
        $act = $action ? 'ON' : 'OFF';
        $stmt3 = $mysqli->prepare("INSERT INTO actuator_logs (actuator_id, action, reason) VALUES (?, ?, ?)");
        $stmt3->bind_param('iss', $act_id, $act, $reason);
        $stmt3->execute();
        $stmt3->close();
    }
    // NOTE: In real deployment you'd also send command to MCU (MQTT / HTTP) to actually switch hardware.
    echo json_encode(['success'=>true, 'actuator'=>$actuator, 'status'=>$action]);
    exit;
} else {
    echo json_encode(['success'=>false, 'message'=>'DB update failed']);
    exit;
}