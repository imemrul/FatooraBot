# Project Brief: TradeOS UAE (FatooraBot)

## Overview
TradeOS UAE is a production-ready multi-tenant B2B SaaS platform for invoice management, built on clean architecture principles. The platform provides tenant-isolated invoice generation, management, and ZATCA-ready compliance workflows.

## Architecture
- **Pattern**: Clean Architecture with Service Layer, Repository Pattern, DTOs
- **Multi-tenancy**: Single database, tenant isolation via `company_id`
- **Auth**: RBAC with policies/permissions
- **API**: RESTful backend, SPA frontend

## Tech Stack
- **Web Server**: Nginx
- **Backend**: Laravel (PHP 8.2+)
- **Frontend**: Vue 3 + Vite + Pinia + Tailwind CSS
- **Database**: PostgreSQL
- **Cache/Queue**: Redis
- **Workers**: Laravel Queue Workers

## Key Requirements
- [x] Multi-tenant SaaS with company_id isolation
- [x] RBAC permissions system
- [x] REST API backend
- [x] SPA frontend (Vue 3)
- [ ] Invoice CRUD with PDF generation
- [ ] Client/customer management
- [ ] Product/service catalog
- [ ] Dashboard analytics
- [ ] ZATCA e-invoicing compliance (Phase 2)
