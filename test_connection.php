<?php
// ==============================================
// File: test_connection.php
// Letakkan di root folder (sejajar dengan index.php)
// Fungsi: Testing koneksi database dan struktur tabel
// Akses: http://localhost/greenhouse/test_connection.php
// ==============================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Greenhouse - Test Connection</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            font-size: 32px;
        }
        .test-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        h3 {
            margin-bottom: 15px;
            font-size: 18px;
        }
        pre {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin: 5px 5px 5px 0;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-info { background: #17a2b8; color: white; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background: #f1f3f5;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Greenhouse System - Diagnostic Test</h1>";

// ========================================
// 1. TEST FILE STRUCTURE
// ========================================
echo "<div class='test-section'>";
echo "<h3>📁 Test 1: Struktur File & Folder</h3>";

$requiredFiles = [
    'config/db.php' => 'Konfigurasi Database',
    'api/get_data.php' => 'API Get Data',
    'api/update_actuator.php' => 'API Update Actuator',
    'api/insert_esp1.php' => 'API Insert ESP1',
    'api/insert_esp2.php' => 'API Insert ESP2',
    'api/clear_logs.php' => 'API Clear Logs',
    'index.php' => 'Dashboard'
];

$allFilesExist = true;

echo "<table>";
echo "<tr><th>File</th><th>Deskripsi</th><th>Status</th></tr>";

foreach ($requiredFiles as $file => $desc) {
    $exists = file_exists($file);
    $status = $exists ? "<span class='badge badge-success'>✓ OK</span>" : "<span class='badge badge-danger'>✗ Missing</span>";
    echo "<tr><td><code>$file</code></td><td>$desc</td><td>$status</td></tr>";
    if (!$exists) $allFilesExist = false;
}

echo "</table>";

if ($allFilesExist) {
    echo "<p style='margin-top:15px;color:#28a745;font-weight:600;'>✅ Semua file ditemukan!</p>";
} else {
    echo "<p style='margin-top:15px;color:#dc3545;font-weight:600;'>❌ Ada file yang hilang. Silakan upload file yang kurang.</p>";
}

echo "</div>";

// ========================================
// 2. TEST DATABASE CONNECTION
// ========================================
echo "<div class='test-section'>";
echo "<h3>🔌 Test 2: Koneksi Database</h3>";

if (!file_exists('config/db.php')) {
    echo "<div class='error'>";
    echo "<p style='font-weight:600;'>❌ File config/db.php tidak ditemukan!</p>";
    echo "<p>Buat file <code>config/db.php</code> dengan isi:</p>";
    echo "<pre>&lt;?php
\$DB_HOST = 'localhost';
\$DB_USER = 'root';
\$DB_PASS = '';
\$DB_NAME = 'greenhouse';

\$conn = new mysqli(\$DB_HOST, \$DB_USER, \$DB_PASS, \$DB_NAME);
if (\$conn->connect_error) {
    die('Connection failed: ' . \$conn->connect_error);
}
\$conn->set_charset('utf8mb4');
?&gt;</pre>";
    echo "</div>";
} else {
    require_once 'config/db.php';
    
    if ($conn->connect_error) {
        echo "<div class='error'>";
        echo "<p style='font-weight:600;'>❌ Koneksi database gagal!</p>";
        echo "<p><strong>Error:</strong> " . $conn->connect_error . "</p>";
        echo "<p><strong>Solusi:</strong></p>";
        echo "<ul>";
        echo "<li>Pastikan MySQL sudah berjalan</li>";
        echo "<li>Cek username dan password di config/db.php</li>";
        echo "<li>Pastikan database 'greenhouse' sudah dibuat</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<p style='font-weight:600;'>✅ Koneksi database berhasil!</p>";
        echo "<p><strong>Host:</strong> " . $DB_HOST . "</p>";
        echo "<p><strong>Database:</strong> " . $DB_NAME . "</p>";
        echo "<p><strong>Character Set:</strong> " . $conn->character_set_name() . "</p>";
        echo "</div>";
        
        // ========================================
        // 3. TEST TABLES
        // ========================================
        echo "</div><div class='test-section'>";
        echo "<h3>🗄️ Test 3: Tabel Database</h3>";
        
        $requiredTables = [
            'sensor_suhu_kelembapan',
            'sensor_soil',
            'sensor_ldr',
            'actuator',
            'log_aktivitas'
        ];
        
        $allTablesExist = true;
        
        echo "<table>";
        echo "<tr><th>Tabel</th><th>Status</th><th>Jumlah Data</th></tr>";
        
        foreach ($requiredTables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            $exists = $result && $result->num_rows > 0;
            
            if ($exists) {
                $count = $conn->query("SELECT COUNT(*) as total FROM $table")->fetch_assoc()['total'];
                $status = "<span class='badge badge-success'>✓ OK</span>";
                $dataCount = "$count rows";
            } else {
                $status = "<span class='badge badge-danger'>✗ Missing</span>";
                $dataCount = "N/A";
                $allTablesExist = false;
            }
            
            echo "<tr><td><code>$table</code></td><td>$status</td><td>$dataCount</td></tr>";
        }
        
        echo "</table>";
        
        if (!$allTablesExist) {
            echo "<div class='error' style='margin-top:20px;'>";
            echo "<p style='font-weight:600;'>❌ Tabel belum lengkap!</p>";
            echo "<p><strong>Solusi:</strong> Import file SQL schema ke database.</p>";
            echo "<p>Gunakan phpMyAdmin atau command line:</p>";
            echo "<pre>mysql -u root -p greenhouse < greenhouse_schema.sql</pre>";
            echo "</div>";
        } else {
            echo "<p style='margin-top:15px;color:#28a745;font-weight:600;'>✅ Semua tabel tersedia!</p>";
        }
        
        // ========================================
        // 4. TEST API ENDPOINTS
        // ========================================
        echo "</div><div class='test-section'>";
        echo "<h3>🌐 Test 4: API Endpoints</h3>";
        
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['REQUEST_URI']);
        
        $endpoints = [
            'api/get_data.php' => 'GET',
            'api/update_actuator.php' => 'POST',
            'api/insert_esp1.php' => 'POST',
            'api/insert_esp2.php' => 'POST',
            'api/clear_logs.php' => 'GET'
        ];
        
        echo "<table>";
        echo "<tr><th>Endpoint</th><th>Method</th><th>URL</th></tr>";
        
        foreach ($endpoints as $endpoint => $method) {
            $url = $baseUrl . '/' . $endpoint;
            $fileExists = file_exists($endpoint);
            $badge = $fileExists ? "<span class='badge badge-success'>✓</span>" : "<span class='badge badge-danger'>✗</span>";
            
            echo "<tr>";
            echo "<td>$badge <code>$endpoint</code></td>";
            echo "<td><span class='badge badge-info'>$method</span></td>";
            echo "<td style='font-size:11px;'>$url</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // ========================================
        // 5. TEST GET DATA API
        // ========================================
        echo "</div><div class='test-section'>";
        echo "<h3>🔍 Test 5: Test API get_data.php</h3>";
        
        try {
            ob_start();
            include 'api/get_data.php';
            $apiOutput = ob_get_clean();
            
            $jsonData = json_decode($apiOutput, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['success'])) {
                if ($jsonData['success']) {
                    echo "<div class='success'>";
                    echo "<p style='font-weight:600;'>✅ API berfungsi dengan baik!</p>";
                    echo "<p><strong>Sample Response:</strong></p>";
                    echo "<pre>" . json_encode($jsonData, JSON_PRETTY_PRINT) . "</pre>";
                    echo "</div>";
                } else {
                    echo "<div class='error'>";
                    echo "<p style='font-weight:600;'>⚠️ API mengembalikan error</p>";
                    echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";
                    echo "</div>";
                }
            } else {
                echo "<div class='error'>";
                echo "<p style='font-weight:600;'>❌ API tidak mengembalikan JSON yang valid</p>";
                echo "<p><strong>JSON Error:</strong> " . json_last_error_msg() . "</p>";
                echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";
                echo "</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<p style='font-weight:600;'>❌ Error menjalankan API</p>";
            echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
            echo "</div>";
        }
        
        // ========================================
        // 6. SYSTEM INFO
        // ========================================
        echo "</div><div class='test-section'>";
        echo "<h3>ℹ️ Informasi Sistem</h3>";
        
        echo "<table>";
        echo "<tr><td><strong>PHP Version</strong></td><td>" . phpversion() . "</td></tr>";
        echo "<tr><td><strong>Server Software</strong></td><td>" . $_SERVER['SERVER_SOFTWARE'] . "</td></tr>";
        echo "<tr><td><strong>Document Root</strong></td><td>" . $_SERVER['DOCUMENT_ROOT'] . "</td></tr>";
        echo "<tr><td><strong>Current Directory</strong></td><td>" . getcwd() . "</td></tr>";
        echo "<tr><td><strong>MySQL Version</strong></td><td>" . $conn->server_info . "</td></tr>";
        echo "</table>";
        
        echo "</div>";
        
        $conn->close();
    }
}

echo "<a href='index.php' class='btn'>← Kembali ke Dashboard</a>";
echo "<a href='?' class='btn' style='background:#28a745;margin-left:10px;'>🔄 Refresh Test</a>";

echo "</div>
</body>
</html>";
?>