<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\PaymentReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentReminderController extends Controller
{
    public function __construct(private readonly PaymentReminderService $service) {}

    public function dashboard(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->getDashboardData($request->user()->company_id)
        );
    }

    public function sendEmail(Invoice $invoice): JsonResponse
    {
        $reminder = $this->service->sendEmailReminder($invoice);

        return response()->json([
            'message' => $reminder->status === 'sent'
                ? 'Email reminder sent.'
                : 'Failed to send — client has no email.',
            'status' => $reminder->status,
        ]);
    }

    public function whatsappMessage(Invoice $invoice): JsonResponse
    {
        $message = $this->service->generateWhatsAppMessage($invoice);

        return response()->json([
            'message' => $message,
            'phone' => $invoice->client->phone,
            'whatsapp_url' => $invoice->client->phone
                ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $invoice->client->phone) . '?text=' . urlencode($message)
                : null,
        ]);
    }

    public function logWhatsApp(Invoice $invoice): JsonResponse
    {
        $reminder = $this->service->logWhatsAppReminder($invoice);

        return response()->json([
            'message' => 'WhatsApp reminder logged.',
            'reminder_id' => $reminder->id,
        ]);
    }
}
