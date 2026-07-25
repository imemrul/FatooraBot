<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttachmentController extends Controller
{
    public function __construct(private readonly AttachmentService $service) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate(['type' => 'required|string', 'id' => 'required|integer']);
        $attachments = $this->service->list($request->type, $request->id);
        return response()->json(['attachments' => $attachments]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string', 'id' => 'required|integer',
            'file' => 'required|file|max:10240',
        ]);
        $attachment = $this->service->upload($request->type, $request->id, $request->file('file'));
        return response()->json(['attachment' => $attachment, 'message' => 'File uploaded.'], 201);
    }

    public function destroy(Attachment $attachment): JsonResponse
    {
        $this->service->delete($attachment);
        return response()->json(['message' => 'Attachment deleted.']);
    }
}
