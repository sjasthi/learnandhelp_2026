-- ============================================================
-- FP8: Assets Management System
-- Database: learnandhelp_db
-- Run in phpMyAdmin: paste entire file into the SQL tab and execute
-- ============================================================

-- ── 1. Assets table ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS assets (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(150)                              NOT NULL,
  asset_type       ENUM('Physical','Digital')               DEFAULT 'Physical',
  category         VARCHAR(100)                             DEFAULT NULL,
  description      TEXT                                     DEFAULT NULL,
  serial_number    VARCHAR(100)                             DEFAULT NULL,
  purchase_date    DATE                                     DEFAULT NULL,
  purchase_price   DECIMAL(10,2)                            DEFAULT NULL,
  condition_status ENUM('New','Good','Fair','Poor','Retired') DEFAULT 'Good',
  location         VARCHAR(150)                             DEFAULT NULL,
  assigned_to      VARCHAR(150)                             DEFAULT NULL,
  warranty_expiry  DATE                                     DEFAULT NULL,
  notes            TEXT                                     DEFAULT NULL,
  status           ENUM('Active','Inactive','Disposed')     DEFAULT 'Active',
  photo_path       VARCHAR(255)                             DEFAULT NULL,
  date_created     DATETIME                        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 2. Asset audit log table ──────────────────────────────────
CREATE TABLE IF NOT EXISTS asset_logs (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  asset_id    INT          DEFAULT NULL,
  asset_name  VARCHAR(150) NOT NULL,
  action      VARCHAR(50)  NOT NULL,
  changed_by  VARCHAR(150) DEFAULT NULL,
  details     TEXT         DEFAULT NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 3. Dummy data (30 records) ────────────────────────────────
-- Safe to run on a fresh table. If you already have data, skip this section.
--
-- NOTE ON PHOTOS:
-- The assets table includes a photo_path column for storing uploaded images.
-- Photo files are stored locally in uploads/assets/ on each machine.
-- Because image files cannot be distributed via SQL, photo_path is set to NULL
-- in all records below. To test the photo upload feature, log in as admin,
-- open any asset, click Edit, and upload an image through the form.
-- The file will be saved automatically to your local uploads/assets/ folder.
INSERT INTO assets (name, asset_type, category, description, serial_number, purchase_date, purchase_price, condition_status, location, assigned_to, warranty_expiry, status, notes) VALUES
-- Physical – Computers & Peripherals
('MacBook Pro 14"',            'Physical', 'Computer',          'Apple MacBook Pro 14-inch M2, used for admin tasks',          'SN-MBP-001',  '2023-08-15', 1999.00, 'Good',    'Main Office',    'Nick Herberg',    '2026-08-15', 'Active',   NULL),
('Dell Monitor 27"',           'Physical', 'Monitor',           'Dell UltraSharp 27" 4K monitor',                              'SN-DLM-002',  '2023-08-15',  549.00, 'Good',    'Main Office',    'Nick Herberg',    '2026-08-15', 'Active',   NULL),
('Wireless Keyboard & Mouse',  'Physical', 'Computer',          'Logitech MK850 wireless combo set',                           'SN-LGK-003',  '2023-08-15',   79.99, 'Good',    'Main Office',    'Nick Herberg',    '2026-08-15', 'Active',   NULL),
('Logitech Webcam C920',       'Physical', 'Camera',            'HD webcam used for virtual classes and meetings',             'SN-LOG-004',  '2022-09-05',   79.00, 'Good',    'Main Office',    'Nick Herberg',    '2025-09-05', 'Active',   NULL),
('iPad (9th Gen)',              'Physical', 'Tablet',            'Apple iPad used for student check-in at events',              'SN-IPD-005',  '2023-01-18',  329.00, 'Good',    'Main Office',    'Sofia Reyes',     '2026-01-18', 'Active',   NULL),
('Fire Tablet (10")',           'Physical', 'Tablet',            'Amazon Fire HD 10 used as a digital sign at events',          'SN-AMZ-006',  '2022-12-01',  149.99, 'Good',    'Storage Room',   'Sofia Reyes',     '2025-12-01', 'Active',   NULL),
('Old Dell Laptop',            'Physical', 'Computer',          'Dell Inspiron 15, retired – battery no longer holds charge',  'SN-DEL-007',  '2018-05-30',  650.00, 'Retired', 'Storage Room',   NULL,              '2021-05-30', 'Inactive', 'Kept for parts'),
('Broken HP Laptop',           'Physical', 'Computer',          'HP Pavilion 15 – cracked screen, non-functional',             'SN-HP2-008',  '2019-08-10',  699.00, 'Poor',    'Storage Room',   NULL,              '2022-08-10', 'Disposed', 'Sent to recycling Mar 2024'),
-- Physical – Office & AV Equipment
('HP LaserJet Printer',        'Physical', 'Printer',           'HP LaserJet Pro MFP – print, scan, copy',                     'SN-HPL-009',  '2022-03-10',  399.00, 'Fair',    'Main Office',    'Maria Santos',    '2025-03-10', 'Active',   'Needs toner soon'),
('Projector – Epson EB-X',     'Physical', 'Projector',         'Epson classroom projector, used for events and classes',      'SN-EPS-010',  '2021-06-01',  699.00, 'Fair',    'Storage Room',   'James Okonkwo',   '2024-06-01', 'Active',   NULL),
('Conference Room TV 65"',     'Physical', 'Monitor',           'Samsung 65" 4K smart TV mounted in conference room',          'SN-SAM-011',  '2022-06-10', 1299.00, 'Good',    'Conference Room','James Okonkwo',   '2027-06-10', 'Active',   NULL),
('Portable Bluetooth Speaker', 'Physical', 'Audio Equipment',   'JBL Flip 6 portable speaker for outdoor events',             'SN-JBL-012',  '2023-07-04',  129.99, 'Good',    'Storage Room',   'James Okonkwo',   '2026-07-04', 'Active',   NULL),
('Ring Light Kit',             'Physical', 'Camera',            '18" LED ring light with tripod for video recordings',         NULL,          '2023-09-10',   65.99, 'New',     'Main Office',    'Sofia Reyes',     NULL,         'Active',   NULL),
('Label Maker',                'Physical', 'Office Equipment',  'Brother PT-D610BT label printer, Bluetooth-enabled',          'SN-BRT-014',  '2023-05-20',   59.99, 'New',     'Main Office',    'Maria Santos',    '2026-05-20', 'Active',   NULL),
-- Physical – Electrical
('USB Hub (7-port)',            'Physical', 'Electrical',        '7-port powered USB 3.0 hub for conference room',              NULL,          '2022-11-01',   39.99, 'Good',    'Conference Room','Sofia Reyes',     NULL,         'Active',   NULL),
('Extension Cord (25ft)',      'Physical', 'Electrical',        'Heavy-duty extension cord for events',                        NULL,          '2021-04-12',   35.00, 'Good',    'Storage Room',   'James Okonkwo',   NULL,         'Active',   NULL),
('Surge Protector Strip',      'Physical', 'Electrical',        '8-outlet surge protector for server/equipment area',          NULL,          '2021-07-15',   29.99, 'Good',    'Main Office',    NULL,              NULL,         'Active',   '2 units'),
-- Physical – Furniture
('Standing Desk',              'Physical', 'Furniture',         'Electric height-adjustable standing desk, 60x30"',            NULL,          '2023-03-15',  549.00, 'New',     'Main Office',    'Nick Herberg',    NULL,         'Active',   NULL),
('Folding Table (6ft)',         'Physical', 'Furniture',         'Plastic folding table used at community booths',             NULL,          '2020-01-20',   89.00, 'Good',    'Storage Room',   'Maria Santos',    NULL,         'Active',   '4 units total'),
('Folding Chairs (set of 10)', 'Physical', 'Furniture',         'Metal folding chairs for events',                             NULL,          '2020-01-20',  120.00, 'Good',    'Storage Room',   'Maria Santos',    NULL,         'Active',   NULL),
('Whiteboard (4x6)',           'Physical', 'Furniture',         'Magnetic dry-erase whiteboard, wall-mounted',                 NULL,          '2021-09-01',  129.00, 'Good',    'Conference Room', NULL,             NULL,         'Active',   NULL),
-- Digital – Software Licenses
('Google Workspace (Annual)',  'Digital',  'Software License',  'Google Workspace Business Starter – annual subscription',    'LIC-GWS-022', '2024-01-01',  144.00, 'New',     NULL,             'Nick Herberg',    '2026-01-01', 'Active',   'Renews Jan 2026'),
('Microsoft 365 (Annual)',     'Digital',  'Software License',  'Microsoft 365 Business Basic – 5 user licenses',             'LIC-M365-023','2024-01-01',  300.00, 'New',     NULL,             'Nick Herberg',    '2026-01-01', 'Active',   'Renews Jan 2026'),
('Zoom Pro License',           'Digital',  'Software License',  'Zoom Pro account for virtual classes and meetings',           'LIC-ZOM-024', '2024-03-01',  149.88, 'Good',    NULL,             'Sofia Reyes',     '2026-03-01', 'Active',   'Annual plan'),
('Canva Pro',                  'Digital',  'Software License',  'Canva Pro for marketing and event materials',                 'LIC-CNV-025', '2024-02-15',   54.99, 'Good',    NULL,             'Sofia Reyes',     '2025-02-15', 'Active',   NULL),
('Slack Pro',                  'Digital',  'Software License',  'Slack Pro for team communication',                           'LIC-SLK-026', '2024-02-01',  225.00, 'Good',    NULL,             'Nick Herberg',    '2026-02-01', 'Active',   'Annual plan'),
('Dropbox Business',           'Digital',  'Software License',  'Dropbox Business plan for file sharing and backup',          'LIC-DBX-027', '2024-04-01',  180.00, 'Good',    NULL,             'Nick Herberg',    '2026-04-01', 'Active',   'Annual plan'),
-- Digital – Domain & Hosting
('Domain – learnandhelp.com',  'Digital',  'Domain / Hosting',  'Annual domain registration',                                  NULL,          '2024-06-01',   18.00, 'Good',    NULL,             'Nick Herberg',    '2025-06-01', 'Active',   'Renews Jun 2025'),
('Web Hosting (SiteGround)',   'Digital',  'Domain / Hosting',  'Shared hosting plan for the project website',                 'LIC-SGH-029', '2024-06-01',  179.88, 'Good',    NULL,             'Nick Herberg',    '2025-06-01', 'Active',   'Annual plan'),
('SSL Certificate',            'Digital',  'Domain / Hosting',  'Annual SSL certificate for learnandhelp.com',                 NULL,          '2024-06-01',   75.00, 'Good',    NULL,             'Nick Herberg',    '2025-06-01', 'Active',   NULL);
