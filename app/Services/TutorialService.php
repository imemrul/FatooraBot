<?php

namespace App\Services;

use App\Models\HelpArticle;
use App\Models\TutorialProgress;
use App\Models\User;

class TutorialService
{
    /**
     * All tutorial definitions with steps.
     */
    public static function definitions(): array
    {
        return [
            'welcome_tour' => [
                'title' => 'Welcome to FatooraBot',
                'description' => 'Learn the basics of your dashboard',
                'icon' => '👋',
                'points' => 10,
                'steps' => [
                    ['target' => '#sidebar', 'title' => 'Navigation', 'content' => 'Use the sidebar to navigate between modules. It collapses on smaller screens.', 'position' => 'right'],
                    ['target' => '#global-search', 'title' => 'Global Search', 'content' => 'Search across invoices, customers, products from here. Try typing a customer name.', 'position' => 'bottom'],
                    ['target' => '#notification-bell', 'title' => 'Notifications', 'content' => 'You\'ll see alerts for new payments, overdue invoices, and system updates here.', 'position' => 'bottom'],
                    ['target' => '#dashboard-stats', 'title' => 'Dashboard', 'content' => 'Your key business metrics at a glance — sales, collections, outstanding amounts.', 'position' => 'bottom'],
                ],
            ],
            'create_first_customer' => [
                'title' => 'Add Your First Customer',
                'description' => 'Learn to create and manage customers',
                'icon' => '👥',
                'points' => 10,
                'steps' => [
                    ['title' => 'Go to Customers', 'content' => 'Click "Customers" in the sidebar under Sales & Finance.', 'action' => 'navigate', 'route' => '/clients'],
                    ['title' => 'Click + New Customer', 'content' => 'Click the blue "+ New Customer" button in the top right.', 'action' => 'click', 'target' => '#btn-new-customer'],
                    ['title' => 'Fill Customer Details', 'content' => 'Enter the company name, contact person, email, and phone. TRN is optional but recommended for UAE businesses.'],
                    ['title' => 'Set Credit Terms', 'content' => 'Set payment terms (e.g., 30 days) and credit limit. This helps track overdue invoices automatically.'],
                    ['title' => 'Save', 'content' => 'Click Save. Your first customer is ready! You can now create invoices for them.'],
                ],
            ],
            'create_first_invoice' => [
                'title' => 'Create Your First Invoice',
                'description' => 'Generate a professional invoice in minutes',
                'icon' => '📄',
                'points' => 15,
                'steps' => [
                    ['title' => 'Go to Invoices', 'content' => 'Click "Invoices" in the sidebar.', 'action' => 'navigate', 'route' => '/invoices'],
                    ['title' => 'Click + New Invoice', 'content' => 'Click the "+ New Invoice" button.'],
                    ['title' => 'Select Customer', 'content' => 'Choose a customer from the dropdown. You can search by name.'],
                    ['title' => 'Set Dates', 'content' => 'Issue date is today. Due date is when payment is expected (e.g., 30 days from now).'],
                    ['title' => 'Add Line Items', 'content' => 'Add products/services. Select a product to auto-fill price, or type manually. Add more lines with "+ Add Line".'],
                    ['title' => 'Review & Save', 'content' => 'Check the totals (subtotal + 5% VAT). Click Save to create as draft.'],
                    ['title' => 'Send Invoice', 'content' => 'Click "Send" to mark it as sent. You can also download PDF or share via WhatsApp.'],
                ],
            ],
            'manage_inventory' => [
                'title' => 'Manage Your Inventory',
                'description' => 'Track stock levels across warehouses',
                'icon' => '📦',
                'points' => 10,
                'steps' => [
                    ['title' => 'Go to Products', 'content' => 'Click "Products" in the sidebar to see your product catalog.'],
                    ['title' => 'Add a Product', 'content' => 'Click "+ New Product". Enter name, SKU, price, cost price, and VAT rate.'],
                    ['title' => 'Check Inventory', 'content' => 'Go to "Inventory" page to see stock levels per warehouse.'],
                    ['title' => 'Stock Movement', 'content' => 'Use the Stock Movement form to record stock in, stock out, or transfers between warehouses.'],
                    ['title' => 'Low Stock Alerts', 'content' => 'Products below their threshold appear in alerts. Set thresholds when creating products.'],
                ],
            ],
            'record_payment' => [
                'title' => 'Record a Payment',
                'description' => 'Track partial and full payments',
                'icon' => '💰',
                'points' => 10,
                'steps' => [
                    ['title' => 'Open an Invoice', 'content' => 'Go to Invoices and click on a sent or overdue invoice.'],
                    ['title' => 'Click Record Payment', 'content' => 'Click the "Pay" button to open the payment form.'],
                    ['title' => 'Enter Payment Details', 'content' => 'Enter amount, method (bank/cash/card), date, and reference number.'],
                    ['title' => 'Partial Payments', 'content' => 'You can record partial payments. The invoice status auto-updates when fully paid.'],
                ],
            ],
            'use_whatsapp' => [
                'title' => 'WhatsApp Commands',
                'description' => 'Run your business from WhatsApp',
                'icon' => '📱',
                'points' => 15,
                'steps' => [
                    ['title' => 'Link Your Phone', 'content' => 'Go to Settings → WhatsApp → Link Phone. Enter your WhatsApp number.'],
                    ['title' => 'Say Hello', 'content' => 'Send "hi" or "help" to the FatooraBot number to see all commands.'],
                    ['title' => 'Check Stock', 'content' => 'Send "stock USB" to check stock levels for any product.'],
                    ['title' => 'Who Owes You', 'content' => 'Send "who owes" to see all outstanding balances by customer.'],
                    ['title' => 'Create Invoice', 'content' => 'Send "invoice" then type the order details. Bot will parse and create it.'],
                    ['title' => 'Daily Report', 'content' => 'Send "today" every morning to get your daily business summary.'],
                ],
            ],
            'generate_reports' => [
                'title' => 'Generate Reports',
                'description' => 'Understand your business performance',
                'icon' => '📊',
                'points' => 10,
                'steps' => [
                    ['title' => 'Profit & Loss', 'content' => 'Go to Reports page. Set date range and click Calculate to see revenue vs expenses.'],
                    ['title' => 'Export CSV', 'content' => 'Download invoices, customers, products, or payments as CSV files for Excel.'],
                    ['title' => 'Aging Report', 'content' => 'See who owes you money and for how long — current, 30, 60, 90+ days.'],
                    ['title' => 'VAT Return', 'content' => 'Generate VAT return summary showing output VAT, input VAT, and net payable.'],
                ],
            ],
            'setup_quotations' => [
                'title' => 'Quotations Workflow',
                'description' => 'Quote → Approve → Invoice in one flow',
                'icon' => '📋',
                'points' => 10,
                'steps' => [
                    ['title' => 'Create Quotation', 'content' => 'Go to Quotations → New Quotation. Select customer, add line items, set validity date.'],
                    ['title' => 'Send to Customer', 'content' => 'Click Send to mark it as sent. Share the PDF with your customer.'],
                    ['title' => 'Approve', 'content' => 'When customer agrees, click Approve to confirm the quote.'],
                    ['title' => 'Convert to Invoice', 'content' => 'Click "→ Invoice" to auto-create an invoice from the approved quote. All items carry over.'],
                ],
            ],
        ];
    }

