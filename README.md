# 🛒 YBT Digital - E-Commerce Platform

YBT Digital is a high-performance, robust e-commerce solution built with PHP, MySQL, and Vanilla CSS. It features a complete shopping experience for users and a powerful management dashboard for administrators.

---

## ✨ Core Features

### 👤 User Features
- **Modern Landing Page:** Responsive design with dynamic product listings and categories.
- **Support System:** Integrated ticket-based support system for customer inquiries.
- **Smart Shopping Cart:** Seamless product discovery to checkout flow.
- **Profiles:** Manage personal information, track orders, and view support tickets.
- **Product Reviews:** Authenticated users can leave ratings and feedback.
- **Coupons:** Support for flat and percentage-based discounts.

### 🔐 Admin Dashboard
- **Product Management:** Full CRUD operations for products and categories.
- **Order Tracking:** Monitor order statuses from pending to completed.
- **User Management:** Oversee registered users and their activities.
- **Marketing Tools:** Manage coupons, FAQs, and customer testimonials.
- **Advanced Reports:** Visual reports for sales and platform performance.
- **Site Settings:** Configure core platform parameters directly from the dashboard.

---

## 🛠️ Technology Stack
- **Backend:** PHP 7.4+ (OOP based classes)
- **Database:** MySQL
- **Frontend:** Vanilla CSS, JavaScript, FontAwesome Icons
- **Server:** Apache (XAMPP/WAMP recommended)

---

## 🚀 Installation & Setup

### 1. Database Setup
- Create a new database named `ybt_digital` in your MySQL server.
- Import the schema from [database/schema.sql](file:///c:/xampp/htdocs/YBT%20Digital/database/schema.sql).

### 2. Configuration
- Open [config/database.php](file:///c:/xampp/htdocs/YBT%20Digital/config/database.php).
- Update the credentials (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`) to match your environment.

### 3. Access the Project
- Move the project folder to your server's root (e.g., `xampp/htdocs/`).
- Visit: `http://localhost/YBT%20Digital/`

---

## 🔑 Default Admin Credentials
> [!IMPORTANT]
> Use the following credentials to access the admin dashboard:
> - **URL:** `http://localhost/YBT%20Digital/admin/login.php`
> - **Email:** `admin@ybtdigital.com`
> - **Password:** `password`

---

## 📂 Project Structure
```text
├── admin/          # Admin dashboard and management pages
├── api/            # Backend API endpoints
├── assets/         # CSS, JS, and Static Images
├── classes/        # CORE PHP Logic (Auth, Product, Cart, etc.)
├── config/         # Database and Site configurations
├── database/       # SQL Schema and migrations
├── includes/       # Shared Header/Footer components
├── uploads/        # Product and User uploaded media
└── *.php           # Core customer-facing pages
```

---

## 🤝 Contributing
Feel free to fork this repository and submit pull requests for any features or bug fixes.
