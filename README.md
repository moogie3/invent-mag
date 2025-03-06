# Invent-MAG : Advanced Inventory Management System  

**Version:** 1.0  
**Author:** moogie3  
**License:** MIT  



## 📘 Introduction  
Invent-Mag is a full-featured Inventory Management System built to streamline and optimize warehouse operations.  
The system enables efficient stock tracking, purchase order management, reporting, and user access control.  
It is designed for businesses that require precise stock control, multi-user access, and real-time data insights.  



## 🔹 Key Features  

✅ **Product & Stock Management** – Add, update, delete, and categorize inventory items.  
✅ **Stock Transactions**         – Track inflow (purchases) and outflow (sales/usage).  
✅ **Purchase Order Management**  – Create, approve, and receive supplier orders.  
✅ **Reporting & Analytics**      – Generate stock level reports and transaction logs.  
✅ **Multi-User Access**          – Role-based authentication for Admin, Manager, and Staff.  
✅ **Modern Tech Stack**          – Built using Laravel 11 and MySQL for high performance and scalability.  



## 🛠️ System Architecture  

Invent-Mag follows the **MVC (Model-View-Controller)** design pattern for a clean separation of concerns, making it scalable and maintainable.  

📌 **Backend:** Laravel 11 (PHP Framework)  
📌 **Frontend:** Blade Templates with Tailwind CSS  
📌 **Database:** MySQL  
📌 **Authentication:** Laravel Fortify  
📌 **Storage:** Local & Cloud-based file management  



## 📊 Core Features & Functionality  



### 1️⃣ **User Roles & Permissions**  
| Role    | Capabilities |
|---------|-------------|
| Admin   | Full access: Manage users, products, stock, orders, and reports. |



### 2️⃣ **Stock Management**  
- **Add New Products** – Define product name, SKU, category, and supplier.  
- **Stock Adjustment** – Record inflow and outflow transactions with reason codes.  
- **Threshold Alerts** – Low stock notifications for better inventory planning.  



### 3️⃣ **Purchase Orders**  
- **Create Purchase Orders** – Select suppliers, items, and quantities.  
- **Order Approval Process** – Managers approve before processing.  
- **Receive & Reconcile Stock** – Update inventory upon order completion.  



### 4️⃣ **Reporting & Analytics**  
- **Stock Reports** – View real-time inventory levels.  
- **Transaction History** – Log every stock movement.  
- **Export to CSV/PDF** – Generate downloadable reports.  



## 🔒 Security & Authentication  

✅ **Laravel Fortify** – Implements secure authentication (Login, Register, Forgot Password).  
✅ **CSRF Protection** – All forms secured with Cross-Site Request Forgery protection.  
✅ **Role-Based Access** – Different user permissions enforced at the database level.  
✅ **Input Validation** – Prevents SQL injection and cross-site scripting attacks.  



## 📩 Support & Contributions  

If you encounter any issues, feel free to submit an issue on GitHub or contact me directly.  
Pull requests are welcome! 😊  



## 📜 License  
Invent-Mag is open-source and licensed under the **MIT License**.  
