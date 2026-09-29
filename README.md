# UniThrift - Campus Academic ReUse & Pre-Owned Gear Marketplace

> **Southeast University**  
> **Department of Computer Science & Engineering**  
> **Course:** CSE 472 - Web and Internet Programming  
> **Project:** Full-Stack Mini Web Application  
> **Technology Stack:** HTML5, CSS3, JavaScript, PHP 8 (PDO), MySQL (MariaDB)

---

## 📖 Project Overview

**UniThrift** is a peer-to-peer campus academic resource exchange marketplace designed for university students. 

Every semester, students purchase expensive course textbooks (e.g., Cormen Algorithms, Database Systems), microcontroller/lab kits (Arduino Uno, sensors, IC chips), engineering drawing tools, and scientific calculators. Once the 4-month semester ends, these supplies sit unused while incoming juniors are forced to purchase brand-new items at full price.

**UniThrift** connects graduating and senior students with incoming juniors to resell their used academic essentials at student-friendly prices, reducing academic costs and eliminating educational waste.

---

## 🌟 Key Features

1. **First-Time Setup Wizard (`install.php`):**
   - Automatically detects missing database and tables on a fresh deployment.
   - Provides a 1-click database creator, table generator, and demo account initializer.
   - Pre-populates 8 realistic academic listings (Textbooks, Arduino kits, Casio calculators, Drawing sets).

2. **Dark Mode & Light Mode Theme Switcher:**
   - Seamless toggle between Dark and Light mode from the navbar.
   - Saves student preference in browser `localStorage`.
   - High-contrast, clean visual design built with CSS variables.

3. **User Authentication & Security:**
   - Student registration with Student ID, Department, Email, and Phone number.
   - Passwords hashed using industry-standard `PASSWORD_BCRYPT`.
   - Protected sessions with auto-redirection on unauthorized access.

4. **Full CRUD Operations (`my_listings.php`):**
   - **Create:** Post items with category, course code tag, condition, original price, resale price, and campus meetup spot.
   - **Read:** Dedicated personal seller studio displaying active inventory, total items, and sold count.
   - **Update:** Edit price, description, condition, or meetup spot.
   - **Status Toggle:** Quick status dropdown (`Available` $\rightarrow$ `Reserved` $\rightarrow$ `Sold`).
   - **Delete:** Remove listings with a confirmation safety prompt.
   - **Strict Ownership Guard:** Validates that `session.user_id === item.user_id` on all updates and deletes.

5. **Live Search & Multi-Criteria Filtering (`marketplace.php`):**
   - Instant real-time search across item titles, course codes, and descriptions without page reload.
   - Category filter pills (`Textbooks`, `Lab Gear & Kits`, `Drawing & Tools`, `Electronics & Calculators`).
   - Condition filter (`Like New`, `Gently Used`, `Fair`).
   - Dynamic sorting (`Newest First`, `Price: Low to High`, `Price: High to Low`, `Biggest Discount %`).

6. **Direct Buyer-to-Seller Communication (`item_details.php`):**
   - 1-click **"WhatsApp Seller"** button with a pre-filled item inquiry message.
   - 1-click **"Call Seller"** button.
   - Campus handover safety tips.

---

## 🗺️ The 5 Interconnected Pages

| # | Page File | Route / Purpose |
|---|---|---|
| **1** | `index.php` | **Home / Landing Page:** Impact counter, category quick-jump cards, recent listings, and How-It-Works guide. |
| **2** | `auth.php` | **Authentication:** Tabbed student login and registration with validation and password hashing. |
| **3** | `marketplace.php` | **Marketplace Catalogue:** Browsing hub with live search bar, category pills, condition filter, and sorting. |
| **4** | `item_details.php` | **Item Details & Contact:** Full specifications, price comparison, discount percentage, and direct contact buttons. |
| **5** | `my_listings.php` | **Seller Studio (CRUD Hub):** Add new items, update prices, change status (Available/Reserved/Sold), and delete. |

---

## 🔒 Security & Validation Implementation

- **SQL Injection Prevention:** 100% of database interactions utilize **PDO Prepared Statements** with parameterized inputs. Zero string concatenation in SQL queries.
- **XSS Sanitization:** All user-supplied output rendered via `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`.
- **Password Protection:** Passwords securely hashed with `password_hash($pass, PASSWORD_BCRYPT)` and verified via `password_verify()`.
- **CSRF Defense:** Action forms validate cryptographically secure tokens generated via `random_bytes(32)`.
- **Input Validation:** Numeric price checks (`price > 0`), required field validation, and email sanitization.

---

## 🚀 Setup & Installation Instructions

### Method 1: Automatic 1-Click Setup (Recommended)
1. Start **Apache** and **MySQL** in your XAMPP Control Panel.
2. Place this project folder into `C:\xampp\htdocs\unithrift` or run via PHP built-in server:
   ```bash
   php -S 127.0.0.1:8000
   ```
3. Open your browser and navigate to:
   ```text
   http://127.0.0.1:8000/install.php
   ```
4. Click **"Initialize Database & Complete Setup"**. The installer creates `unithrift_db`, builds the tables, sets up the admin account, and loads sample data.

### Method 2: Manual Database Import
1. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Create a database named `unithrift_db`.
3. Import the included [`database.sql`](file:///d:/web%20project/database.sql) file.
4. Verify database credentials in [`config/config.php`](file:///d:/web%20project/config/config.php).

---

## 🔑 Default Demo Credentials

| Role | Email / Student ID | Password | Notes |
|---|---|---|---|
| **Administrator / Student** | `admin@seu.edu.bd` *(or `2021000000001`)* | `admin123` | Pre-owns the sample academic listings. |

---

## 🧪 Testing Checklist (Documentation Test Cases)

| Test Case | Scenario Description | Expected Result | Status |
|---|---|---|:---:|
| **TC-01** | Student registration with valid data | Account created, password hashed with BCRYPT, auto-logged in. | ✅ Pass |
| **TC-02** | Registration with duplicate Student ID or Email | Request rejected with friendly alert: *"User already exists"*. | ✅ Pass |
| **TC-03** | Unauthorized access to `my_listings.php` without login | Intercepted with HTTP 302 redirect to `auth.php` with flash alert. | ✅ Pass |
| **TC-04** | Create item with negative price (e.g. `-500`) | Rejected by validation; prompts price must be greater than zero. | ✅ Pass |
| **TC-05** | SQL Injection attempt in search bar (`' OR '1'='1`) | Safely parameterized via PDO; treated as literal string with 0 errors. | ✅ Pass |
| **TC-06** | Dark Mode Toggle button click | HTML `data-theme="dark"` set and persisted in `localStorage`. | ✅ Pass |

---

## 📚 References (Harvard Style)

1. Nixon, R., 2021. *Learning PHP, MySQL & JavaScript: With jQuery, CSS & HTML5*. 6th ed. Sebastopol: O'Reilly Media.
2. The PHP Group, 2024. *PHP Data Objects (PDO) Manual*. Available at: <https://www.php.net/manual/en/book.pdo.php> [Accessed 30 September 2026].
3. Mozilla Developer Network, 2024. *CSS custom properties (variables)*. MDN Web Docs. Available at: <https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties> [Accessed 30 September 2026].
4. OWASP Foundation, 2023. *SQL Injection Prevention Cheat Sheet*. Available at: <https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html> [Accessed 30 September 2026].
