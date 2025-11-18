<?php
// ==============================================
// File: api/get_data.php - FIXED VERSION WITH BETTER ERROR HANDLING
// Fungsi: Mengambil data sensor, history, actuator, dan logs
// ==============================================

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan error di output
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Cek apakah file config ada
if (!file_exists(__DIR__ . '/../config/db.php')) {
    echo json_encode([
        'success' => false,
        'error' => 'File config/db.php tidak ditemukan',
        'path' => __DIR__ . '/../config/db.php'
    ]);
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Cek koneksi database
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => 'Koneksi database gagal: ' . $conn->connect_error
    ]);
    exit;
}

try {
    // --- Data sensor terbaru ---
    $latest = [
        'temperature' => null,
        'humidity' => null,
        'soil' => null,
        'light' => null,
        'ts' => null
    ];

    // Suhu & kelembapan (dari sensor DHT)
    $query = "SELECT suhu, kelembapan, waktu FROM sensor_suhu_kelembapan ORDER BY id DESC LIMIT 1";
    $res = $conn->query($query);
    
    if ($res === false) {
        throw new Exception("Query error: " . $conn->error);
    }
    
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $latest['temperature'] = floatval($row['suhu']);
        $latest['humidity'] = floatval($row['kelembapan']);
        $latest['ts'] = $row['waktu'];
    }

    // Kelembapan Tanah (Soil Moisture)
    $query = "SELECT soil, waktu FROM sensor_soil ORDER BY id DESC LIMIT 1";
    $res = $conn->query($query);
    
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $latest['soil'] = floatval($row['soil']);
    }

    // Intensitas Cahaya (LDR)
    $query = "SELECT nilai_ldr, waktu FROM sensor_ldr ORDER BY id DESC LIMIT 1";
    $res = $conn->query($query);
    
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $latest['light'] = intval($row['nilai_ldr']);
    }

    // --- History chart (ambil 24 titik terakhir per sensor) ---
    $history = ['temp'=>[], 'hum'=>[], 'soil'=>[], 'light'=>[]];

    // Suhu
    $query = "SELECT suhu AS value, waktu AS ts FROM sensor_suhu_kelembapan ORDER BY id DESC LIMIT 24";
    $res = $conn->query($query);
    if ($res) {
        while($row = $res->fetch_assoc()) {
            $history['temp'][] = ['ts'=>$row['ts'], 'value'=>floatval($row['value'])];
        }
    }

    // Kelembapan Udara
    $query = "SELECT kelembapan AS value, waktu AS ts FROM sensor_suhu_kelembapan ORDER BY id DESC LIMIT 24";
    $res = $conn->query($query);
    if ($res) {
        while($row = $res->fetch_assoc()) {
            $history['hum'][] = ['ts'=>$row['ts'], 'value'=>floatval($row['value'])];
        }
    }

    // Kelembapan Tanah
    $query = "SELECT soil AS value, waktu AS ts FROM sensor_soil ORDER BY id DESC LIMIT 24";
    $res = $conn->query($query);
    if ($res) {
        while($row = $res->fetch_assoc()) {
            $history['soil'][] = ['ts'=>$row['ts'], 'value'=>floatval($row['value'])];
        }
    }

    // Intensitas Cahaya
    $query = "SELECT nilai_ldr AS value, waktu AS ts FROM sensor_ldr ORDER BY id DESC LIMIT 24";
    $res = $conn->query($query);
    if ($res) {
        while($row = $res->fetch_assoc()) {
            $history['light'][] = ['ts'=>$row['ts'], 'value'=>intval($row['value'])];
        }
    }

    // --- Actuators (ambil semua actuator dari tabel) ---
    $actuators = [];
    $query = "SELECT code, status FROM actuator";
    $res = $conn->query($query);
    
    if ($res) {
        while($row = $res->fetch_assoc()) {
            $actuators[$row['code']] = ['status'=>intval($row['status'])];
        }
    }

    // --- Logs (ambil 10 terakhir) ---
    $logs = [];
    $query = "SELECT detail, created_at FROM log_aktivitas ORDER BY id DESC LIMIT 10";
    $res = $conn->query($query);
    
    if ($res) {
        while($row = $res->fetch_assoc()) {
            $logs[] = ['detail'=>$row['detail'], 'created_at'=>$row['created_at']];
        }
    }

    // --- Output JSON ---
    echo json_encode([
        'success' => true,
        'latest' => $latest,
        'history' => $history,
        'actuators' => $actuators,
        'logs' => $logs,
        'server_time' => date('Y-m-d H:i:s'),
        'data_count' => [
            'temp' => count($history['temp']),
            'hum' => count($history['hum']),
            'soil' => count($history['soil']),
            'light' => count($history['light']),
            'logs' => count($logs)
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

$conn->close();
?>


<?php
// ==============================================
// File: api/update_actuator.php - FIXED VERSION
// Fungsi: Update status actuator (ON/OFF)
// ==============================================

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// Ambil input dari POST
$code = isset($_POST['code']) ? trim($_POST['code']) : null;
$status = isset($_POST['status']) ? intval($_POST['status']) : null;

// Validasi input
if (empty($code) || ($status !== 0 && $status !== 1)) {
    echo json_encode([
        'success' => false,
        'msg' => 'Parameter tidak valid. Code dan status diperlukan.'
    ]);
    exit;
}

// Validasi code actuator
$validCodes = ['pump', 'fan', 'light'];
if (!in_array($code, $validCodes)) {
    echo json_encode([
        'success' => false,
        'msg' => 'Code actuator tidak valid'
    ]);
    exit;
}

try {
    // Update status actuator
    $stmt = $conn->prepare("UPDATE actuator SET status = ?, updated_at = NOW() WHERE code = ?");
    $stmt->bind_param("is", $status, $code);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        // Catat ke log aktivitas
        $statusText = $status ? 'ON' : 'OFF';
        $actuatorNames = [
            'pump' => 'Pompa Air',
            'fan' => 'Kipas Ventilasi',
            'light' => 'Lampu Grow'
        ];
        $actuatorName = $actuatorNames[$code] ?? strtoupper($code);
        
        $detail = sprintf("%s diubah menjadi %s", $actuatorName, $statusText);
        $stmt2 = $conn->prepare("INSERT INTO log_aktivitas (detail) VALUES (?)");
        $stmt2->bind_param("s", $detail);
        $stmt2->execute();
        $stmt2->close();

        echo json_encode([
            'success' => true,
            'msg' => "Status $actuatorName berhasil diubah menjadi $statusText"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'msg' => 'Gagal mengubah status: ' . $conn->error
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'msg' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>


<?php
// ==============================================
// File: api/send_notification.php - NEW FILE
// Fungsi: Mengirim notifikasi via Telegram/Email/WhatsApp
// ==============================================

header('Content-Type: application/json');

// Fungsi untuk mengirim notifikasi Telegram
function sendTelegram($token, $chatId, $message) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];
    
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    return $result !== FALSE;
}

// Fungsi untuk mengirim Email
function sendEmail($to, $subject, $message) {
    $headers = "From: greenhouse@yourdomain.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $htmlMessage = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { padding: 20px; background: #f5f5f5; }
            .alert { background: #fff; padding: 20px; border-left: 4px solid #ef4444; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='alert'>
                <h2>🚨 Greenhouse Alert</h2>
                <p>{$message}</p>
                <p><small>Waktu: " . date('d/m/Y H:i:s') . "</small></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return mail($to, $subject, $htmlMessage, $headers);
}

// Fungsi untuk mengirim WhatsApp (menggunakan Twilio atau API lainnya)
function sendWhatsApp($to, $message) {
    // Implementasi menggunakan Twilio API atau WhatsApp Business API
    // Contoh menggunakan Twilio:
    /*
    $accountSid = 'YOUR_TWILIO_ACCOUNT_SID';
    $authToken = 'YOUR_TWILIO_AUTH_TOKEN';
    $twilioNumber = 'whatsapp:+14155238886'; // Twilio WhatsApp number
    $toNumber = "whatsapp:$to";
    
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
    
    $data = [
        'From' => $twilioNumber,
        'To' => $toNumber,
        'Body' => $message
    ];
    
    // Send POST request using cURL
    */
    
    return true; // Placeholder
}

// Main execution
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'telegram';
    $message = $_POST['message'] ?? '';
    
    $result = false;
    
    switch ($type) {
        case 'telegram':
            $token = $_POST['token'] ?? '';
            $chatId = $_POST['chat_id'] ?? '';
            $result = sendTelegram($token, $chatId, $message);
            break;
            
        case 'email':
            $to = $_POST['email'] ?? '';
            $subject = $_POST['subject'] ?? 'Greenhouse Alert';
            $result = sendEmail($to, $subject, $message);
            break;
            
        case 'whatsapp':
            $to = $_POST['phone'] ?? '';
            $result = sendWhatsApp($to, $message);
            break;
    }
    
    echo json_encode([
        'success' => $result,
        'msg' => $result ? 'Notifikasi berhasil dikirim' : 'Gagal mengirim notifikasi'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'msg' => 'Method not allowed'
    ]);
}
?>


<?php
// ==============================================
// File: api/check_alerts.php - NEW FILE
// Fungsi: Cek kondisi sensor dan kirim alert otomatis
// ==============================================

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// Ambil threshold dari settings (bisa disimpan di database atau file config)
$thresholds = [
    'temp_max' => 35,      // Suhu maksimal (°C)
    'temp_min' => 15,      // Suhu minimal (°C)
    'humidity_max' => 90,  // Kelembapan udara maksimal (%)
    'humidity_min' => 40,  // Kelembapan udara minimal (%)
    'soil_min' => 30,      // Kelembapan tanah minimal (%)
    'light_min' => 100     // Intensitas cahaya minimal (lx)
];

// Ambil data sensor terbaru
$res = $conn->query("SELECT suhu, kelembapan FROM sensor_suhu_kelembapan ORDER BY id DESC LIMIT 1");
$dht = $res->fetch_assoc();

$res = $conn->query("SELECT soil FROM sensor_soil ORDER BY id DESC LIMIT 1");
$soilData = $res->fetch_assoc();

$res = $conn->query("SELECT nilai_ldr FROM sensor_ldr ORDER BY id DESC LIMIT 1");
$lightData = $res->fetch_assoc();

$alerts = [];

// Cek suhu
if ($dht && $dht['suhu'] > $thresholds['temp_max']) {
    $alerts[] = [
        'type' => 'warning',
        'sensor' => 'temperature',
        'message' => "Suhu terlalu tinggi: {$dht['suhu']}°C (Batas: {$thresholds['temp_max']}°C)",
        'action' => 'Aktifkan kipas ventilasi'
    ];
}

if ($dht && $dht['suhu'] < $thresholds['temp_min']) {
    $alerts[] = [
        'type' => 'warning',
        'sensor' => 'temperature',
        'message' => "Suhu terlalu rendah: {$dht['suhu']}°C (Minimal: {$thresholds['temp_min']}°C)",
        'action' => 'Tutup ventilasi'
    ];
}

// Cek kelembapan tanah
if ($soilData && $soilData['soil'] < $thresholds['soil_min']) {
    $alerts[] = [
        'type' => 'critical',
        'sensor' => 'soil',
        'message' => "Kelembapan tanah rendah: {$soilData['soil']}% (Minimal: {$thresholds['soil_min']}%)",
        'action' => 'Aktifkan pompa air segera'
    ];
}

// Cek intensitas cahaya
if ($lightData && $lightData['nilai_ldr'] < $thresholds['light_min']) {
    $alerts[] = [
        'type' => 'info',
        'sensor' => 'light',
        'message' => "Cahaya kurang: {$lightData['nilai_ldr']}lx (Minimal: {$thresholds['light_min']}lx)",
        'action' => 'Aktifkan lampu grow'
    ];
}

echo json_encode([
    'success' => true,
    'alerts' => $alerts,
    'count' => count($alerts),
    'thresholds' => $thresholds
], JSON_PRETTY_PRINT);

$conn->close();
?>