-- database: greenhouse_db
CREATE DATABASE greenhouse_db;
USE greenhouse_db;

CREATE TABLE sensors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sensor_type VARCHAR(50) NOT NULL, -- 'temp','hum','ldr','soil'
  value FLOAT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(sensor_type),
  INDEX(created_at)
);

CREATE TABLE actuators (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL, -- 'pump','fan','light'
  status TINYINT(1) NOT NULL DEFAULT 0, -- 0=off,1=on
  last_changed DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE actuator_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actuator_id INT NOT NULL,
  action VARCHAR(10) NOT NULL, -- 'ON'/'OFF'
  reason VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (actuator_id) REFERENCES actuators(id)
);

-- contoh data awal
INSERT INTO actuators (name, status) VALUES
('pump', 0), ('fan', 0), ('light', 0);

-- contoh data sensor (masukkan beberapa baris untuk history)
INSERT INTO sensors (sensor_type, value) VALUES
('temp', 28.5), ('hum', 60), ('ldr', 720), ('soil', 45),
('temp', 29.0), ('hum', 58), ('ldr', 680), ('soil', 42);