CREATE DATABASE IF NOT EXISTS fl_db;
USE fl_db;

CREATE TABLE IF NOT EXISTS hotlines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category ENUM('police','medical','fire','disaster') NOT NULL,
    phone VARCHAR(30) NOT NULL,
    description VARCHAR(255),
    city VARCHAR(50) DEFAULT 'national',
    barangay VARCHAR(100) DEFAULT 'all',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    session_id VARCHAR(100),
    hotline_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotline_id) REFERENCES hotlines(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Activity Logs (Audit Trail)
CREATE TABLE IF NOT EXISTS activity_logs (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    admin_username VARCHAR(50)  NOT NULL,
    action         VARCHAR(100) NOT NULL,
    details        VARCHAR(255),
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(150) NOT NULL,
    message    TEXT         NOT NULL,
    type       ENUM('info','warning','emergency') DEFAULT 'info',
    is_active  TINYINT(1) DEFAULT 1,
    created_by VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO hotlines (name, category, phone, description, city, barangay) VALUES
('PNP Emergency Hotline',             'police',  '911',            'National Police Emergency Hotline',      'national',     'all'),
('Mabalacat Police Station',          'police',  '(045) 458-1234', 'Mabalacat City Police Station',          'mabalacat',    'all'),
('Angeles City Police',               'police',  '(045) 888-3333', 'Angeles City Police Station',            'angeles',      'all'),
('Barangay Tanod - Dau',              'police',  '(045) 458-5678', 'Barangay Security Officers',             'mabalacat',    'Dau'),
('Emergency Medical Services',        'medical', '911',            'National Emergency Medical Services',    'national',     'all'),
('Mabalacat City Health Office',      'medical', '(045) 458-2222', 'City Health Office Emergency',           'mabalacat',    'all'),
('AUF Medical Center',                'medical', '(045) 888-4444', 'Hospital Emergency Room',                'angeles',      'all'),
('Red Cross Pampanga',                'medical', '(045) 961-2294', 'Emergency Medical Response',             'san_fernando', 'all'),
('Barangay Health Center - Dau',      'medical', '(045) 458-3456', 'Barangay Health Services',               'mabalacat',    'Dau'),
('Bureau of Fire Protection',         'fire',    '911',            'National Fire Emergency',                'national',     'all'),
('Mabalacat Fire Station',            'fire',    '(045) 458-3333', 'City Fire Department',                   'mabalacat',    'all'),
('Angeles City Fire Department',      'fire',    '(045) 888-2222', 'Fire and Rescue Services',               'angeles',      'all'),
('BFP San Fernando',                  'fire',    '(045) 961-2345', 'Fire Protection Bureau',                 'san_fernando', 'all'),
('NDRRMC Hotline',                    'disaster','911-1406',       'National Disaster Risk Reduction',       'national',     'all'),
('Mabalacat DRRMO',                   'disaster','(045) 458-4444', 'Disaster Risk Reduction Management',     'mabalacat',    'all'),
('PAGASA Weather Updates',            'disaster','(02) 8284-0800', 'Weather Information and Warnings',       'national',     'all'),
('Angeles City DRRM',                 'disaster','(045) 888-5555', 'Disaster Response Management',           'angeles',      'all'),
('Barangay Emergency Response - Dau', 'disaster','(045) 458-6789', 'Local Disaster Response Team',           'mabalacat',    'Dau');

INSERT INTO admin_users (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSp/gi.a2');
