# 🌿 Greenhouse Smart Monitoring System v2.5

Dashboard profesional untuk monitoring dan kontrol greenhouse secara real-time dengan notifikasi otomatis.

## ✨ Fitur Utama

### 📊 Monitoring Real-time
- **Suhu & Kelembapan Udara** (DHT22)
- **Kelembapan Tanah** (Soil Moisture Sensor)
- **Intensitas Cahaya** (LDR Sensor)
- Update otomatis setiap 3 detik

### 📈 Visualisasi Data
- Grafik kombinasi Suhu & Kelembapan (dual Y-axis)
- Grafik Kelembapan Tanah
- Grafik Intensitas Cahaya (bar chart)
- History 24 data point terakhir

### 🎮 Kontrol Aktuator
- **Pompa Air** - Untuk sistem irigasi
- **Kipas Angin** - Untuk sirkulasi udara
- **Lampu Grow** - Untuk pencahayaan tambahan
- Toggle ON/OFF dengan UI modern

### 🔔 Sistem Notifikasi Otomatis
- ⚠️ Peringatan threshold sensor
- 📱 Notifikasi via Telegram
- 📧 Support untuk email (opsional)
- 💬 Support untuk WhatsApp API (opsional)

### 🌓 Dark Mode
- Switch light/dark theme
- Simpan preferensi user

### 📝 Activity Logging
- Log setiap perubahan actuator
- Log alert dan warning
- Timestamped history

---

## 🚀 Instalasi

### 1. Database Setup

Jalankan SQL berikut untuk membuat struktur database:

```sql
CREATE DATABASE greenhouse;
USE greenhouse;

-- Tabel sensor suhu & kelembapan
CREATE TABLE sensor_suhu_kelembapan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    suhu DECIMAL(5,2) NOT NULL,
    kelembapan DECIMAL(5,2) NOT NULL,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel sensor LDR (cahaya)
CREATE TABLE sensor_ldr (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nilai_ldr INT NOT NULL,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel sensor kelembapan tanah
CREATE TABLE sensor_soil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soil INT NOT NULL,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel actuator
CREATE TABLE actuator (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    status TINYINT(1) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default actuators
INSERT INTO actuator (code, status) VALUES 
('pump', 0),
('fan', 0),
('light', 0);

-- Tabel log aktivitas
CREATE TABLE log_aktivitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    detail TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

### 2. Struktur File

```
greenhouse/
├── index.php                    # Main dashboard
├── config/
│   ├── db.php                  # Database config
│   └── telegram_config.php     # Telegram notification config
├── api/
│   ├── get_data.php            # Get all sensor data
│   ├── insert_esp1.php         # Insert DHT22 + LDR data
│   ├── insert_esp2.php         # Insert soil moisture data
│   ├── update_actuator.php     # Toggle actuator ON/OFF
│   ├── clear_logs.php          # Clear activity logs
│   └── monitor_thresholds.php  # Automatic threshold monitoring
├── assets/
│   ├── css/
│   │   └── style.css           # Enhanced styling
│   └── js/
│       └── app.js              # Main JavaScript logic
└── data/
    └── last_alerts.json        # Alert tracking (auto-created)
