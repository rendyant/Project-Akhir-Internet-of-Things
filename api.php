<?php
// api.php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'latest';

if ($mode === 'latest') {
    // Ambil latest value per sensor_type
    $types = ['temp','hum','ldr','soil'];
    $result = [];
    foreach ($types as $t) {
        $stmt = $mysqli->prepare("SELECT value, created_at FROM sensors WHERE sensor_type=? ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param('s', $t);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $result[$t] = $res ? ['value' => (float)$res['value'], 'ts' => $res['created_at']] : null;
        $stmt->close();
    }
    // actuators status
    $acts = [];
    $q = $mysqli->query("SELECT id,name,status,last_changed FROM actuators");
    while ($row = $q->fetch_assoc()) {
        $acts[$row['name']] = ['id'=> (int)$row['id'], 'status' => (int)$row['status'], 'last_changed' => $row['last_changed']];
    }
    echo json_encode(['success'=>true, 'sensors'=>$result, 'actuators'=>$acts]);
    exit;
}

if ($mode === 'history') {
    // params: type=temp/hum/ldr/soil, limit (rows)
    $type = isset($_GET['type']) ? $_GET['type'] : 'temp';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 24; // default 24 points
    $limit = max(1, min(500, $limit));
    $stmt = $mysqli->prepare("SELECT value, created_at FROM sensors WHERE sensor_type=? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param('si', $type, $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = ['value' => (float)$r['value'], 'ts' => $r['created_at']];
    }
    // reverse to chronological
    $rows = array_reverse($rows);
    echo json_encode(['success'=>true, 'type'=>$type, 'data'=>$rows]);
    exit;
}

if ($mode === 'logs') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $stmt = $mysqli->prepare("SELECT l.*, a.name FROM actuator_logs l JOIN actuators a ON l.actuator_id = a.id ORDER BY l.created_at DESC LIMIT ?");
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $logs = [];
    while ($r = $res->fetch_assoc()) {
        $logs[] = $r;
    }
    echo json_encode(['success'=>true, 'logs'=>$logs]);
    exit;
}

echo json_encode(['success'=>false, 'message'=>'invalid mode']);
