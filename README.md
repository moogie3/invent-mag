# Invent-MAG: Advanced Inventory & Accounting System

**Version:** 1.2  
**Author:** moogie3  
**License:** MIT  

---

## 📘 Introduction

Invent-MAG is a comprehensive, modern, and feature-rich application designed to be a complete solution for managing your business. It combines inventory management, sales and purchases, customer and supplier relationship management (CRM/SRM), and a full accounting suite into a single, cohesive platform.

Built on the latest Laravel 11 framework, Invent-MAG provides powerful tools for real-time stock tracking, sophisticated order management, insightful financial reporting, and granular user access control. It is the ideal solution for businesses looking to streamline operations, enhance collaboration, and leverage data-driven insights to make informed decisions.

![Screenshot of Invent-MAG](screenshot.png)

---

## 🚀 Live Demo & Credentials

Experience the power of Invent-MAG firsthand with our live demo.

- **URL:** `https://invent-mag.com`
- **Admin Login:**
  - **Email:** `admin@example.com`
  - **Password:** `password`
- **Manager Login:**
  - **Email:** `manager@example.com`
  - **Password:** `password`
- **Staff Login:**
  - **Email:** `staff@example.com`
  - **Password:** `password`

---

## ✨ Key Features

Invent-MAG offers a powerful suite of functionalities to manage every aspect of your business.

### **Inventory & Product Management**
- **Product Catalog:** Full CRUD for products, including detailed information, images, and categorizations.
- **Stock Control:** Track stock levels across multiple warehouses in real-time.
- **Stock Adjustments:** Manage stock inflows and outflows with clear reason codes.
- **Low Stock Alerts:** Proactive alerts for items falling below their low-stock threshold.
- **Unit Management:** Define and manage different units of measurement for your products.

### **Sales & Customer Management (CRM)**
- **Sales Order Management:** Create, manage, and track sales orders from creation to payment.
- **Point of Sale (POS):** A dedicated, user-friendly POS interface for fast and efficient in-person transactions with receipt printing.
- **Customer Database:** Full CRUD for customer profiles, including interaction history and purchase tracking.
- **Sales Pipeline:** A complete sales pipeline management system to track and convert sales opportunities into orders.
- **Sales Returns:** Easily manage customer returns and process refunds.
- **Advanced Analytics:**
  - **Customer Retention & LTV:** Analyze customer loyalty and lifetime value.
  - **Sales Forecasting:** Predict future sales trends based on historical data.

### **Purchase & Supplier Management (SRM)**
- **Purchase Order Management:** Full CRUD for purchase orders, from creation to stock reconciliation.
- **Supplier Database:** Maintain detailed supplier profiles, track interaction history, and manage procurement.
- **Purchase Returns:** Manage returns to suppliers efficiently.
- **Supplier Analytics:** Analyze supplier performance and track purchase history.

### **Financial & Accounting Suite**
- **Chart of Accounts (COA):** A customizable COA to organize your company's finances.
- **Journal & General Ledger:** Record all financial transactions and maintain a complete audit trail.
- **Trial Balance:** Ensure the accuracy of your accounting entries.
- **Financial Reporting:**
  - **Income Statement:** Track your company's profitability.
  - **Balance Sheet:** Get a snapshot of your company's financial health.
- **Accounts Receivable & Payable:**
  - **AR/AP Aging Reports:** Monitor outstanding invoices and bills.
  - **Payment Tracking:** Record and manage payments for both sales and purchase orders.
- **Multi-Currency Support:** Configure and manage transactions in different currencies.

### **Administration & System**
- **Interactive Dashboard:** A detailed dashboard with key metrics on sales, purchases, financial health, AR/AP aging, low stock alerts, and a sales forecast.
- **User & Role Management:** Granular control over user access with pre-defined roles (Admin, Manager, Staff) powered by Spatie Laravel Permission.
- **Settings:** Customize application settings, including currency, taxes, and theme.
- **Notifications:** An in-app notification system to keep users informed of important events.
- **RESTful API:** A versioned (v1) API with Sanctum authentication to integrate with other systems.

---

## 🚀 Technology Stack

- **Backend:** Laravel 11, PHP 8.2+, Laravel Fortify, Laravel Sanctum, Spatie Laravel Permission
- **Frontend:** Blade, Alpine.js, Tailwind CSS, Vite
- **Database:** MySQL (Recommended), SQLite
- **Testing:** PHPUnit, Vitest

---

## ⚙️ Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & NPM/Yarn
- MySQL

### Installation Steps
1.  **Clone the repository:**
    ```bash
    git clone https://github.com/moogie3/invent-mag.git
    cd invent-mag
    ```
2.  **Install dependencies:**
    ```bash
    composer install
    npm install
    ```
3.  **Configure environment:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    # Configure your DB_DATABASE, DB_USERNAME, and DB_PASSWORD in .env
    ```
4.  **Run migrations and seeders:**
    ```bash
    php artisan migrate --seed
    ```
5.  **Build assets:**
    ```bash
    npm run build
    ```
6.  **Serve the application:**
    ```bash
    php artisan serve
    ```

---

## 🧪 Running Tests

- **Backend (PHPUnit):** `php artisan test`
- **Frontend (Vitest):** `npm run test:js`

---

## 🤝 Support & Contributions

If you encounter any issues or have suggestions, please submit an issue on GitHub. Pull requests are welcome!

---

## 📜 License

Invent-MAG is licensed under the **MIT License**. See the `LICENSE` file for more details.
