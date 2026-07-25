# Progress: TradeOS UAE

## Completed
- [x] Laravel 13 project scaffolded
- [x] PostgreSQL + Redis configured (.env)
- [x] Sanctum + Spatie Permission + DomPDF installed
- [x] Multi-tenant architecture (company_id + TenantScope + BelongsToTenant trait)
- [x] Database migrations (companies, users, clients, products, invoices, invoice_items, invoice_payments, warehouses, inventory_levels, stock_movements)
- [x] Eloquent models with relationships and casts
- [x] User implements MustVerifyEmail
- [x] DTOs (ClientDTO, ProductDTO, InvoiceDTO, InvoiceItemDTO, StockMovementDTO)
- [x] Repository pattern (interfaces + Eloquent implementations)
- [x] Service layer (AuthService, ClientService, ProductService, InvoiceService, CompanyService, InventoryService)
- [x] AuthService: register, login, logout, forgot password, reset password, email verification
- [x] CompanyService: updateProfile, uploadLogo, deleteLogo, auto-onboarding
- [x] InventoryService: stockIn, stockOut, transfer, getLevels, getMovements, getAlerts
- [x] ClientService: CRUD, search, getLedger, getStatement with running balance
- [x] InvoiceService: CRUD, send, recordPayment (partial/full), generatePdf, auto-status sync
- [x] Form requests (validation + authorization) — 16 request classes
- [x] API resources (response transformers) — 12 resource classes
- [x] Policies (InvoicePolicy, ClientPolicy, ProductPolicy)
- [x] Events/Listeners (InvoiceCreated → LogInvoiceCreated)
- [x] Middleware (EnsureTenantContext, CheckPermission, CheckRole, EnsureOnboarded)
- [x] API routes (auth + company + products + warehouses + inventory + invoices + payments + pdf + clients + ledger + statement)
- [x] RBAC seeder (owner, accountant, warehouse_manager, salesman)
- [x] 10 permissions with view/manage granularity
- [x] Gate::before owner bypass
- [x] User model: canAccess(), canAccessAny(), isOwner() helpers
- [x] Factories (Company, User, Client, Product, Warehouse)
- [x] Feature tests — AuthTest (16), RbacTest (25), ClientTest (14), InvoiceTest (16), CompanyOnboardingTest (15), InventoryTest (18)
- [x] Vue 3 + Pinia + Vue Router + Tailwind CSS frontend
- [x] SPA pages (Login, Register, ForgotPassword, ResetPassword, Dashboard, Invoices, Clients, CustomerDetail, Products, Inventory, Unauthorized, CompanySetup, Settings)
- [x] Invoices page: full CRUD, status filter tabs, send/pay/cancel actions, payment modal, PDF download, create/edit with dynamic line items
- [x] Products page with full CRUD modal, SKU/barcode/cost fields, stock level display, search
- [x] Inventory page with stock movement form, levels/movements tabs, low stock + out of stock alerts
- [x] Clients page with CRUD modal, CRM fields, search, balance/overdue display
- [x] CustomerDetail page with Overview, Ledger (running balance), Invoices tabs
- [x] 3-step onboarding wizard (Business Info → Legal & Tax → Logo & Review)
- [x] Company settings page with logo management
- [x] Role-aware sidebar with permission-based filtering
- [x] Email verification banner in AppLayout
- [x] Pinia stores (auth, invoices with payment/pdf/send)
- [x] Vue usePermission composable + v-can/v-role directives
- [x] Router permission guards + onboarding guard with 403 page
- [x] PDF invoice generation with bilingual Arabic+English template
- [x] QR code on PDF invoices
- [x] TRN display on PDF (company + client)
- [x] Invoice payment tracking (invoice_payments table)
- [x] Partial payment support with auto-status sync

## Not Started
- [ ] ZATCA e-invoicing compliance (Phase 2)
- [ ] Nginx production config
- [ ] CI/CD pipeline

## Tier 2 Completed
- [x] User/Team Management — invite, list, update role, toggle status, remove members
- [x] Email Notifications — InvoiceSentMail, PaymentReceivedMail, OverdueReminderMail with Blade templates
- [x] Expense Tracking — categories, CRUD, receipt upload, summary by category/month, profit/loss
- [x] Recurring Invoices — CRUD templates, weekly/monthly/quarterly/yearly, cron auto-generation, pause/resume
- [x] Multi-currency Support — currency CRUD, exchange rates, converter, seed defaults (AED/USD/EUR/GBP/SAR/INR)
- [x] Export/Reports — CSV export for invoices/clients/products/payments/expenses, profit/loss report
- [x] Activity Feed/Notifications — in-app notifications, bell with unread count, 30s polling, mark read/all

