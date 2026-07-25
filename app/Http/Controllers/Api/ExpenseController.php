<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(private readonly ExpenseService $service) {}

    public function index(Request $request): JsonResponse
    {
        $expenses = $this->service->listExpenses($request->only('category_id', 'from', 'to', 'search'));
        return response()->json($expenses);
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $expense = $this->service->create(
            $request->safe()->except('receipt'),
            $request->user()->id,
            $request->file('receipt'),
        );
        return response()->json(['expense' => $expense, 'message' => 'Expense recorded.'], 201);
    }

    public function update(StoreExpenseRequest $request, Expense $expense): JsonResponse
    {
        $expense = $this->service->update($expense, $request->safe()->except('receipt'), $request->file('receipt'));
        return response()->json(['expense' => $expense, 'message' => 'Expense updated.']);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $this->service->delete($expense);
        return response()->json(['message' => 'Expense deleted.']);
    }

    public function summary(Request $request): JsonResponse
    {
        $summary = $this->service->getSummary(
            $request->user()->company_id,
            $request->query('from'),
            $request->query('to'),
        );
        return response()->json($summary);
    }

    // ── Categories ──

    public function categories(): JsonResponse
    {
        return response()->json(['categories' => $this->service->listCategories()]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'color' => 'nullable|string|max:7']);
        $cat = $this->service->createCategory($data);
        return response()->json(['category' => $cat], 201);
    }

    public function updateCategory(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'color' => 'nullable|string|max:7']);
        $cat = $this->service->updateCategory($expenseCategory, $data);
        return response()->json(['category' => $cat]);
    }

    public function destroyCategory(ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->service->deleteCategory($expenseCategory);
        return response()->json(['message' => 'Category deleted.']);
    }
}
