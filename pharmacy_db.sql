

CREATE DATABASE IF NOT EXISTS pharmacy_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmacy_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `sale`;
DROP TABLE IF EXISTS medicine;
DROP TABLE IF EXISTS customer;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE customer (
    customer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE medicine (
    medicine_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medicine_name VARCHAR(120) NOT NULL,
    manufacturer VARCHAR(120) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL CHECK (unit_price >= 0)
) ENGINE=InnoDB;

CREATE TABLE `sale` (
    sale_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    medicine_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL CHECK (quantity >= 1),
    sale_date DATE NOT NULL,
    CONSTRAINT fk_sale_customer FOREIGN KEY (customer_id)
        REFERENCES customer(customer_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sale_medicine FOREIGN KEY (medicine_id)
        REFERENCES medicine(medicine_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO customer (name, address, phone) VALUES
('Arun Kumar', 'Vengola, Kerala', '9876501001'),
('Rahul Thomas', 'Perumbavoor, Kerala', '9876501002'),
('Anjali Menon', 'Aluva, Kerala', '9876501003'),
('Nikhil Jose', 'Kakkanad, Kerala', '9876501004'),
('Meera Nair', 'Muvattupuzha, Kerala', '9876501005');

INSERT INTO medicine (medicine_name, manufacturer, unit_price) VALUES
('Paracetamol 500 mg', 'Cipla', 20.00),
('Cetirizine 10 mg', 'Dr. Reddy''s Laboratories', 35.00),
('Vitamin C 500 mg', 'Sun Pharmaceutical', 60.00),
('Antacid Tablets', 'Abbott', 45.00),
('Amoxicillin 250 mg', 'Cipla', 85.00);

INSERT INTO `sale` (customer_id, medicine_id, quantity, sale_date) VALUES
(1, 1, 2, '2026-10-21'),
(2, 5, 1, '2026-10-21');
