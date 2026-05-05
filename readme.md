# 🏢 Site Management SaaS Backend (CI4)

Production-ready, multi-tenant, API-first backend system for apartment/site/building management.

---

## 🚀 Overview

This project is a **full-featured SaaS backend** built with **CodeIgniter 4**, designed for managing residential complexes, buildings, and facilities.

It provides a scalable architecture with **multi-tenancy**, **RBAC**, **audit logging**, and a **fully tested domain layer**.

---

## ⚙️ Core Features

* 🔐 **JWT Authentication**
* 🏢 **Multi-tenant architecture (company_id scoped)**
* 👥 **RBAC (Role-Based Access Control)**
* 🧾 **Audit Logging (old/new values)**
* 🧠 **Service Layer Architecture**
* 🔄 **Transaction-safe operations**
* 🧪 **Full PHPUnit Test Suite (300+ tests)**
* 📦 **Docker-ready environment**
* 📊 **OpenAPI-ready structure**

---

## 🧩 Modules

### Core Domains

* Site / Block / Floor / Unit
* Resident / Occupancy
* Due (Debt) Management
* Payment Processing
* Deposit Tracking

### Operations

* Service Requests / Work Orders
* Asset & Maintenance
* Visitor & Security
* Staff / Shift / Task Management
* Common Area Reservations

### Communication

* Notifications
* Announcements

### Governance

* Meetings & Decisions
* Legal / Enforcement

### System

* Document Management
* Meter / Consumption Tracking

---

## 🏗️ Architecture

```text
Controller → Service → Validator → Model
```

* Controllers: Thin layer (HTTP handling)
* Services: Business logic
* Validators: Input validation rules
* Models: Database interaction

---

## 🔐 Security & Integrity

* JWT-based authentication
* Permission-based authorization
* Tenant isolation (company_id)
* Audit trail for all critical operations
* Defensive validation & exception handling

---

## 🧪 Testing

* ✅ 300+ PHPUnit tests
* ✅ Feature + Unit coverage
* ✅ Deterministic test database
* ✅ Migration-safe testing strategy

Run tests:

```bash
cd backend
composer test
```

---

## 🐳 Setup

### Requirements

* PHP 8.2+
* MySQL 8+
* Composer

### Installation

```bash
git clone https://github.com/Y-usu-F/site-management-api.git
cd site-management-api/backend

composer install
cp env .env
php spark migrate
```

---

## 🔧 Development

Run local server:

```bash
php spark serve
```

---

## 🗂️ Project Structure

```text
backend/
 ├── app/
 │   ├── Controllers/
 │   ├── Services/
 │   ├── Models/
 │   ├── Validators/
 │   └── Database/Migrations/
 ├── tests/
 ├── writable/
 └── public/
```

---

## 🧠 Design Principles

* API-first design
* Domain-driven modularization
* Idempotent migrations
* Test-driven stabilization
* Clean separation of concerns

---

## 📌 Status

```text
✔ Core system stable
✔ Test suite passing
✔ Schema & migration stabilized
✔ Ready for production extension
```

---

## 🚫 Notes

* Legacy database analysis was used only for schema gap identification.
* Data import from legacy systems is intentionally out of scope.

---

## 📦 Roadmap (Optional)

* Frontend integration (Vue / React / Flutter)
* Billing & subscription module
* Multi-language support
* Real-time notifications (WebSocket)

---

## 👤 Author

**Yusuf Benli**

---

## ⭐ License

This project is open-source and available under the MIT License.
