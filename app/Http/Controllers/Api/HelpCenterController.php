<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpCenterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = HelpArticle::published()->orderBy('sort_order');

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('q')) {
            $query->search($request->q);
        }

        return response()->json(['articles' => $query->get()]);
    }

    public function show(string $slug): JsonResponse
    {
        $article = HelpArticle::published()->where('slug', $slug)->first();
        if (!$article) return response()->json(['message' => 'Article not found.'], 404);
        return response()->json(['article' => $article]);
    }

    public function categories(): JsonResponse
    {
        $categories = HelpArticle::published()
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        $labels = [
            'getting_started' => '🚀 Getting Started',
            'invoices' => '📄 Invoices & Payments',
            'inventory' => '📦 Inventory',
            'reports' => '📊 Reports',
            'whatsapp' => '📱 WhatsApp Bot',
        ];

        return response()->json([
            'categories' => $categories->map(fn ($c) => [
                'key' => $c->category,
                'label' => $labels[$c->category] ?? ucfirst($c->category),
                'count' => $c->count,
            ]),
        ]);
    }
}
