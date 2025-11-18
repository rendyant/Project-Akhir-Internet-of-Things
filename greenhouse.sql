-- ================================================
-- Database: greenhouse
-- Deskripsi: Schema database untuk Greenhouse Monitoring System
-- ================================================

CREATE DATABASE IF NOT EXISTS greenhouse;
USE greenhouse;

-- ================================================
-- Tabel: sensor_suhu_kelembapan
-- Fungsi: Menyimpan data dari sensor DHT (Suhu & Kelembapan Udara)
-- ================================================
CREATE TABLE IF NOT EXISTS sensor_suhu_kelembapan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    suhu DECIMAL(5,2) NOT NULL COMMENT 'Suhu dalam Celsius',
    kelembapan DECIMAL(5,2) NOT NULL COMMENT 'Kelembapan udara dalam persen',
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_waktu (waktu DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Data sensor DHT22/DHT11';

-- ================================================
-- Tabel: sensor_soil
-- Fungsi: Menyimpan data kelembapan tanah
-- ================================================
CREATE TABLE IF NOT EXISTS sensor_soil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soil DECIMAL(5,2) NOT NULL COMMENT 'Kelembapan tanah dalam persen',
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_waktu (waktu DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Data sensor kelembapan tanah';

-- ================================================
-- Tabel: sensor_ldr
-- Fungsi: Menyimpan data intensitas cahaya
-- ================================================
CREATE TABLE IF NOT EXISTS sensor_ldr (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nilai_ldr INT NOT NULL COMMENT 'Intensitas cahaya dalam lux',
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_waktu (waktu DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Data sensor cahaya (LDR)';

-- ================================================
-- Tabel: actuator
-- Fungsi: Menyimpan status actuator (relay control)
-- ================================================
CREATE TABLE IF NOT EXISTS actuator (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE COMMENT 'Kode actuator: pump, fan, light',
    name VARCHAR(50) NOT NULL COMMENT 'Nama actuator',
    status TINYINT(1) DEFAULT 0 COMMENT '0=OFF, 1=ON',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Status actuator (relay)';

-- Insert default actuators
INSERT INTO actuator (code, name, status) VALUES
('pump', 'Pompa Air', 0),
('fan', 'Kipas Ventilasi', 0),
('light', 'Lampu Grow', 0)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- ================================================
-- Tabel: log_aktivitas
-- Fungsi: Menyimpan log aktivitas sistem
-- ================================================
CREATE TABLE IF NOT EXISTS log_aktivitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    detail TEXT NOT NULL COMMENT 'Detail aktivitas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Log aktivitas sistem';

-- ================================================
-- Tabel: notification_settings (BARU)
-- Fungsi: Menyimpan pengaturan notifikasi
-- ================================================
CREATE TABLE IF NOT EXISTS notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('telegram', 'email', 'whatsapp') NOT NULL,
    config JSON NOT NULL COMMENT 'Konfigurasi notifikasi (token, chat_id, dll)',
    thresholds JSON COMMENT 'Batas threshold untuk alert',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Pengaturan notifikasi';

-- Insert default notification settings
INSERT INTO notification_settings (type, config, thresholds) VALUES
('telegram', 
 JSON_OBJECT('token', '', 'chat_id', ''),
 JSON_OBJECT('temp_max', 35, 'temp_min', 15, 'soil_min', 30, 'light_min', 100)
);

-- ================================================
-- Tabel: alert_history (BARU)
-- Fungsi: Menyimpan riwayat alert yang dikirim
-- ================================================
CREATE TABLE IF NOT EXISTS alert_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type VARCHAR(50) NOT NULL COMMENT 'Type: temperature, soil, light',
    message TEXT NOT NULL,
    notification_type ENUM('telegram', 'email', 'whatsapp') NOT NULL,
    status ENUM('sent', 'failed') DEFAULT 'sent',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sent_at (sent_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Riwayat notifikasi yang terkirim';

-- ================================================
-- STORED PROCEDURES
-- ================================================

-- Procedure untuk auto cleanup data lama (optional)
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS cleanup_old_data(IN days INT)
BEGIN
    -- Hapus data sensor lebih dari X hari
    DELETE FROM sensor_suhu_kelembapan WHERE waktu < DATE_SUB(NOW(), INTERVAL days DAY);
    DELETE FROM sensor_soil WHERE waktu < DATE_SUB(NOW(), INTERVAL days DAY);
    DELETE FROM sensor_ldr WHERE waktu < DATE_SUB(NOW(), INTERVAL days DAY);
    DELETE FROM log_aktivitas WHERE created_at < DATE_SUB(NOW(), INTERVAL days DAY);
    
    SELECT CONCAT('Data lebih dari ', days, ' hari berhasil dihapus') AS result;
END$$

DELIMITER ;

-- ================================================
-- VIEWS untuk kemudahan query
-- ================================================

-- View untuk data sensor terbaru
CREATE OR REPLACE VIEW v_latest_sensors AS
SELECT 
    (SELECT suhu FROM sensor_suhu_kelembapan ORDER BY id DESC LIMIT 1) AS temperature,
    (SELECT kelembapan FROM sensor_suhu_kelembapan ORDER BY id DESC LIMIT 1) AS humidity,
    (SELECT soil FROM sensor_soil ORDER BY id DESC LIMIT 1) AS soil_moisture,
    (SELECT nilai_ldr FROM sensor_ldr ORDER BY id DESC LIMIT 1) AS light_intensity,
    NOW() AS last_update;

-- View untuk status actuator
CREATE OR REPLACE VIEW v_actuator_status AS
SELECT 
    code,
    name,
    CASE WHEN status = 1 THEN 'ON' ELSE 'OFF' END AS status_text,
    status,
    updated_at
FROM actuator
ORDER BY id;

-- ================================================
-- SAMPLE DATA untuk testing (optional)
-- ================================================

-- Insert sample sensor data
INSERT INTO sensor_suhu_kelembapan (suhu, kelembapan) VALUES
(28.5, 65.2),
(29.1, 63.8),
(27.8, 68.5);

INSERT INTO sensor_soil (soil) VALUES
(45.2),
(42.8),
(48.5);

INSERT INTO sensor_ldr (nilai_ldr) VALUES
(850),
(920),
(780);

-- Insert sample log
INSERT INTO log_aktivitas (detail) VALUES
('Sistem dimulai'),
('Pompa Air diaktifkan'),
('Suhu mencapai 32°C - Alert dikirim');

-- ================================================
-- GRANTS (optional - sesuaikan dengan kebutuhan)
-- ================================================

-- Buat user khusus untuk aplikasi (lebih secure)
-- CREATE USER IF NOT EXISTS 'greenhouse_app'@'localhost' IDENTIFIED BY 'password_anda';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON greenhouse.* TO 'greenhouse_app'@'localhost';
-- FLUSH PRIVILEGES;

-- ================================================
-- USEFUL QUERIES
-- ================================================

-- Lihat data sensor 24 jam terakhir
-- SELECT * FROM sensor_suhu_kelembapan WHERE waktu >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY waktu DESC;

-- Lihat rata-rata suhu hari ini
-- SELECT AVG(suhu) as avg_temp, AVG(kelembapan) as avg_humidity FROM sensor_suhu_kelembapan WHERE DATE(waktu) = CURDATE();

-- Cleanup data lebih dari 30 hari
-- CALL cleanup_old_data(30);

-- Lihat view data terbaru
-- SELECT * FROM v_latest_sensors;
-- SELECT * FROM v_actuator_status;