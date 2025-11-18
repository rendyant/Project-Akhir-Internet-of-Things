<?php
/**
 * =====================================================
 * File: api/insert_esp1.php
 * Fungsi: Insert data dari ESP1 (DHT22 + LDR)
 * Method: POST
 * Parameters: suhu, kelembapan, ldr
 * =====================================================
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Use POST method']);
    exit;
}

$suhu = isset($_POST['suhu']) ? floatval($_POST['suhu']) : null;
$kelembapan = isset($_POST['kelembapan']) ? floatval($_POST['kelembapan']) : null;
$ldr = isset($_POST['ldr']) ? intval($_POST['ldr']) : null;

if ($suhu === null || $kelembapan === null || $ldr === null) {
    echo json_encode(['success' => false, 'msg' => 'Parameter tidak lengkap (suhu, kelembapan, ldr)']);
    exit;
}

try {
    // Insert suhu & kelembapan
    $stmt1 = $conn->prepare("INSERT INTO sensor_suhu_kelembapan (suhu, kelembapan) VALUES (?, ?)");
    $stmt1->bind_param("dd", $suhu, $kelembapan);
    $ok1 = $stmt1->execute();
    $stmt1->close();

    // Insert LDR
    $stmt2 = $conn->prepare("INSERT INTO sensor_ldr (nilai_ldr) VALUES (?)");
    $stmt2->bind_param("i", $ldr);
    $ok2 = $stmt2->execute();
    $stmt2->close();

    if ($ok1 && $ok2) {
        echo json_encode([
            'success' => true,
            'msg' => 'Data berhasil disimpan',
            'data' => [
                'suhu' => $suhu,
                'kelembapan' => $kelembapan,
                'ldr' => $ldr
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Gagal insert data']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}

$conn->close();
?>

---FILE_SEPARATOR---

<?php
/**
 * =====================================================
 * File: api/insert_esp2.php
 * Fungsi: Insert data dari ESP2 (Soil Moisture)
 * Method: POST/GET
 * Parameters: soil
 * =====================================================
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

// Support both POST and GET
$soil = null;
if (isset($_POST['soil'])) {
    $soil = floatval($_POST['soil']);
} elseif (isset($_GET['soil'])) {
    $soil = floatval($_GET['soil']);
}

if ($soil === null) {
    echo json_encode(['success' => false, 'msg' => 'Parameter soil diperlukan']);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO sensor_soil (soil) VALUES (?)");
    $stmt->bind_param("d", $soil);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        echo json_encode([
            'success' => true,
            'msg' => 'Data soil berhasil disimpan',
            'data' => ['soil' => $soil]
        ]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Gagal insert data']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}

$conn->close();
?>

---FILE_SEPARATOR---

<?php
/**
 * =====================================================
 * File: api/clear_logs.php
 * Fungsi: Hapus semua log aktivitas
 * Method: GET/POST
 * =====================================================
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

try {
    $query = "DELETE FROM log_aktivitas";
    $result = $conn->query($query);

    if ($result) {
        // Insert log bahwa log telah dibersihkan
        $conn->query("INSERT INTO log_aktivitas (detail) VALUES ('Log aktivitas dibersihkan')");
        
        echo json_encode([
            'success' => true,
            'msg' => 'Semua log berhasil dihapus'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'msg' => 'Gagal menghapus log: ' . $conn->error
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'msg' => $e->getMessage()
    ]);
}

$conn->close();
?>

---FILE_SEPARATOR---

<?php
/**
 * =====================================================
 * File: api/check_alerts.php
 * Fungsi: Cek kondisi sensor dan return alert
 * Method: GET
 * =====================================================
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

// Threshold settings
$thresholds = [
    'temp_max' => 35,
    'temp_min' => 15,
    'humidity_max' => 90,
    'humidity_min' => 40,
    'soil_min' => 30,
    'light_min' => 100
];

$alerts = [];

try {
    // Cek suhu & kelembapan
    $result = $conn->query("SELECT suhu, kelembapan FROM sensor_suhu_kelembapan ORDER BY id DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if ($row['suhu'] > $thresholds['temp_max']) {
            $alerts[] = [
                'type' => 'warning',
                'sensor' => 'temperature',
                'message' => "Suhu terlalu tinggi: {$row['suhu']}°C",
                'action' => 'Aktifkan kipas ventilasi'
            ];
        }
        
        if ($row['suhu'] < $thresholds['temp_min']) {
            $alerts[] = [
                'type' => 'warning',
                'sensor' => 'temperature',
                'message' => "Suhu terlalu rendah: {$row['suhu']}°C",
                'action' => 'Tutup ventilasi'
            ];
        }
    }

    // Cek soil moisture
    $result = $conn->query("SELECT soil FROM sensor_soil ORDER BY id DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if ($row['soil'] < $thresholds['soil_min']) {
            $alerts[] = [
                'type' => 'critical',
                'sensor' => 'soil',
                'message' => "Kelembapan tanah rendah: {$row['soil']}%",
                'action' => 'Aktifkan pompa air'
            ];
        }
    }

    // Cek cahaya
    $result = $conn->query("SELECT nilai_ldr FROM sensor_ldr ORDER BY id DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if ($row['nilai_ldr'] < $thresholds['light_min']) {
            $alerts[] = [
                'type' => 'info',
                'sensor' => 'light',
                'message' => "Cahaya kurang: {$row['nilai_ldr']}lx",
                'action' => 'Aktifkan lampu grow'
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'alerts' => $alerts,
        'count' => count($alerts),
        'thresholds' => $thresholds
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>

---FILE_SEPARATOR---

<?php
/**
 * =====================================================
 * File: api/send_notification.php
 * Fungsi: Kirim notifikasi (Telegram/Email/WhatsApp)
 * Method: POST
 * Parameters: type, message, [token, chat_id, email, phone]
 * =====================================================
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    return $result !== FALSE;
}

function sendEmail($to, $subject, $message) {
    $headers = "From: greenhouse@yourdomain.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $htmlMessage = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <div style='padding: 20px; background: #f5f5f5;'>
            <div style='background: #fff; padding: 20px; border-left: 4px solid #ef4444;'>
                <h2>🚨 Greenhouse Alert</h2>
                <p>{$message}</p>
                <p><small>Waktu: " . date('d/m/Y H:i:s') . "</small></p>
            </div>
        </div>
    </body>
    </html>";
    
    return @mail($to, $subject, $htmlMessage, $headers);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Use POST method']);
    exit;
}

$type = $_POST['type'] ?? 'telegram';
$message = $_POST['message'] ?? '';

if (empty($message)) {
    echo json_encode(['success' => false, 'msg' => 'Parameter message wajib diisi']);
    exit;
}

$result = false;

try {
    switch ($type) {
        case 'telegram':
            $token = $_POST['token'] ?? '';
            $chatId = $_POST['chat_id'] ?? '';
            
            if (empty($token) || empty($chatId)) {
                throw new Exception('Token dan Chat ID wajib diisi');
            }
            
            $result = sendTelegram($token, $chatId, $message);
            break;
            
        case 'email':
            $to = $_POST['email'] ?? '';
            $subject = $_POST['subject'] ?? 'Greenhouse Alert';
            
            if (empty($to)) {
                throw new Exception('Email address wajib diisi');
            }
            
            $result = sendEmail($to, $subject, $message);
            break;
            
        default:
            throw new Exception('Notification type tidak valid');
    }
    
    echo json_encode([
        'success' => $result,
        'msg' => $result ? 'Notifikasi berhasil dikirim' : 'Gagal mengirim notifikasi'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'msg' => $e->getMessage()
    ]);
}
?>