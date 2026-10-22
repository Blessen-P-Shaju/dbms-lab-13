# Pharmacy Sales System — DBMS Lab Mini Project

- **Student name:** Blessen P Shaju
- **Roll number:** 13
- **Question number:** Q13
- **Project:** Pharmacy Sales System
- **Database:** `pharmacy_db`

## Description

This project records pharmacy medicine sales and displays a report containing customer name, medicine name, quantity, amount, and sale date. It uses PHP, MySQL, HTML, and CSS and is designed to run locally with XAMPP.

## Files

- `db.php` — connects PHP to the `pharmacy_db` MySQL database.
- `sale.php` — displays the entry form and inserts a sale using a prepared SQL statement.
- `report.php` — joins `sale`, `customer`, and `medicine` to display the report. The amount is calculated as `unit_price * quantity`.
- `pharmacy_db.sql` — creates the tables and inserts sample master records.
- `screenshots/` — save the required screenshots here.

## How it works

1. Start Apache and MySQL in XAMPP.
2. Import `pharmacy_db.sql` through phpMyAdmin to create the database, tables, and sample records.
3. `db.php` opens a MySQL connection using PHP's `mysqli` extension and is included in both pages.
4. The entry form in `sale.php` sends the selected customer, medicine, quantity, and date using POST. PHP validates the fields and uses a prepared `INSERT` statement to save the sale.
5. `report.php` executes a `SELECT` query with `INNER JOIN` across the three tables. It calculates each sale amount using the medicine unit price multiplied by the quantity and displays the results in an HTML table.

## Setup using XAMPP

1. Copy the `dbms-lab-13` folder into your XAMPP `htdocs` directory. Example on Windows: `C:\\xampp\\htdocs\\dbms-lab-13`.
2. Open the XAMPP Control Panel and start **Apache** and **MySQL**.
3. Visit `http://localhost/phpmyadmin`.
4. Choose **Import**, select `pharmacy_db.sql`, and run the import.
5. Open `http://localhost/dbms-lab-13/sale.php`.
6. Save a test sale, then open the report page to check that it appears.
7. Take screenshots of the entry form, successful save message, and report page. Save them in `screenshots/`.

## GitHub submission

Create a public repository named `dbms-lab-13`, then upload the project files and screenshots. Confirm that the files are visible when logged out, and submit the repository link to your faculty before the deadline.

## Notes

- The default XAMPP MySQL account is commonly `root` with an empty password on a local installation. If your MySQL password differs, update `db.php`.
- The SQL file includes two example sale records so the report is populated on first import. Remove the optional `INSERT INTO sale` section if your instructor wants an initially empty transaction table.
- Use fictional/sample data for the lab demonstration.
