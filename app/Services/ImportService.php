<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ImportLog;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ImportService
{
    public function importCsv(string $type, UploadedFile $file): ImportLog
    {
        $log = ImportLog::create([
            'company_id' => Auth::user()->company_id,
            'imported_by' => Auth::id(),
            'type' => $type,
            'filename' => $file->getClientOriginalName(),
            'status' => 'processing',
        ]);

        $rows = $this->parseCsv($file);
        $log->update(['total_rows' => count($rows)]);

        $errors = [];
        $success = 0;

        DB::transaction(function () use ($type, $rows, &$success, &$errors) {
            foreach ($rows as $i => $row) {
                try {
                    match ($type) {
                        'clients' => $this->importClient($row),
                        'products' => $this->importProduct($row),
                        default => throw new \RuntimeException("Unknown type: {$type}"),
                    };
                    $success++;
                } catch (\Throwable $e) {
                    $errors[] = ['row' => $i + 2, 'error' => $e->getMessage()];
                }
            }
        });

        $log->update([
            'success_count' => $success,
            'error_count' => count($errors),
            'errors' => $errors ?: null,
            'status' => count($errors) === count($rows) ? 'failed' : 'completed',
        ]);

        return $log;
    }

    public function listLogs(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return ImportLog::with('importer:id,name')->latest()->paginate(20);
    }

    private function importClient(array $row): void
    {
        Client::create([
            'company_id' => Auth::user()->company_id,
            'name' => $row['name'] ?? throw new \RuntimeException('Name required'),
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'contact_person' => $row['contact_person'] ?? null,
            'tax_registration_number' => $row['trn'] ?? $row['tax_registration_number'] ?? null,
            'address' => $row['address'] ?? null,
            'city' => $row['city'] ?? null,
            'country' => $row['country'] ?? 'AE',
            'credit_limit' => $row['credit_limit'] ?? 0,
            'payment_terms' => $row['payment_terms'] ?? 30,
        ]);
    }

    private function importProduct(array $row): void
    {
        Product::create([
            'company_id' => Auth::user()->company_id,
            'name' => $row['name'] ?? throw new \RuntimeException('Name required'),
            'sku' => $row['sku'] ?? null,
            'barcode' => $row['barcode'] ?? null,
            'unit_price' => $row['unit_price'] ?? $row['price'] ?? 0,
            'cost_price' => $row['cost_price'] ?? $row['cost'] ?? 0,
            'vat_rate' => $row['vat_rate'] ?? 5,
            'unit' => $row['unit'] ?? 'pcs',
            'description' => $row['description'] ?? null,
            'low_stock_threshold' => $row['low_stock_threshold'] ?? 10,
        ]);
    }

    private function parseCsv(UploadedFile $file): array
    {
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');
        $headers = fgetcsv($handle);
        $headers = array_map(fn ($h) => strtolower(trim($h)), $headers);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === count($headers)) {
                $rows[] = array_combine($headers, $data);
            }
        }
        fclose($handle);
        return $rows;
    }
}
