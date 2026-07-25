<?php

use App\Http\Controllers\Api\ActivityTimelineController;
use App\Http\Controllers\Api\AgingReportController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BatchPaymentController;
use App\Http\Controllers\Api\ClientContactController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientPortalController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CreditControlController;
use App\Http\Controllers\Api\CreditNoteController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\CustomFieldController;
use App\Http\Controllers\Api\DashboardConfigController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeliveryNoteController;
use App\Http\Controllers\Api\DocumentNumberController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\GlobalSearchController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\InventoryAdjustmentController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvoiceTemplateController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderParserController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\PaymentReminderController;
use App\Http\Controllers\Api\ProductBundleController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfitMarginController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\RecurringInvoiceController;
use App\Http\Controllers\Api\SalesOrderController;
use App\Http\Controllers\Api\ScheduledReportController;
use App\Http\Controllers\Api\TaxReportController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\WarehouseTransferController;
use App\Http\Controllers\Api\ZatcaController;
use Illuminate\Support\Facades\Route;

// ── Public auth routes ──
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// ── Public: Client Portal (no auth) ──
Route::get('/portal/invoice/{token}', [ClientPortalController::class, 'viewInvoice']);
Route::get('/portal/invoice/{token}/pdf', [ClientPortalController::class, 'downloadPdf']);
Route::get('/portal/statement/{token}', [ClientPortalController::class, 'statement']);

// ── Public: WhatsApp Webhook (Meta Cloud API) ──
use App\Http\Controllers\Api\WhatsAppWebhookController;
Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verify']);
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'receive']);

// ── Authenticated (no tenant/onboarding check) ──
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/email/verify', [AuthController::class, 'verifyEmail']);
    Route::post('/email/resend', [AuthController::class, 'resendVerification']);

    Route::get('/company', [CompanyController::class, 'show']);
    Route::put('/company', [CompanyController::class, 'update']);
    Route::post('/company/logo', [CompanyController::class, 'uploadLogo']);
    Route::delete('/company/logo', [CompanyController::class, 'deleteLogo']);
});

