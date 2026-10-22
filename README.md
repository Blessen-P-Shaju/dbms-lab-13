# Pharmacy Sales System — DBMS Lab Mini Project

- **Student Name:** Blessen P Shaju
- **Roll Number:** 13
- **Question Number:** Q13
- **Project:** Pharmacy Sales System
- **Database:** `pharmacy_db`

## How PHP Connects to MySQL and Executes Queries

1. **Database Connection:** `db.php` establishes the connection between PHP and MySQL using the `mysqli` extension with host `localhost`, username `root`, an empty password, and database `pharmacy_db`.
2. **Form Submission:** When `sale.php` is submitted via HTTP POST, the selected customer ID, medicine ID, quantity, and sale date are captured and validated.
3. **Executing INSERT Query:** A prepared statement (`INSERT INTO sale (customer_id, medicine_id, quantity, sale_date) VALUES (?, ?, ?, ?)`) is prepared with `$conn->prepare()`, securely bound with `$stmt->bind_param("iiis", ...)`, and executed using `$stmt->execute()` to store the sale record.
4. **Executing SELECT Query:** `report.php` issues a multi-table `SELECT` query joining `sale`, `customer`, and `medicine` using `INNER JOIN` to fetch the customer name, medicine name, quantity, and sale date.
5. **Amount Calculation:** The sale amount is computed dynamically in SQL as `(m.unit_price * s.quantity) AS amount`.
6. **Result Display:** Results are fetched row-by-row using `$result->fetch_assoc()` and rendered into an HTML table with appropriate output escaping.

## Deliverables Checklist

1. **Source Code:**
   - `db.php` — Database connection configuration
   - `sale.php` — Sale entry form with link to the report
   - `report.php` — Plain tabular sales report with link to the entry form
2. **Database File:**
   - `pharmacy_db.sql` — MySQL database export containing table definitions and sample data
3. **Screenshots (`screenshots/` folder):**
   - `screenshots/entry_form.png` — Sale entry form
   - `screenshots/confirmation_message.png` — Confirmation message ("Record saved successfully!")
   - `screenshots/report_page.png` — Sales report page
4. **Documentation:**
   - `README.md` — Student credentials, 6-line query execution note, setup instructions, and deliverables checklist

## Setup Instructions (XAMPP)

1. Place the `dbms-lab-13` folder inside your XAMPP `htdocs` directory (e.g. `C:\xampp\htdocs\dbms-lab-13`).
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin` in your browser.
4. Import `pharmacy_db.sql` into phpMyAdmin.
5. Open `http://localhost/dbms-lab-13/sale.php` to add new sales.
6. Open `http://localhost/dbms-lab-13/report.php` to view the sales report.
