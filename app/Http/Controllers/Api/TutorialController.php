<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TutorialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TutorialController extends Controller
{
    public function __construct(private readonly TutorialService $service) {}

    public function progress(Request $request): JsonResponse
    {
        return response()->json($this->service->getUserProgress($request->user()->id));
    }

    public function show(string $key): JsonResponse
    {
        $tutorial = $this->service->getTutorial($key);
        if (!$tutorial) return response()->json(['message' => 'Tutorial not found.'], 404);
        return response()->json(['tutorial' => array_merge(['key' => $key], $tutorial)]);
    }

    public function advance(Request $request, string $key): JsonResponse
    {
        $result = $this->service->advanceStep($request->user()->id, $key);
        if (!$result) return response()->json(['message' => 'Tutorial not found.'], 404);
        return response()->json($result);
    }

    public function reset(Request $request, string $key): JsonResponse
    {
        $this->service->resetProgress($request->user()->id, $key);
        return response()->json(['message' => 'Progress reset.']);
    }

    public function dismissWelcome(Request $request): JsonResponse
    {
        $this->service->markWelcomeSeen($request->user()->id);
        return response()->json(['message' => 'Welcome tour dismissed.']);
    }
}
