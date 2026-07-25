<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdminService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly SuperAdminService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->getPlatformStats());
    }
}
