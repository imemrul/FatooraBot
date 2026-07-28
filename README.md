# FatooraBot

<p align="center">
  <img src="public/FatooraBot.png" width="140" alt="FatooraBot Logo">
</p>

<h1 align="center">FatooraBot</h1>

<p align="center">
Modern Multi-Tenant Invoice & Business Platform built with Laravel 13 + Vue 3
</p>

<p align="center">
<img src="https://img.shields.io/badge/Laravel-13-red">
<img src="https://img.shields.io/badge/PHP-8.3+-777BB4">
<img src="https://img.shields.io/badge/Vue-3-42b883">
<img src="https://img.shields.io/badge/TailwindCSS-4-38BDF8">
<img src="https://img.shields.io/badge/License-MIT-green">
</p>

---

## 🚀 Overview

FatooraBot is a modern API-first, multi-tenant invoicing platform for SMEs, SaaS providers, and enterprise customers.

### Features

- Multi-Tenant Architecture
- Customer Management
- Product Management
- Inventory
- Invoice Generation
- PDF Export
- API Tokens
- Laravel Sanctum
- Role & Permission
- Admin Panel
- Queue Jobs
- Notifications
- AI Ready
- REST API
- Audit Logs

## 🏗 Tech Stack

- Laravel 13
- PHP 8.3+
- Vue 3
- Tailwind CSS
- Pinia
- Vite
- MySQL
- Redis
- Sanctum
- Docker

## 📂 Project Structure

```text
app/
bootstrap/
config/
database/
public/
resources/
routes/
storage/
tests/
```

## ⚡ Installation

```bash
git clone https://github.com/imemrul/FatooraBot.git
cd FatooraBot

composer install

cp .env.example .env

php artisan key:generate

php artisan migrate --seed

npm install

npm run build

php artisan serve
```

## 🔐 Authentication

Uses Laravel Sanctum.

```http
Authorization: Bearer YOUR_API_TOKEN
Accept: application/json
```

## 🌐 API

```
/api/v1
```

Resources:

- Products
- Customers
- Inventory
- Invoices
- API Tokens
- Webhooks

## 👑 Admin

- Dashboard
- Tenant Management
- User Management
- Plans
- Billing
- Roles
- Permissions

## 🔒 Security

- CSRF
- XSS Protection
- SQL Injection Protection
- Rate Limiting
- Secure API Tokens
- Tenant Isolation

## 🧪 Testing

```bash
php artisan test
composer test
```

## 🚀 Deployment

```bash
composer install --no-dev
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

## 🛣 Roadmap

- POS
- Payment Gateway
- WhatsApp
- OCR
- AI Invoice Assistant
- Mobile App
- Reports
- Analytics

## 🤝 Contributing

```bash
git checkout -b feature/new-feature
git commit -m "feat: add feature"
git push origin feature/new-feature
```

## 📄 License

MIT

## 👨‍💻 Author

**Emrul Hasan Udoy**

- GitHub: https://github.com/imemrul
- LinkedIn: https://linkedin.com/in/imemrul