// ── Tenant-scoped + onboarded routes ──
Route::middleware(['auth:sanctum', 'tenant', 'onboarded'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Audit Logs (owner only via Gate::before)
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/stats', [AuditLogController::class, 'stats']);
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show']);

    // Customers
    Route::middleware('permission:view_customers')->group(function () {
        Route::get('/clients', [ClientController::class, 'index']);
        Route::get('/clients/all', [ClientController::class, 'all']);
        Route::get('/clients/{client}', [ClientController::class, 'show']);
        Route::get('/clients/{client}/ledger', [ClientController::class, 'ledger']);
        Route::get('/clients/{client}/statement', [ClientController::class, 'statement']);
    });
    Route::middleware('permission:manage_customers')->group(function () {
        Route::post('/clients', [ClientController::class, 'store']);
        Route::put('/clients/{client}', [ClientController::class, 'update']);
        Route::delete('/clients/{client}', [ClientController::class, 'destroy']);
    });

    // Products
    Route::middleware('permission:view_inventory')->group(function () {
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/all', [ProductController::class, 'all']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
    });
    Route::middleware('permission:manage_inventory')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });

    // Warehouses
    Route::middleware('permission:view_inventory')->group(function () {
        Route::get('/warehouses', [WarehouseController::class, 'index']);
        Route::get('/warehouses/all', [WarehouseController::class, 'all']);
        Route::get('/warehouses/{warehouse}', [WarehouseController::class, 'show']);
    });
    Route::middleware('permission:manage_inventory')->group(function () {
        Route::post('/warehouses', [WarehouseController::class, 'store']);
        Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update']);
        Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy']);
    });

    // Inventory
    Route::middleware('permission:view_inventory')->group(function () {
        Route::get('/inventory/levels', [InventoryController::class, 'levels']);
        Route::get('/inventory/movements', [InventoryController::class, 'movements']);
        Route::get('/inventory/alerts', [InventoryController::class, 'alerts']);
    });
    Route::middleware('permission:manage_inventory')->group(function () {
        Route::post('/inventory/move', [InventoryController::class, 'move']);
    });

    // Invoices
    Route::middleware('permission:view_invoices')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf']);

        // Lightweight list for select dropdowns
        Route::get('/invoices/all', [InvoiceController::class, 'all']);

        // Payment reminders
        Route::get('/reminders/dashboard', [PaymentReminderController::class, 'dashboard']);
        Route::get('/invoices/{invoice}/whatsapp-reminder', [PaymentReminderController::class, 'whatsappMessage']);
    });
    Route::middleware('permission:manage_invoices')->group(function () {
        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update']);
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);
        Route::patch('/invoices/{invoice}/status', [InvoiceController::class, 'markAs']);
        Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send']);
        Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment']);

        // Reminder actions
        Route::post('/invoices/{invoice}/remind-email', [PaymentReminderController::class, 'sendEmail']);
        Route::post('/invoices/{invoice}/remind-whatsapp', [PaymentReminderController::class, 'logWhatsApp']);

        Route::post('/orders/parse', [OrderParserController::class, 'parse']);
        Route::post('/orders/confirm', [OrderParserController::class, 'confirm']);
    });

    // Sales Orders
    Route::middleware('permission:manage_invoices')->group(function () {
        Route::get('/sales-orders', [SalesOrderController::class, 'index']);
        Route::post('/sales-orders', [SalesOrderController::class, 'store']);
        Route::get('/sales-orders/{salesOrder}', [SalesOrderController::class, 'show']);
        Route::put('/sales-orders/{salesOrder}', [SalesOrderController::class, 'update']);
        Route::delete('/sales-orders/{salesOrder}', [SalesOrderController::class, 'destroy']);
        Route::post('/sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm']);
        Route::post('/sales-orders/{salesOrder}/deliver', [SalesOrderController::class, 'deliver']);
        Route::post('/sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel']);
        Route::post('/sales-orders/{salesOrder}/convert-to-invoice', [SalesOrderController::class, 'convertToInvoice']);
    });

    // ── Tier 2: Team Management (owner only) ──
    Route::middleware('permission:manage_users')->group(function () {
        Route::get('/team', [TeamController::class, 'index']);
        Route::post('/team', [TeamController::class, 'invite']);
        Route::patch('/team/{id}/role', [TeamController::class, 'updateRole']);
        Route::post('/team/{id}/toggle-status', [TeamController::class, 'toggleStatus']);
        Route::delete('/team/{id}', [TeamController::class, 'destroy']);
    });

    // ── Tier 2: Expenses ──
    Route::middleware('permission:manage_invoices')->group(function () {
        Route::get('/expenses', [ExpenseController::class, 'index']);
        Route::post('/expenses', [ExpenseController::class, 'store']);
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update']);
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);
        Route::get('/expenses/summary', [ExpenseController::class, 'summary']);
        Route::get('/expense-categories', [ExpenseController::class, 'categories']);
        Route::post('/expense-categories', [ExpenseController::class, 'storeCategory']);
        Route::put('/expense-categories/{expenseCategory}', [ExpenseController::class, 'updateCategory']);
        Route::delete('/expense-categories/{expenseCategory}', [ExpenseController::class, 'destroyCategory']);
    });

    // ── Tier 2: Recurring Invoices ──
    Route::middleware('permission:manage_invoices')->group(function () {
        Route::get('/recurring-invoices', [RecurringInvoiceController::class, 'index']);
        Route::post('/recurring-invoices', [RecurringInvoiceController::class, 'store']);
        Route::get('/recurring-invoices/{recurringInvoice}', [RecurringInvoiceController::class, 'show']);
        Route::put('/recurring-invoices/{recurringInvoice}', [RecurringInvoiceController::class, 'update']);
        Route::delete('/recurring-invoices/{recurringInvoice}', [RecurringInvoiceController::class, 'destroy']);
        Route::post('/recurring-invoices/{recurringInvoice}/toggle', [RecurringInvoiceController::class, 'toggleActive']);
    });

    // ── Tier 2: Currencies ──
    Route::get('/currencies', [CurrencyController::class, 'index']);
    Route::post('/currencies', [CurrencyController::class, 'store']);
    Route::put('/currencies/{currency}', [CurrencyController::class, 'update']);
    Route::delete('/currencies/{currency}', [CurrencyController::class, 'destroy']);
    Route::post('/currencies/convert', [CurrencyController::class, 'convert']);
    Route::post('/currencies/seed-defaults', [CurrencyController::class, 'seedDefaults']);

    // ── Tier 2: Exports & Reports ──
    Route::middleware('permission:view_reports')->group(function () {
        Route::get('/export/invoices', [ExportController::class, 'invoices']);
        Route::get('/export/clients', [ExportController::class, 'clients']);
        Route::get('/export/products', [ExportController::class, 'products']);
        Route::get('/export/payments', [ExportController::class, 'payments']);
        Route::get('/export/expenses', [ExportController::class, 'expenses']);
        Route::get('/reports/profit-loss', [ExportController::class, 'profitLoss']);
    });

    // ── Tier 2: Notifications ──
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // ── Tier 3: Credit Notes ──
    Route::middleware('permission:manage_invoices')->group(function () {
        Route::get('/credit-notes', [CreditNoteController::class, 'index']);
        Route::post('/credit-notes', [CreditNoteController::class, 'store']);
        Route::get('/credit-notes/{creditNote}', [CreditNoteController::class, 'show']);
        Route::post('/credit-notes/{creditNote}/issue', [CreditNoteController::class, 'issue']);
        Route::post('/credit-notes/{creditNote}/apply', [CreditNoteController::class, 'apply']);
        Route::post('/credit-notes/{creditNote}/cancel', [CreditNoteController::class, 'cancel']);
    });

    // ── Tier 3: Delivery Notes ──
    Route::middleware('permission:manage_invoices')->group(function () {
        Route::get('/delivery-notes', [DeliveryNoteController::class, 'index']);
        Route::post('/delivery-notes', [DeliveryNoteController::class, 'store']);
        Route::get('/delivery-notes/{deliveryNote}', [DeliveryNoteController::class, 'show']);
        Route::post('/delivery-notes/{deliveryNote}/in-transit', [DeliveryNoteController::class, 'markInTransit']);
        Route::post('/delivery-notes/{deliveryNote}/delivered', [DeliveryNoteController::class, 'markDelivered']);
        Route::post('/delivery-notes/{deliveryNote}/cancel', [DeliveryNoteController::class, 'cancel']);
        Route::delete('/delivery-notes/{deliveryNote}', [DeliveryNoteController::class, 'destroy']);
        Route::post('/sales-orders/{salesOrder}/delivery-note', [DeliveryNoteController::class, 'createFromOrder']);
    });

    // ── Tier 3: Quotations ──
    Route::middleware('permission:manage_invoices')->group(function () {
        Route::get('/quotations', [QuotationController::class, 'index']);
        Route::post('/quotations', [QuotationController::class, 'store']);
        Route::get('/quotations/{quotation}', [QuotationController::class, 'show']);
        Route::put('/quotations/{quotation}', [QuotationController::class, 'update']);
        Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy']);
        Route::post('/quotations/{quotation}/send', [QuotationController::class, 'send']);
        Route::post('/quotations/{quotation}/approve', [QuotationController::class, 'approve']);
        Route::post('/quotations/{quotation}/reject', [QuotationController::class, 'reject']);
        Route::post('/quotations/{quotation}/convert-to-invoice', [QuotationController::class, 'convertToInvoice']);
    });

    // ── Tier 3: Purchase Orders & Suppliers ──
    Route::middleware('permission:manage_inventory')->group(function () {
        Route::get('/suppliers', [PurchaseOrderController::class, 'suppliers']);
        Route::post('/suppliers', [PurchaseOrderController::class, 'storeSupplier']);
        Route::put('/suppliers/{supplier}', [PurchaseOrderController::class, 'updateSupplier']);
        Route::delete('/suppliers/{supplier}', [PurchaseOrderController::class, 'destroySupplier']);

        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index']);
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store']);
        Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show']);
        Route::put('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update']);
        Route::delete('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy']);
        Route::post('/purchase-orders/{purchaseOrder}/send', [PurchaseOrderController::class, 'send']);
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);
    });

    // ── Tier 3: Tax Reports ──
    Route::middleware('permission:view_reports')->group(function () {
        Route::get('/reports/vat-return', [TaxReportController::class, 'vatReturn']);
    });

    // ── Tier 3: Bulk Invoice Actions ──
    Route::middleware('permission:manage_invoices')->group(function () {
        Route::post('/invoices/bulk-send', [InvoiceController::class, 'bulkSend']);
        Route::post('/invoices/bulk-status', [InvoiceController::class, 'bulkStatus']);
        Route::post('/invoices/bulk-delete', [InvoiceController::class, 'bulkDelete']);
    });

    // ── Tier 3: File Attachments ──
    Route::get('/attachments', [AttachmentController::class, 'index']);
    Route::post('/attachments', [AttachmentController::class, 'store']);
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);

    // ── Tier 4: Client Portal Link Generation ──
    Route::middleware('permission:manage_invoices')->group(function () {
        Route::post('/invoices/{invoice}/portal-link', [ClientPortalController::class, 'generateLink']);
    });

    // ── Tier 4: Inventory Adjustments ──
    Route::middleware('permission:manage_inventory')->group(function () {
        Route::get('/inventory-adjustments', [InventoryAdjustmentController::class, 'index']);
        Route::post('/inventory-adjustments', [InventoryAdjustmentController::class, 'store']);
        Route::get('/inventory-adjustments/{inventoryAdjustment}', [InventoryAdjustmentController::class, 'show']);
        Route::post('/inventory-adjustments/{inventoryAdjustment}/apply', [InventoryAdjustmentController::class, 'apply']);
        Route::delete('/inventory-adjustments/{inventoryAdjustment}', [InventoryAdjustmentController::class, 'destroy']);
    });

    // ── Tier 4: Product Categories ──
    Route::get('/product-categories', [ProductCategoryController::class, 'index']);
    Route::get('/product-categories/all', [ProductCategoryController::class, 'all']);
    Route::middleware('permission:manage_inventory')->group(function () {
        Route::post('/product-categories', [ProductCategoryController::class, 'store']);
        Route::put('/product-categories/{productCategory}', [ProductCategoryController::class, 'update']);
        Route::delete('/product-categories/{productCategory}', [ProductCategoryController::class, 'destroy']);
    });

    // ── Tier 4: Payment Methods ──
    Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
    Route::middleware('permission:manage_invoices')->group(function () {
        Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
        Route::put('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update']);
        Route::delete('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy']);
    });

    // ── Tier 4: Invoice Templates ──
    Route::get('/invoice-templates', [InvoiceTemplateController::class, 'index']);
    Route::middleware('permission:manage_invoices')->group(function () {
        Route::post('/invoice-templates', [InvoiceTemplateController::class, 'store']);
        Route::put('/invoice-templates/{invoiceTemplate}', [InvoiceTemplateController::class, 'update']);
        Route::delete('/invoice-templates/{invoiceTemplate}', [InvoiceTemplateController::class, 'destroy']);
    });

    // ── Tier 4: Scheduled Reports ──
    Route::middleware('permission:view_reports')->group(function () {
        Route::get('/scheduled-reports', [ScheduledReportController::class, 'index']);
        Route::post('/scheduled-reports', [ScheduledReportController::class, 'store']);
        Route::put('/scheduled-reports/{scheduledReport}', [ScheduledReportController::class, 'update']);
        Route::delete('/scheduled-reports/{scheduledReport}', [ScheduledReportController::class, 'destroy']);
    });

    // ── Tier 4: Data Import ──
    Route::middleware('permission:manage_customers')->group(function () {
        Route::post('/import', [ImportController::class, 'import']);
        Route::get('/import/logs', [ImportController::class, 'logs']);
    });

    // ── Tier 5: Aging Report ──
    Route::middleware('permission:view_reports')->group(function () {
        Route::get('/reports/aging', [AgingReportController::class, 'index']);
    });

    // ── Tier 5: Client Contacts ──
    Route::middleware('permission:view_customers')->group(function () {
        Route::get('/clients/{clientId}/contacts', [ClientContactController::class, 'index']);
    });
    Route::middleware('permission:manage_customers')->group(function () {
        Route::post('/clients/{clientId}/contacts', [ClientContactController::class, 'store']);
        Route::put('/clients/{clientId}/contacts/{contact}', [ClientContactController::class, 'update']);
        Route::delete('/clients/{clientId}/contacts/{contact}', [ClientContactController::class, 'destroy']);
    });

    // ── Tier 5: Product Bundles ──
    Route::middleware('permission:view_inventory')->group(function () {
        Route::get('/product-bundles', [ProductBundleController::class, 'index']);
        Route::get('/product-bundles/{productBundle}/expand', [ProductBundleController::class, 'expand']);
    });
    Route::middleware('permission:manage_inventory')->group(function () {
        Route::post('/product-bundles', [ProductBundleController::class, 'store']);
        Route::put('/product-bundles/{productBundle}', [ProductBundleController::class, 'update']);
        Route::delete('/product-bundles/{productBundle}', [ProductBundleController::class, 'destroy']);
    });

    // ── Tier 5: Custom Fields ──
    Route::get('/custom-fields/definitions', [CustomFieldController::class, 'definitions']);
    Route::post('/custom-fields/definitions', [CustomFieldController::class, 'storeDefinition']);
    Route::put('/custom-fields/definitions/{definition}', [CustomFieldController::class, 'updateDefinition']);
    Route::delete('/custom-fields/definitions/{definition}', [CustomFieldController::class, 'destroyDefinition']);
    Route::get('/custom-fields/values', [CustomFieldController::class, 'values']);
    Route::post('/custom-fields/values', [CustomFieldController::class, 'setValues']);

    // ── Tier 5: Dashboard Config ──
    Route::get('/dashboard-config', [DashboardConfigController::class, 'show']);
    Route::put('/dashboard-config', [DashboardConfigController::class, 'update']);

    // ── Tier 5: Global Search ──
    Route::get('/search', [GlobalSearchController::class, 'search']);

    // ── Tier 6: ZATCA E-Invoicing ──
    Route::middleware('permission:manage_invoices')->group(function () {
        Route::post('/invoices/{invoice}/zatca/generate', [ZatcaController::class, 'generate']);
        Route::get('/invoices/{invoice}/zatca/xml', [ZatcaController::class, 'xml']);
    });

    // ── Tier 6: Warehouse Transfers ──
    Route::middleware('permission:manage_inventory')->group(function () {
        Route::post('/warehouse-transfers', [WarehouseTransferController::class, 'store']);
        Route::get('/warehouse-transfers/history', [WarehouseTransferController::class, 'history']);
    });

    // ── Tier 6: Profit Margin Analysis ──
    Route::middleware('permission:view_reports')->group(function () {
        Route::get('/reports/margins/products', [ProfitMarginController::class, 'products']);
        Route::get('/reports/margins/invoices', [ProfitMarginController::class, 'invoices']);
    });

    // ── Tier 6: Client Credit Control ──
    Route::middleware('permission:view_customers')->group(function () {
        Route::get('/clients/{client}/credit-check', [CreditControlController::class, 'check']);
        Route::get('/credit-control/at-risk', [CreditControlController::class, 'atRisk']);
    });
    Route::middleware('permission:manage_customers')->group(function () {
        Route::post('/clients/{client}/credit-hold', [CreditControlController::class, 'toggleHold']);
    });

    // ── Tier 6: Batch Payments ──
    Route::middleware('permission:manage_invoices')->group(function () {
        Route::get('/batch-payments', [BatchPaymentController::class, 'index']);
        Route::post('/batch-payments', [BatchPaymentController::class, 'store']);
        Route::get('/clients/{clientId}/unpaid-invoices', [BatchPaymentController::class, 'unpaidInvoices']);
    });

    // ── Tier 6: Document Numbering Config ──
    Route::get('/document-numbers', [DocumentNumberController::class, 'index']);
    Route::put('/document-numbers/{documentNumberConfig}', [DocumentNumberController::class, 'update']);
    Route::post('/document-numbers/seed-defaults', [DocumentNumberController::class, 'seedDefaults']);
    Route::get('/document-numbers/preview', [DocumentNumberController::class, 'preview']);

    // ── Tier 6: Activity Timeline ──
    Route::get('/activity-timeline', [ActivityTimelineController::class, 'index']);

    // ── Tier 7: WhatsApp Phone Linking ──
    Route::get('/whatsapp/phones', [\App\Http\Controllers\Api\WhatsAppPhoneController::class, 'index']);
    Route::post('/whatsapp/phones', [\App\Http\Controllers\Api\WhatsAppPhoneController::class, 'link']);
    Route::delete('/whatsapp/phones/{whatsappPhone}', [\App\Http\Controllers\Api\WhatsAppPhoneController::class, 'unlink']);
    Route::post('/whatsapp/test', [WhatsAppWebhookController::class, 'test']);

    // ── Tier 8: Tutorials & Help Center ──
    Route::get('/tutorials/progress', [\App\Http\Controllers\Api\TutorialController::class, 'progress']);
    Route::get('/tutorials/{key}', [\App\Http\Controllers\Api\TutorialController::class, 'show']);
    Route::post('/tutorials/{key}/advance', [\App\Http\Controllers\Api\TutorialController::class, 'advance']);
    Route::post('/tutorials/{key}/reset', [\App\Http\Controllers\Api\TutorialController::class, 'reset']);
    Route::post('/tutorials/dismiss-welcome', [\App\Http\Controllers\Api\TutorialController::class, 'dismissWelcome']);

    Route::get('/help', [\App\Http\Controllers\Api\HelpCenterController::class, 'index']);
    Route::get('/help/categories', [\App\Http\Controllers\Api\HelpCenterController::class, 'categories']);
    Route::get('/help/{slug}', [\App\Http\Controllers\Api\HelpCenterController::class, 'show']);
});