```

### 3. Konfigurasi Database

Edit file `config/db.php`:

```php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = 'your_password';  // Isi password MySQL Anda
$DB_NAME = 'greenhouse';
```

---

## 📱 Setup Notifikasi Telegram

### Step 1: Buat Bot Telegram

1. Buka Telegram dan cari **@BotFather**
2. Ketik `/newbot`
3. Ikuti instruksi:
   - Berikan nama bot (contoh: `My Greenhouse Bot`)
   - Berikan username (contoh: `myGreenhouse_bot`)
4. **Simpan Token** yang diberikan (format: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

### Step 2: Dapatkan Chat ID

1. Cari bot Anda di Telegram
2. Klik **Start** atau kirim pesan apapun
3. Buka browser dan akses:
   ```
   https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates
   ```
4. Cari bagian `"chat":{"id":` dan **salin angka Chat ID** Anda

### Step 3: Konfigurasi

Edit file `config/telegram_config.php`:

```php
define('TELEGRAM_BOT_TOKEN', '123456789:ABCdefGHIjklMNOpqrsTUVwxyz'); // Ganti dengan token Anda
define('TELEGRAM_CHAT_ID', '987654321');  // Ganti dengan chat ID Anda
define('TELEGRAM_ENABLED', true);  // Set true untuk mengaktifkan
```

### Step 4: Test Notifikasi

Buka browser dan akses:
```
http://localhost/greenhouse/api/monitor_thresholds.php
```

Anda akan menerima notifikasi di Telegram jika ada sensor yang melewati threshold.

---

## ⚙️ Setup Monitoring Otomatis (Cron Job)

Untuk monitoring otomatis setiap 5 menit, tambahkan cron job:

### Linux/Mac:

1. Buka crontab:
   ```bash
   crontab -e
   ```

2. Tambahkan baris ini:
   ```
   */5 * * * * php /path/to/greenhouse/api/monitor_thresholds.php >> /path/to/greenhouse/data/monitor.log 2>&1
   ```

### Windows (Task Scheduler):

1. Buka Task Scheduler
2. Buat Task baru
3. Trigger: Setiap 5 menit
4. Action: Run program
   - Program: `php.exe`
   - Arguments: `C:\xampp\htdocs\greenhouse\api\monitor_thresholds.php`

---

## 🎯 Threshold Konfigurasi

Edit threshold di file `assets/js/app.js` dan `api/monitor_thresholds.php`:

```javascript
const THRESHOLDS = {
  temp: { min: 20, max: 35 },      // Suhu dalam °C
  humidity: { min: 40, max: 80 },  // Kelembapan udara dalam %
  soil: { min: 30, max: 70 },      // Kelembapan tanah dalam %
  light: { min: 100, max: 1000 }   // Intensitas cahaya dalam lux
};
```

---

## 🔌 Integrasi ESP32/ESP8266

### ESP32 Code Example (DHT22 + LDR):

```cpp
#include <WiFi.h>
#include <HTTPClient.h>
#include <DHT.h>

#define DHTPIN 4
#define DHTTYPE DHT22
#define LDRPIN 34

const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";
const char* serverName = "http://192.168.1.100/greenhouse/api/insert_esp1.php";

DHT dht(DHTPIN, DHTTYPE);

void setup() {
  Serial.begin(115200);
  WiFi.begin(ssid, password);
  dht.begin();
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi connected!");
}

void loop() {
  float temp = dht.readTemperature();
  float humidity = dht.readHumidity();
  int ldr = analogRead(LDRPIN);
  
  if (isnan(temp) || isnan(humidity)) {
    Serial.println("Failed to read from DHT sensor!");
    delay(2000);
    return;
  }
  
  HTTPClient http;
  http.begin(serverName);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  
  String postData = "suhu=" + String(temp) + 
                    "&kelembapan=" + String(humidity) + 
                    "&ldr=" + String(ldr);
  
  int httpCode = http.POST(postData);
  
  if (httpCode > 0) {
    Serial.println("Data sent: " + postData);
    Serial.println("Response: " + http.getString());
  }
  
  http.end();
  delay(3000); // Send every 3 seconds
}
```

### ESP32 Code Example (Soil Moisture):

```cpp
#include <WiFi.h>
#include <HTTPClient.h>

#define SOILPIN 35

const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";
const char* serverName = "http://192.168.1.100/greenhouse/api/insert_esp2.php";

void setup() {
  Serial.begin(115200);
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi connected!");
}

void loop() {
  int soilValue = analogRead(SOILPIN);
  int soilPercent = map(soilValue, 4095, 0, 0, 100); // Adjust mapping based on sensor
  
  HTTPClient http;
  http.begin(serverName);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  
  String postData = "soil=" + String(soilPercent);
  
  int httpCode = http.POST(postData);
  
  if (httpCode > 0) {
    Serial.println("Soil data sent: " + String(soilPercent) + "%");
    Serial.println("Response: " + http.getString());
  }
  
  http.end();
  delay(3000);
}
```

---

## 🐛 Troubleshooting

### Grafik tidak muncul
- Cek console browser (F12) untuk error JavaScript
- Pastikan Chart.js berhasil dimuat
- Pastikan ada data di database

### Actuator tidak bisa toggle
- Cek `api/update_actuator.php` bisa diakses
- Cek tabel `actuator` di database
- Cek console browser untuk error AJAX

### Notifikasi Telegram tidak terkirim
- Pastikan `TELEGRAM_ENABLED` = `true`
- Verifikasi Bot Token dan Chat ID benar
- Test manual dengan akses `monitor_thresholds.php` di browser
- Cek `error_log` server

### Data sensor tidak update
- Pastikan ESP32/ESP8266 terhubung ke WiFi
- Cek IP server benar di kode ESP
- Test endpoint `insert_esp1.php` dan `insert_esp2.php` dengan Postman

---

## 📧 Setup Email Notification (Opsional)

Tambahkan di `config/telegram_config.php`:

```php
// Email Configuration
define('EMAIL_ENABLED', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('EMAIL_TO', 'recipient@email.com');

function sendEmailNotification($subject, $message) {
    if (!EMAIL_ENABLED) return false;
    
    // Implementasi menggunakan PHPMailer atau mail()
    // ... kode email di sini
}
```

---

## 📱 Setup WhatsApp Notification (Opsional)

Gunakan WhatsApp Business API atau third-party service seperti:
- Twilio WhatsApp API
- WA Gateway
- Fonnte
- Wablas

---

## 🎨 Customization

### Mengubah Warna Theme

Edit `assets/css/style.css`:

```css
:root {
  --accent: #10b981;  /* Warna utama */
  --accent-hover: #059669;
  --danger: #ef4444;
}
```

### Menambah Sensor Baru

1. Tambah kolom di database
2. Update `get_data.php` untuk query data
3. Tambah card di `index.php`
4. Update `app.js` untuk display data

---

## 📜 License

MIT License - Feel free to use and modify!

## 👨‍💻 Support

Untuk bantuan lebih lanjut:
- 📧 Email: support@greenhouse.com
- 💬 Telegram: @greenhouse_support

---

**Dibuat dengan ❤️ untuk Smart Agriculture**