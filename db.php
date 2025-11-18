<?php
/**
 * =====================================================
 * File: config/db.php
 * Lokasi: greenhouse/config/db.php
 * Fungsi: Konfigurasi koneksi database MySQL
 * =====================================================
 */

// Database Configuration
$DB_HOST = 'localhost';     // Host database (biasanya localhost)
$DB_USER = 'root';          // Username database
$DB_PASS = '';              // Password database (kosongkan jika tidak ada)
$DB_NAME = 'greenhouse';    // Nama database

// Set timezone Indonesia
date_default_timezone_set('Asia/Jakarta');

// Create connection
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Log error
    error_log("Database connection failed: " . $conn->connect_error);
    
    // Jika akses via browser (bukan command line)
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        
        // Return JSON error jika header belum dikirim
        if (!headers_sent()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Koneksi database gagal',
                'debug' => [
                    'host' => $DB_HOST,
                    'user' => $DB_USER,
                    'database' => $DB_NAME,
                    'error_message' => $conn->connect_error,
                    'solutions' => [
                        '1. Pastikan MySQL sudah berjalan',
                        '2. Cek username dan password di config/db.php',
                        '3. Pastikan database "greenhouse" sudah dibuat',
                        '4. Jalankan: CREATE DATABASE greenhouse;'
                    ]
                ]
            ], JSON_PRETTY_PRINT);
            exit;
        }
    }
    
    die("Database connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8
$conn->set_charset("utf8mb4");

// Optional: Set SQL mode (untuk compatibility)
$conn->query("SET sql_mode = ''");

// Success (silent - tidak ada output)
// Connection berhasil, variable $conn siap digunakan
?>