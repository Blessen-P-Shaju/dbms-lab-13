# Pharmacy Sales System — DBMS Lab Mini Project

- **Student Name:** Blessen P Shaju
- **Roll Number:** 13
- **Question Number:** Q13
- **Project:** Pharmacy Sales System
- **Database:** `pharmacy_db`

## How PHP Connects to MySQL and Executes Queries

1. **Database Connection:** `db.php` establishes the connection between PHP and MySQL using the `mysqli` extension with host `localhost`, username `root`, an empty password, and database `pharmacy_db`.
2. **Form Submission:** When `sale.php` is submitted via HTTP POST, existing dropdown selections or new customer/medicine details are captured inside a database transaction (`$conn->begin_transaction()`).
3. **Executing INSERT Queries:** If new customer or medicine fields are entered, prepared statements insert them into `customer` and `medicine` first (`$conn->insert_id` retrieves their auto-incremented IDs), followed by inserting the sale record into `sale` (`INSERT INTO sale (customer_id, medicine_id, quantity, sale_date) VALUES (?, ?, ?, ?)`).
4. **Executing SELECT Query:** `report.php` issues a multi-table `SELECT` query joining `sale`, `customer`, and `medicine` using `INNER JOIN`, supporting both All Customers and Individual Customer filtering as well as date-range filtering.
5. **Amount Calculation:** The sale amount is computed dynamically in SQL as `(m.unit_price * s.quantity) AS amount` (with `unit_price` kept intact in the `medicine` table).
6. **Result Display:** Results are fetched row-by-row using `$result->fetch_assoc()` and rendered into a plain HTML table with live client-side search.

## Deliverables Checklist

1. **Source Code:**
   - `db.php` — Database connection configuration
   - `sale.php` — Interactive sale entry form (existing dropdowns with automatic customer-to-medicine selection + unified section for adding new customer and medicine with auto-ascending ID previews)
   - `report.php` — Plain tabular sales report with Individual/All Customer dropdown, date-range filter, and live search
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