    public function getUserProgress(int $userId): array
    {
        $definitions = self::definitions();
        $progress = TutorialProgress::where('user_id', $userId)->get()->keyBy('tutorial_key');

        $tutorials = [];
        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($definitions as $key => $def) {
            $p = $progress->get($key);
            $totalSteps = count($def['steps']);
            $completed = $p?->completed ?? false;

            $totalPoints += $def['points'];
            if ($completed) $earnedPoints += $def['points'];

            $tutorials[] = [
                'key' => $key,
                'title' => $def['title'],
                'description' => $def['description'],
                'icon' => $def['icon'],
                'points' => $def['points'],
                'total_steps' => $totalSteps,
                'current_step' => $p?->current_step ?? 0,
                'completed' => $completed,
                'completed_at' => $p?->completed_at,
            ];
        }

        return [
            'tutorials' => $tutorials,
            'total_points' => $totalPoints,
            'earned_points' => $earnedPoints,
            'completion_pct' => $totalPoints > 0 ? round($earnedPoints / $totalPoints * 100) : 0,
        ];
    }

    public function getTutorial(string $key): ?array
    {
        return self::definitions()[$key] ?? null;
    }

    public function advanceStep(int $userId, string $key): ?array
    {
        $def = self::definitions()[$key] ?? null;
        if (!$def) return null;

        $progress = TutorialProgress::advance($userId, $key, count($def['steps']));

        return [
            'key' => $key,
            'current_step' => $progress->current_step,
            'total_steps' => count($def['steps']),
            'completed' => $progress->completed,
            'step' => $def['steps'][$progress->current_step - 1] ?? null,
        ];
    }

    public function markWelcomeSeen(int $userId): void
    {
        User::withoutGlobalScopes()->where('id', $userId)->update(['has_seen_welcome_tour' => true]);
    }

    public function resetProgress(int $userId, string $key): void
    {
        TutorialProgress::where('user_id', $userId)->where('tutorial_key', $key)->delete();
    }
}
