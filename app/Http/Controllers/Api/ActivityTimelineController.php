<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityTimeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityTimelineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'subject_type' => 'required|string',
            'subject_id' => 'required|integer',
        ]);

        $typeMap = [
            'invoice' => \App\Models\Invoice::class,
            'client' => \App\Models\Client::class,
            'product' => \App\Models\Product::class,
            'quotation' => \App\Models\Quotation::class,
            'sales_order' => \App\Models\SalesOrder::class,
            'purchase_order' => \App\Models\PurchaseOrder::class,
        ];

        $type = $typeMap[$request->subject_type] ?? $request->subject_type;

        return response()->json([
            'timeline' => ActivityTimeline::forSubject($type, $request->subject_id),
        ]);
    }
}
