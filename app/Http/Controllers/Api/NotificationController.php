<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $service) {}

    public function index(Request $request): JsonResponse
    {
        $notifications = $this->service->list($request->user()->id, $request->boolean('unread'));
        return response()->json($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['count' => $this->service->unreadCount($request->user()->id)]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $this->service->markAsRead($id, $request->user()->id);
        return response()->json(['message' => 'Marked as read.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->service->markAllRead($request->user()->id);
        return response()->json(['message' => "{$count} notifications marked as read."]);
    }
}
