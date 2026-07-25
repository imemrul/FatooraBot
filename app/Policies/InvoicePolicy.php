<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccess('view_invoices');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id
            && $user->canAccess('view_invoices');
    }

    public function create(User $user): bool
    {
        return $user->canAccess('manage_invoices');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id
            && $user->canAccess('manage_invoices');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->company_id === $invoice->company_id
            && $user->canAccess('manage_invoices')
            && $invoice->status === 'draft';
    }
}
