<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DashboardConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardConfigController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $config = DashboardConfig::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['widgets' => DashboardConfig::defaults()],
        );
        return response()->json(['config' => $config]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate(['widgets' => 'required|array']);
        $config = DashboardConfig::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['widgets' => $request->widgets],
        );
        return response()->json(['config' => $config]);
    }
}
