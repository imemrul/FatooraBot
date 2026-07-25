<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __construct(private readonly ImportService $service) {}

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:clients,products',
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $log = $this->service->importCsv($request->type, $request->file('file'));

        return response()->json([
            'import' => $log,
            'message' => "Imported {$log->success_count} of {$log->total_rows} rows. {$log->error_count} errors.",
        ]);
    }

    public function logs(): JsonResponse
    {
        return response()->json($this->service->listLogs());
    }
}
