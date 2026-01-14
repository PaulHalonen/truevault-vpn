-- TRUEVAULT VPN - ENHANCED DEVICE MANAGEMENT SCHEMA
-- Version: 3.0 with Network Scanner Support

-- Discovered Devices Table (from network scanner)
CREATE TABLE IF NOT EXISTS discovered_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    device_id TEXT UNIQUE NOT NULL,
    user_id INTEGER NOT NULL,
    ip TEXT NOT NULL,
    mac TEXT NOT NULL,
    hostname TEXT,
    vendor TEXT,
    type TEXT NOT NULL,
    type_name TEXT NOT NULL,
    icon TEXT DEFAULT '❓',
    category TEXT DEFAULT 'personal',
    discovered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    added_to_vpn INTEGER DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Port Forwarding Rules
CREATE TABLE IF NOT EXISTS port_forwards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    device_id TEXT NOT NULL,
    device_name TEXT NOT NULL,
    device_type TEXT NOT NULL,
    local_ip TEXT NOT NULL,
    local_port INTEGER NOT NULL,
    external_port INTEGER NOT NULL,
    protocol TEXT DEFAULT 'tcp',
    status TEXT DEFAULT 'active',
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
