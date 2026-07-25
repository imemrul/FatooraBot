<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class ExpenseService
{
    public function listExpenses(array $filters = []): LengthAwarePaginator
    {
        $query = Expense::with(['category', 'recorder:id,name'])
            ->latest('expense_date');

        if (!empty($filters['category_id'])) {
            $query->where('expense_category_id', $filters['category_id']);
        }
        if (!empty($filters['from'])) {
            $query->where('expense_date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('expense_date', '<=', $filters['to']);
        }
        if (!empty($filters['search'])) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']);
            $query->where(function ($q) use ($escaped) {
                $q->where('vendor', 'ilike', "%{$escaped}%")
                    ->orWhere('description', 'ilike', "%{$escaped}%");
            });
        }

        return $query->paginate(20);
    }

    public function create(array $data, int $userId, ?UploadedFile $receipt = null): Expense
    {
        $data['recorded_by'] = $userId;

        if ($receipt) {
            $data['receipt_path'] = $receipt->store('receipts/' . auth()->user()->company_id, 'public');
        }

        return Expense::create($data)->load('category');
    }

    public function update(Expense $expense, array $data, ?UploadedFile $receipt = null): Expense
    {
        if ($receipt) {
            if ($expense->receipt_path) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
            $data['receipt_path'] = $receipt->store('receipts/' . $expense->company_id, 'public');
        }

        $expense->update($data);
        return $expense->fresh()->load('category');
    }

    public function delete(Expense $expense): void
    {
        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }
        $expense->delete();
    }

    // ── Categories ──

    public function listCategories(): \Illuminate\Database\Eloquent\Collection
    {
        return ExpenseCategory::withCount('expenses')
            ->withSum('expenses', 'amount')
            ->orderBy('name')
            ->get();
    }

    public function createCategory(array $data): ExpenseCategory
    {
        return ExpenseCategory::create($data);
    }

    public function updateCategory(ExpenseCategory $cat, array $data): ExpenseCategory
    {
        $cat->update($data);
        return $cat->fresh();
    }

    public function deleteCategory(ExpenseCategory $cat): void
    {
        $cat->delete();
    }

    // ── Summary ──

    public function getSummary(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $query = Expense::where('company_id', $companyId);

        if ($from) $query->where('expense_date', '>=', $from);
        if ($to) $query->where('expense_date', '<=', $to);

        $total = (clone $query)->sum('amount');

        $byCategory = (clone $query)
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.name, expense_categories.color, SUM(expenses.amount) as total')
            ->groupBy('expense_categories.name', 'expense_categories.color')
            ->orderByDesc('total')
            ->get();

        $monthly = (clone $query)
            ->selectRaw("TO_CHAR(expense_date, 'YYYY-MM') as month, SUM(amount) as total")
            ->groupByRaw("TO_CHAR(expense_date, 'YYYY-MM')")
            ->orderBy('month')
            ->get();

        return compact('total', 'byCategory', 'monthly');
    }
}
