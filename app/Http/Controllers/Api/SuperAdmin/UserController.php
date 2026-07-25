<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly SuperAdminService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->listAllUsers(
                $request->integer('per_page', 20),
                $request->string('search')->toString() ?: null,
            )
        );
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $user = $this->service->toggleUserStatus($id);

        return response()->json([
            'message' => $user->is_active ? 'User activated.' : 'User deactivated.',
            'is_active' => $user->is_active,
        ]);
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $this->service->resetUserPassword($id, $validated['password']);

        return response()->json(['message' => 'Password reset.']);
    }
}
