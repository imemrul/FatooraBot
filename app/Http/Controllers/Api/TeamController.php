<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InviteTeamMemberRequest;
use App\Http\Resources\UserResource;
use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function __construct(private readonly TeamService $service) {}

    public function index(Request $request): JsonResponse
    {
        $members = $this->service->list($request->user()->company_id, $request->query('search'));

        return response()->json([
            'members' => UserResource::collection($members),
            'meta' => ['total' => $members->total(), 'last_page' => $members->lastPage()],
        ]);
    }

    public function invite(InviteTeamMemberRequest $request): JsonResponse
    {
        $user = $this->service->invite(
            $request->user()->company_id,
            $request->validated(),
            $request->user()->id,
        );

        return response()->json(['member' => new UserResource($user), 'message' => 'Team member invited.'], 201);
    }

    public function updateRole(Request $request, int $id): JsonResponse
    {
        $request->validate(['role' => 'required|in:owner,accountant,warehouse_manager,salesman']);

        $user = $this->service->updateRole($request->user()->company_id, $id, $request->role);

        return response()->json(['member' => new UserResource($user), 'message' => 'Role updated.']);
    }

    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $user = $this->service->toggleStatus($request->user()->company_id, $id);

        return response()->json(['member' => new UserResource($user)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($request->user()->id === $id) {
            return response()->json(['message' => 'Cannot remove yourself.'], 422);
        }

        $this->service->remove($request->user()->company_id, $id);

        return response()->json(['message' => 'Team member removed.']);
    }
}
