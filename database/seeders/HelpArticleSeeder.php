<?php

namespace Database\Seeders;

use App\Models\HelpArticle;
use Illuminate\Database\Seeder;

class HelpArticleSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            // Getting Started
            ['slug' => 'what-is-fatoorabot', 'title' => 'What is FatooraBot?', 'category' => 'getting_started', 'sort_order' => 1,
                'summary' => 'FatooraBot is a complete business management platform for UAE companies.',
                'content' => "# What is FatooraBot?\n\nFatooraBot is an all-in-one business management platform designed for UAE companies. It helps you:\n\n- **Create & send invoices** with VAT compliance\n- **Track payments** and outstanding balances\n- **Manage inventory** across multiple warehouses\n- **Generate reports** (P&L, aging, VAT returns)\n- **Run your business from WhatsApp** with bot commands\n\n## Who is it for?\n\n- Small & medium businesses in UAE\n- Trading companies\n- Service providers\n- Freelancers\n\n## Key Features\n\n1. Multi-tenant (each company has isolated data)\n2. Role-based access (owner, accountant, warehouse manager, salesman)\n3. Arabic + English bilingual invoices\n4. ZATCA e-invoicing ready\n5. WhatsApp automation",
                'tags' => ['overview', 'introduction', 'features']],

            ['slug' => 'first-time-setup', 'title' => 'First Time Setup Guide', 'category' => 'getting_started', 'sort_order' => 2,
                'summary' => 'Complete your company setup in 3 easy steps.',
                'content' => "# First Time Setup\n\n## Step 1: Business Information\n- Company name\n- Phone number\n- Address & city\n\n## Step 2: Legal & Tax\n- Trade license number\n- Tax Registration Number (TRN)\n- These appear on your invoices\n\n## Step 3: Logo & Review\n- Upload your company logo (appears on invoices & PDFs)\n- Review all information\n- Click Complete Setup\n\n## After Setup\n1. Add your first customer\n2. Add your products\n3. Create your first invoice\n\n> 💡 **Tip**: You can update all these details later from Settings.",
                'tags' => ['setup', 'onboarding', 'company']],

            ['slug' => 'understanding-roles', 'title' => 'User Roles & Permissions', 'category' => 'getting_started', 'sort_order' => 3,
                'summary' => 'Learn about the 4 roles and what each can do.',
                'content' => "# User Roles\n\n## 👑 Owner\n- Full access to everything\n- Can manage team members\n- Can change company settings\n- Can view audit logs\n\n## 📊 Accountant\n- Create & manage invoices\n- Manage customers\n- View inventory (read-only)\n- Generate reports\n\n## 📦 Warehouse Manager\n- Full inventory management\n- Stock in/out/transfer\n- View invoices & customers (read-only)\n\n## 💼 Salesman\n- Create invoices & quotations\n- Manage customers\n- View inventory (read-only)\n\n> 💡 **Tip**: Owners can invite team members from Settings → Team.",
                'tags' => ['roles', 'permissions', 'team', 'access']],

            // Invoices
            ['slug' => 'creating-invoices', 'title' => 'How to Create an Invoice', 'category' => 'invoices', 'sort_order' => 1,
                'summary' => 'Step-by-step guide to creating your first invoice.',
                'content' => "# Creating an Invoice\n\n1. Go to **Invoices** page\n2. Click **+ New Invoice**\n3. Select a **Customer** from the dropdown\n4. Set **Issue Date** and **Due Date**\n5. Add **Line Items**:\n   - Select a product (auto-fills price)\n   - Or type description and price manually\n   - VAT (5%) is calculated automatically\n6. Click **Save** (creates as Draft)\n7. Click **Send** to mark as sent\n\n## Invoice Statuses\n- 📝 **Draft** — Not yet sent\n- 📤 **Sent** — Sent to customer\n- 🔴 **Overdue** — Past due date, unpaid\n- ✅ **Paid** — Fully paid\n- ❌ **Cancelled** — Voided\n\n## Tips\n- Download PDF anytime\n- Record partial payments\n- Send payment reminders for overdue invoices",
                'tags' => ['invoice', 'create', 'billing']],

            ['slug' => 'recording-payments', 'title' => 'Recording Payments', 'category' => 'invoices', 'sort_order' => 2,
                'summary' => 'Track partial and full payments against invoices.',
                'content' => "# Recording Payments\n\n1. Open an invoice (click on it from the list)\n2. Click the **Pay** button\n3. Enter:\n   - **Amount** (can be partial)\n   - **Method** (bank transfer, cash, card, cheque)\n   - **Date** of payment\n   - **Reference** number (optional)\n4. Click **Record Payment**\n\n## Partial Payments\nYou can record multiple partial payments. The invoice automatically:\n- Updates the paid amount\n- Shows remaining balance\n- Changes to **Paid** status when fully paid\n\n## Batch Payments\nFor one payment covering multiple invoices:\n1. Use the Batch Payment feature\n2. Select customer → see all unpaid invoices\n3. Allocate amounts to each invoice",
                'tags' => ['payment', 'partial', 'batch', 'record']],

            // Inventory
            ['slug' => 'managing-products', 'title' => 'Managing Products', 'category' => 'inventory', 'sort_order' => 1,
                'summary' => 'Add products with SKU, pricing, and stock thresholds.',
                'content' => "# Managing Products\n\n## Adding a Product\n1. Go to **Products** page\n2. Click **+ New Product**\n3. Fill in:\n   - **Name** — Product name\n   - **SKU** — Unique code (e.g., USB-C-65W)\n   - **Barcode** — Optional\n   - **Unit Price** — Selling price\n   - **Cost Price** — Your purchase cost (for margin reports)\n   - **VAT Rate** — Usually 5% in UAE\n   - **Low Stock Threshold** — Alert when stock drops below this\n\n## Stock Levels\n- View stock per warehouse on the Inventory page\n- Stock updates automatically when:\n  - You receive a Purchase Order\n  - An inventory adjustment is applied\n  - A transfer between warehouses is done",
                'tags' => ['product', 'sku', 'stock', 'inventory']],

            // Payments & Reports
            ['slug' => 'understanding-reports', 'title' => 'Understanding Reports', 'category' => 'reports', 'sort_order' => 1,
                'summary' => 'Profit & Loss, Aging, VAT Return — explained simply.',
                'content' => "# Reports Guide\n\n## Profit & Loss\nShows your **revenue minus expenses** for any date range.\n- Revenue = total of non-draft, non-cancelled invoices\n- Expenses = total from Expenses module\n- Profit = Revenue - Expenses\n\n## Aging Report\nShows **who owes you money and for how long**:\n- 🟢 Current — not yet due\n- 🟡 1-30 days overdue\n- 🟠 31-60 days overdue\n- 🔴 61-90 days overdue\n- ⛔ 90+ days overdue\n\n## VAT Return\nFor your quarterly VAT filing:\n- **Output VAT** — VAT you charged on sales\n- **Input VAT** — VAT you paid on purchases\n- **Net** — What you owe (or are owed) to the government\n\n## CSV Exports\nDownload any data as CSV for Excel:\n- Invoices, Customers, Products, Payments, Expenses",
                'tags' => ['report', 'profit', 'loss', 'aging', 'vat', 'export']],

            // WhatsApp
            ['slug' => 'whatsapp-setup', 'title' => 'Setting Up WhatsApp Bot', 'category' => 'whatsapp', 'sort_order' => 1,
                'summary' => 'Link your phone and start using WhatsApp commands.',
                'content' => "# WhatsApp Bot Setup\n\n## Step 1: Link Your Phone\n1. Go to **Settings** in the app\n2. Find **WhatsApp** section\n3. Enter your WhatsApp phone number\n4. Click **Link**\n\n## Step 2: Start Chatting\nSend any of these commands:\n\n| Command | What it does |\n|---------|-------------|\n| `help` | Show all commands |\n| `today` | Today's sales summary |\n| `who owes` | Outstanding balances |\n| `stock USB` | Check stock for a product |\n| `invoice` | Create a new invoice |\n| `invoices` | List recent invoices |\n| `aging` | Aging report |\n| `monthly` | Monthly P&L |\n\n## Tips\n- Commands are case-insensitive\n- You can use short forms: `inv` = `invoice`\n- Type `cancel` anytime to abort a multi-step flow\n- Each team member can link their own phone",
                'tags' => ['whatsapp', 'bot', 'commands', 'setup']],

            ['slug' => 'whatsapp-invoice-creation', 'title' => 'Creating Invoices via WhatsApp', 'category' => 'whatsapp', 'sort_order' => 2,
                'summary' => 'Create invoices by just texting your order details.',
                'content' => "# WhatsApp Invoice Creation\n\n## Quick Method\nJust send the order as natural text:\n\n```\ninvoice Al Futtaim 50 USB chargers 100 HDMI cables\n```\n\nThe bot will:\n1. Find the customer \"Al Futtaim\"\n2. Match products by name\n3. Show you a preview with prices\n4. Ask you to Confirm or Cancel\n\n## Step-by-Step Method\n1. Send `invoice`\n2. Bot asks for order details\n3. Type: customer name + products + quantities\n4. Review the preview\n5. Tap ✅ Confirm\n\n## After Creation\nThe bot shows buttons:\n- 📤 **Send** — Mark as sent\n- 📄 **PDF** — Get the PDF document\n\n> 💡 **Tip**: The bot uses fuzzy matching, so \"USB charger\" will find \"USB-C Charger 65W\".",
                'tags' => ['whatsapp', 'invoice', 'create', 'order']],

            // Quotations
            ['slug' => 'quotation-workflow', 'title' => 'Quotation to Invoice Workflow', 'category' => 'invoices', 'sort_order' => 3,
                'summary' => 'Create quotes, get approval, convert to invoices automatically.',
                'content' => "# Quotation Workflow\n\n## The Flow\n```\nDraft → Send → Approve → Convert to Invoice\n```\n\n## Steps\n1. **Create Quotation** — Add customer, line items, validity date\n2. **Send** — Share PDF with customer for review\n3. **Approve** — When customer agrees, mark as approved\n4. **Convert** — Click \"→ Invoice\" to auto-create an invoice\n\n## What Carries Over\n- All line items (products, quantities, prices)\n- Customer details\n- Notes and terms\n- Discount amount\n\n## Tips\n- Set a validity date (e.g., 30 days)\n- Expired quotes show as \"Expired\" status\n- You can reject quotes that don't proceed\n- Each quote gets a unique number (QT-0001-000001)",
                'tags' => ['quotation', 'quote', 'proforma', 'convert']],
        ];

        foreach ($articles as $article) {
            HelpArticle::updateOrCreate(['slug' => $article['slug']], $article);
        }
    }
}
