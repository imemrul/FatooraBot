<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppPhone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppPhoneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $phones = WhatsAppPhone::where('company_id', $request->user()->company_id)
            ->with('user:id,name,email')
            ->get();

        return response()->json(['phones' => $phones]);
    }

    public function link(Request $request): JsonResponse
    {
        $request->validate(['phone' => 'required|string|max:20']);

        $normalized = WhatsAppPhone::normalize($request->phone);

        $existing = WhatsAppPhone::where('phone', $normalized)->first();
        if ($existing) {
            return response()->json(['message' => 'This phone is already linked.'], 422);
        }

        $wp = WhatsAppPhone::create([
            'company_id' => $request->user()->company_id,
            'user_id' => $request->user()->id,
            'phone' => $normalized,
            'verified_at' => now(),
        ]);

        return response()->json(['phone' => $wp->load('user:id,name'), 'message' => 'Phone linked.'], 201);
    }

    public function unlink(WhatsAppPhone $whatsappPhone): JsonResponse
    {
        $whatsappPhone->delete();
        return response()->json(['message' => 'Phone unlinked.']);
    }
}
