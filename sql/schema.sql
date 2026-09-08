CREATE DATABASE IF NOT EXISTS membership_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE membership_app;

-- Categories are admin-managed: each has its own color and back-of-card layout
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    color_hex VARCHAR(7) NOT NULL DEFAULT '#1c3b2e',   -- e.g. #FFFFFF, #2f6b3f
    back_layout ENUM('family', 'facilities') NOT NULL DEFAULT 'family',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_code VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    parent_spouse_name VARCHAR(150) DEFAULT NULL,
    cnic_no VARCHAR(20) DEFAULT NULL,
    category_id INT NOT NULL,
    address VARCHAR(255) DEFAULT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    joined_at DATE NOT NULL,
    valid_upto DATE NOT NULL,
    status ENUM('active', 'expired', 'revoked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

-- Used on the back of the card when the member's category back_layout = 'family'
CREATE TABLE IF NOT EXISTS family_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    relationship VARCHAR(60) NOT NULL,
    birth_year VARCHAR(4) DEFAULT NULL,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Admin-managed instruction bullets shown on 'facilities' back layout
CREATE TABLE IF NOT EXISTS instructions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    text VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Admin-managed facility/discount list shown on 'facilities' back layout
-- (text-based, since third-party brand logos can't be auto-generated)
CREATE TABLE IF NOT EXISTS facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    discount_text VARCHAR(60) DEFAULT NULL,
    logo_path VARCHAR(255) DEFAULT NULL,   -- optional: admin can upload their own licensed logo image
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Seed the two categories from your reference design
INSERT INTO categories (name, color_hex, back_layout) VALUES
    ('Civilian', '#FFFFFF', 'family'),
    ('Serving Officer', '#2f6b3f', 'facilities')
ON DUPLICATE KEY UPDATE name = name;
