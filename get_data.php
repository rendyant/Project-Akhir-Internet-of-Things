<?php
/**
 * =====================================================
 * File: api/get_data.php
 * Lokasi: greenhouse/api/get_data.php
 * Fungsi: API untuk mengambil semua data sensor, actuator, dan logs
 * Method: GET
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
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Function untuk response JSON
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// Cek apakah file config ada
$configPath = __DIR__ . '/../config/db.php';
if (!file_exists($configPath)) {
    sendResponse([
        'success' => false,
        'error' => 'File konfigurasi database tidak ditemukan',
        'debug' => [
            'config_path' => $configPath,
            'current_dir' => __DIR__,
            'solution' => 'Buat file config/db.php dengan konfigurasi database'
        ]
    ], 500);
}

// Include config database
require_once $configPath;

// Cek koneksi database
if (!isset($conn) || $conn->connect_error) {
    sendResponse([
        'success' => false,
        'error' => 'Koneksi database gagal',
        'debug' => [
            'error' => isset($conn) ? $conn->connect_error : 'Variable $conn tidak tersedia',
            'solution' => 'Periksa konfigurasi database di config/db.php'
        ]
    ], 500);
}

try {
    // ==========================================
    // AMBIL DATA SENSOR TERBARU
    // ==========================================
    $latest = [
        'temperature' => null,
        'humidity' => null,
        'soil' => null,
        'light' => null,
        'ts' => null
    ];

    // Suhu & Kelembapan (DHT)
    $query = "SELECT suhu, kelembapan, waktu FROM sensor_suhu_kelembapan ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result === false) {
        throw new Exception("Query error on sensor_suhu_kelembapan: " . $conn->error);
    }
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $latest['temperature'] = round(floatval($row['suhu']), 1);
        $latest['humidity'] = round(floatval($row['kelembapan']), 1);
        $latest['ts'] = $row['waktu'];
    }

    // Kelembapan Tanah
    $query = "SELECT soil, waktu FROM sensor_soil ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $latest['soil'] = round(floatval($row['soil']), 1);
    }

    // Intensitas Cahaya
    $query = "SELECT nilai_ldr, waktu FROM sensor_ldr ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $latest['light'] = intval($row['nilai_ldr']);
    }

    // ==========================================
    // AMBIL HISTORY DATA (24 TITIK TERAKHIR)
    // ==========================================
    $history = [
        'temp' => [],
        'hum' => [],
        'soil' => [],
        'light' => []
    ];

    // Temperature History
    $query = "SELECT suhu AS value, waktu AS ts FROM sensor_suhu_kelembapan ORDER BY id DESC LIMIT 24";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $history['temp'][] = [
                'ts' => $row['ts'],
                'value' => round(floatval($row['value']), 1)
            ];
        }
    }

    // Humidity History
    $query = "SELECT kelembapan AS value, waktu AS ts FROM sensor_suhu_kelembapan ORDER BY id DESC LIMIT 24";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $history['hum'][] = [
                'ts' => $row['ts'],
                'value' => round(floatval($row['value']), 1)
            ];
        }
    }

    // Soil Moisture History
    $query = "SELECT soil AS value, waktu AS ts FROM sensor_soil ORDER BY id DESC LIMIT 24";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $history['soil'][] = [
                'ts' => $row['ts'],
                'value' => round(floatval($row['value']), 1)
            ];
        }
    }

    // Light Intensity History
    $query = "SELECT nilai_ldr AS value, waktu AS ts FROM sensor_ldr ORDER BY id DESC LIMIT 24";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $history['light'][] = [
                'ts' => $row['ts'],
                'value' => intval($row['value'])
            ];
        }
    }

    // ==========================================
    // AMBIL STATUS ACTUATOR
    // ==========================================
    $actuators = [];
    $query = "SELECT code, name, status FROM actuator ORDER BY id";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $actuators[$row['code']] = [
                'name' => $row['name'],
                'status' => intval($row['status'])
            ];
        }
    }

    // Jika actuator kosong, set default
    if (empty($actuators)) {
        $actuators = [
            'pump' => ['name' => 'Pompa Air', 'status' => 0],
            'fan' => ['name' => 'Kipas Ventilasi', 'status' => 0],
            'light' => ['name' => 'Lampu Grow', 'status' => 0]
        ];
    }

    // ==========================================
    // AMBIL LOG AKTIVITAS (10 TERAKHIR)
    // ==========================================
    $logs = [];
    $query = "SELECT detail, created_at FROM log_aktivitas ORDER BY id DESC LIMIT 10";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = [
                'detail' => $row['detail'],
                'created_at' => $row['created_at']
            ];
        }
    }

    // ==========================================
    // KIRIM RESPONSE SUCCESS
    // ==========================================
    sendResponse([
        'success' => true,
        'latest' => $latest,
        'history' => $history,
        'actuators' => $actuators,
        'logs' => $logs,
        'meta' => [
            'server_time' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
            'data_count' => [
                'temp' => count($history['temp']),
                'hum' => count($history['hum']),
                'soil' => count($history['soil']),
                'light' => count($history['light']),
                'logs' => count($logs)
            ]
        ]
    ], 200);

} catch (Exception $e) {
    // Error handling
    sendResponse([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ], 500);
} finally {
    // Tutup koneksi database
    if (isset($conn)) {
        $conn->close();
    }
}
?>