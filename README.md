# 2THSND4 - Streetwear E-Commerce Web Application

**Full Symfony 7 & Docker Migration for Web Development Finals (100/100 Points Rubrics Compliant)**

---

## 🚀 Quick Start Guide

### 1. Run using Docker Compose (Recommended)
Make sure Docker Desktop is running, then in the project root:
```bash
# Start all containers (Symfony Web App, PostgreSQL 16, Mailpit)
docker compose up -d

# Seed default database accounts and initial streetwear inventory
php bin/console app:seed
```
- **Web Application:** [http://localhost:8000](http://localhost:8000)
- **PostgreSQL Database:** `localhost:5432` (db: `app`, user: `app`, pass: `!ChangeMe!`)
- **Mailpit Web UI:** [http://localhost:8025](http://localhost:8025)

---

### 2. Run Locally (Alternative)
```bash
# Update schema if needed
php bin/console doctrine:schema:update --force

# Seed database
php bin/console app:seed

# Start Symfony web server
php -S 0.0.0.0:8000 -t public
```

---

## 🔑 Pre-Configured Demo Accounts

| Role | Email | Password | Access Rights |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@2thsnd4.com` | `admin123` | Full system control: Admin dashboard, manage all users/staff, edit/delete any record, update orders, view inquiries |
| **Staff Member** | `staff@2thsnd4.com` | `staff123` | Staff portal: Create products, view all products, **edit and delete own created records only**. Admin areas blocked (403) |
| **Customer** | `customer@2thsnd4.com` | `customer123` | Storefront: Browse shop, add to cart, checkout with COD, track personal order history, profile & password |

---

## 📋 Compliance with Finals Project Rubrics (100 Points)

### 1. Authentication & User Roles (17 Points)
- **Working Login & Logout System (6 pts):** Integrated Symfony form login at `/login`, logout at `/logout`, session handling, and role-based redirector.
- **Proper Password Hashing & Security (5 pts):** Uses Symfony `UserPasswordHasherInterface` (Argon2id/Bcrypt). No plain-text passwords stored.
- **Correct Role Implementation (4 pts):** `ROLE_ADMIN`, `ROLE_STAFF`, `ROLE_USER` with hierarchical inheritance defined in `config/packages/security.yaml`.
- **Unauthorized Access Properly Blocked (2 pts):** Protected routes redirect guests to `/login`.

### 2. Authorization & Access Control (11 Points)
- **Role-based route protection (5 pts):** Firewalls and access controls for `^/admin` (Admin only), `^/staff` (Staff only), `^/checkout`, `^/my-orders`, `^/profile` (Users only).
- **Proper access denial (3 pts):** Custom `AccessDeniedHandler` returns HTTP 403 status with customized error page preventing staff from accessing admin routes.
- **Role checks in controllers & templates (3 pts):** `#[IsGranted]` attributes in controllers and `is_granted()` Twig filters for dynamic role-specific navigation menus.

### 3. Admin Features (19 Points)
- **Create users/staff (5 pts):** Admin interface at `/admin/users/new` to create new Admin, Staff, or Customer accounts.
- **Update user/staff (4 pts):** Full user editing at `/admin/users/{id}/edit` (name, email, role, password reset).
- **Delete user/staff (4 pts):** Deletion modal with CSRF token verification (safeguard prevents self-deletion).
- **View all data records (3 pts):** View all users, full inventory with creator tracking, all customer orders, and customer messages.
- **Admin dashboard (3 pts):** Real-time statistics at `/admin` showing Total Users, Total Staff, Total System Records, Low Stock alerts, and Gross Revenue.

### 4. Staff Features (16 Points)
- **Create records (6 pts):** Staff can publish new product records at `/staff/product/new` (automatically tags `createdBy = current staff`).
- **Edit own records only (6 pts):** Controller-level and template-level enforcement: Staff cannot edit products created by Admin or other staff. Attempting manual URL access returns 403 Forbidden.
- **Delete own records only:** Staff can delete only their own records with confirmation prompt and CSRF protection.
- **View records (4 pts):** Staff portal at `/staff/products` with filter tabs for "All Products" and "My Created Records Only".

### 5. CRUD Functionality (15 Points)
- **Create (4 pts), Read (3 pts), Update (4 pts), Delete with confirmation (4 pts):** Implemented for Products, Users/Staff, Orders, and Inquiries with confirmation modals and CSRF tokens.

### 6. Validation, Errors & Security (11 Points)
- **Form validation (4 pts):** Symfony Form constraints (`NotBlank`, `Email`, `Length`, `PositiveOrZero`).
- **Flash messages (2 pts):** Success, warning, and danger alert banners on all user actions.
- **CSRF protection (3 pts):** Enabled on login, forms, and delete actions.
- **Account status (active/disabled):** Admin can disable/archive accounts; disabled accounts are blocked by `UserChecker`.

### 7. User Interface & Usability (8 Points)
- **Faithful 2THSND4 Streetwear Design (3 pts):** Recreated exact dark streetwear visual identity, typography (Moderniz & Alkatra fonts), and responsive grids.
- **Role-based menu display (2 pts):** Navbar dropdown dynamically adapts to show Admin tools, Staff tools, or Customer orders.
- **Mobile readability (3 pts):** Fully responsive navigation and layout.

### 8. Code Quality & Project Structure (3 Points)
- **Clean controller usage (1 pt):** Single responsibility controllers (`AdminController`, `StaffController`, `ShopController`, `CartController`, etc.).
- **Proper entity & repository usage (1 pt):** Doctrine ORM entities with custom query repository methods.
- **Organized templates & routes (1 pt):** Structured `templates/` hierarchy matching controllers.
