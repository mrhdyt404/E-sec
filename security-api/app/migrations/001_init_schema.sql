CREATE DATABASE IF NOT EXISTS security_api;
USE security_api;

-- traffic_logs
CREATE TABLE traffic_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  ts DATETIME NOT NULL,
  ip VARCHAR(45) NOT NULL,
  method VARCHAR(10),
  path TEXT,
  status SMALLINT,
  response_time_ms INT,
  bytes INT,
  user_agent TEXT,
  referrer TEXT,
  server VARCHAR(255),
  country CHAR(2),
  city VARCHAR(64),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ts (ts),
  INDEX idx_ip (ip),
  INDEX idx_path (path(255))
) ENGINE=InnoDB;

-- ip_reputation
CREATE TABLE ip_reputation (
  ip VARCHAR(45) PRIMARY KEY,
  score INT DEFAULT 0,
  last_seen DATETIME,
  country CHAR(2),
  city VARCHAR(64),
  blocked TINYINT DEFAULT 0
);

-- geoip_cache
CREATE TABLE geoip_cache (
  ip VARCHAR(45) PRIMARY KEY,
  country CHAR(2),
  city VARCHAR(64),
  lat DECIMAL(9,6),
  lon DECIMAL(9,6),
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- traffic_stats
CREATE TABLE traffic_stats (
  path VARCHAR(255) PRIMARY KEY,
  avg_rps FLOAT,
  std_rps FLOAT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- anomalies
CREATE TABLE anomalies (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  ts DATETIME,
  ip VARCHAR(45),
  path VARCHAR(255),
  type VARCHAR(50),
  score INT,
  description TEXT
);

-- blocks
CREATE TABLE blocks (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  prev_hash CHAR(64),
  hash CHAR(64),
  data_hash CHAR(64),
  ts DATETIME
);

-- alerts
CREATE TABLE alerts (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  ts DATETIME,
  ip VARCHAR(45),
  channel ENUM('FCM','WA','EMAIL'),
  message TEXT,
  status ENUM('sent','failed'),
  response TEXT
);

-- api_clients
CREATE TABLE api_clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  api_key CHAR(64),
  active TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- system_settings
CREATE TABLE system_settings (
  k VARCHAR(100) PRIMARY KEY,
  v TEXT
);
