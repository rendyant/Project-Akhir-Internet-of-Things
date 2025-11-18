<?php
/**
 * =====================================================
 * File: api/update_actuator.php
 * Lokasi: greenhouse/api/update_actuator.php
 * Fungsi: API untuk update status actuator (relay ON/OFF)
 * Method: POST
 * Parameters: code (pump/fan/light), status (0/1)
 * Response: JSON
 * =====================================================
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Function untuk response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// Hanya terima POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse([
        'success' => false,
        'error' => 'Method not allowed. Use POST method.'
    ], 405);
}

// Load config
$configPath = __DIR__ . '/../config/db.php';
if (!file_exists($configPath)) {
    sendResponse([
        'success' => false,
        'error' => 'File konfigurasi tidak ditemukan'
    ], 500);
}

require_once $configPath;

// Cek koneksi
if (!isset($conn) || $conn->connect_error) {
    sendResponse([
        'success' => false,
        'error' => 'Koneksi database gagal'
    ], 500);
}

try {
    // Ambil parameter
    $code = isset($_POST['code']) ? trim($_POST['code']) : null;
    $status = isset($_POST['status']) ? intval($_POST['status']) : null;

    // Validasi input
    if (empty($code)) {
        sendResponse([
            'success' => false,
            'error' => 'Parameter "code" wajib diisi'
        ], 400);
    }

    if ($status !== 0 && $status !== 1) {
        sendResponse([
            'success' => false,
            'error' => 'Parameter "status" harus 0 atau 1'
        ], 400);
    }

    // Validasi code actuator
    $validCodes = ['pump', 'fan', 'light'];
    if (!in_array($code, $validCodes)) {
        sendResponse([
            'success' => false,
            'error' => 'Code actuator tidak valid. Gunakan: pump, fan, atau light'
        ], 400);
    }

    // Update status actuator
    $stmt = $conn->prepare("UPDATE actuator SET status = ?, updated_at = NOW() WHERE code = ?");
    
    if (!$stmt) {
        throw new Exception("Prepare statement error: " . $conn->error);
    }

    $stmt->bind_param("is", $status, $code);
    $executed = $stmt->execute();
    
    if (!$executed) {
        throw new Exception("Execute error: " . $stmt->error);
    }

    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    // Jika tidak ada row yang di-update, insert data baru
    if ($affectedRows === 0) {
        $actuatorNames = [
            'pump' => 'Pompa Air',
            'fan' => 'Kipas Ventilasi',
            'light' => 'Lampu Grow'
        ];
        
        $name = $actuatorNames[$code];
        $stmt = $conn->prepare("INSERT INTO actuator (code, name, status) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $code, $name, $status);
        $stmt->execute();
        $stmt->close();
    }

    // Catat ke log aktivitas
    $statusText = $status ? 'ON' : 'OFF';
    $actuatorNames = [
        'pump' => 'Pompa Air',
        'fan' => 'Kipas Ventilasi',
        'light' => 'Lampu Grow'
    ];
    $actuatorName = $actuatorNames[$code];
    
    $detail = sprintf("%s diubah menjadi %s", $actuatorName, $statusText);
    $stmt = $conn->prepare("INSERT INTO log_aktivitas (detail) VALUES (?)");
    $stmt->bind_param("s", $detail);
    $stmt->execute();
    $stmt->close();

    // Response sukses
    sendResponse([
        'success' => true,
        'message' => "Status {$actuatorName} berhasil diubah menjadi {$statusText}",
        'data' => [
            'code' => $code,
            'status' => $status,
            'status_text' => $statusText,
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ], 200);

} catch (Exception $e) {
    sendResponse([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>