## Tier 3 Completed
- [x] Credit Notes / Refunds — issue against invoices, draft→issued→applied workflow, auto-adjust invoice balance
- [x] Delivery Notes — create from sales orders or standalone, pending→in_transit→delivered lifecycle
- [x] Quotations / Proforma — full CRUD, draft→sent→approved→converted workflow, convert to invoice
- [x] Purchase Orders — supplier CRUD, PO CRUD, send, receive goods with auto stock-in to warehouse
- [x] Tax Reports — VAT return summary with output/input VAT, credit note adjustments, net payable/refundable
- [x] Bulk Actions — bulk send, bulk status update, bulk delete for invoices
- [x] File Attachments — polymorphic attachments for any model, upload/list/delete via API

## Tier 4 Completed
- [x] Client Portal — public invoice view via token link, PDF download, client statement, 90-day expiry
- [x] Inventory Adjustments — stock count/audit, reason tracking, draft→applied with auto stock-in/out
- [x] Product Categories — category tree with parent/child, product assignment, CRUD
- [x] Payment Methods — configurable per company (bank/cash/card/online), bank details, default method
- [x] Invoice Templates — layout/color/font config, logo/QR/bilingual toggles, header/footer text
- [x] Scheduled Reports — weekly/monthly auto-email, 5 report types, activate/deactivate
- [x] Data Import — CSV import for clients & products, row-level error tracking, import logs

## Tier 5 Completed
- [x] Aging Report — receivables aging (current/30/60/90/120+ days), per-client breakdown, top 20 debtors
- [x] Client Contacts — multiple contacts per client, primary flag, role (decision_maker/accounts/operations)
- [x] Product Bundles — bundle products together, auto-expand to invoice line items, custom bundle price
- [x] Invoice Line Discounts — per-line discount field (fixed/percent) added to invoice_items
- [x] Custom Fields — user-defined fields on invoices/clients/products, text/number/date/select/boolean types
- [x] Dashboard Widget Config — per-user widget visibility/order, defaults, save/restore
- [x] Global Search — search across invoices, clients, products, quotations, sales orders from top bar

## Tier 6 Completed
- [x] ZATCA Phase 1 E-Invoicing — UUID per invoice, TLV QR code, UBL 2.1 XML generation, SHA-256 hash
- [x] Multi-Warehouse Transfers — transfer endpoint with history, uses existing InventoryService
- [x] Profit Margin Analysis — per-product margins (cost vs price), per-invoice margins with summary
- [x] Client Credit Control — credit check API, credit hold toggle, at-risk clients list, can_invoice flag
- [x] Batch Payment Processing — one payment against multiple invoices, auto-allocate, auto-sync paid amounts
- [x] Document Numbering Config — custom prefix/suffix/padding/separator per document type, year toggle, preview
- [x] Activity Timeline — polymorphic per-entity activity log with user/action/metadata, query by subject

## Tier 7 Completed
- [x] WhatsApp Cloud API Client — send text, buttons, documents via Meta Graph API, mock mode for dev
- [x] Intent Parser — regex-based NLP mapping 20+ patterns to commands, button reply parsing
- [x] Invoice Commands — create (free-text → confirm flow), list, send, PDF, status with interactive buttons
- [x] Stock Commands — check by product name/SKU, low stock alerts with per-warehouse breakdown
- [x] Payment Commands — who owes (outstanding by client), send reminder with WhatsApp deep links
- [x] Report Commands — today summary, monthly P&L, aging report with top debtors
- [x] Webhook Controller — Meta verification + message receiver, async queue dispatch
- [x] Phone Linking — link/unlink WhatsApp numbers to user accounts, tenant identification by phone
- [x] Conversation State — multi-step flows with 15min expiry, context persistence
- [x] Test Endpoint — /api/whatsapp/test for local development without Meta API

## Tier 8 Completed
- [x] Tutorial System — 8 interactive tutorials with step-by-step guides (welcome, customers, invoices, inventory, payments, WhatsApp, reports, quotations)
- [x] Progress Tracker — gamified points system (10-15 pts per tutorial), completion percentage, per-step progress bars
- [x] Help Center — searchable knowledge base with 10 articles across 5 categories, markdown rendering
- [x] Tutorial Spotlight — Vue component with overlay, step dots, target highlighting, back/next/skip
- [x] Contextual Tooltips — HelpTooltip component (? icon) for inline help anywhere in the app
- [x] WhatsApp Tutorial Bot — "learn" command shows tutorial menu, "learn invoice" sends step-by-step lesson
- [x] Help Article Seeder — 10 comprehensive articles covering all features with markdown + tags
