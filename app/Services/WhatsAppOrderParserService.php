<?php

namespace App\Services;

use App\DTOs\ParsedOrderDTO;
use App\DTOs\ParsedOrderItemDTO;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WhatsAppOrderParserService
{
    private Collection $clients;
    private Collection $products;

    public function parse(string $message): ParsedOrderDTO
    {
        $this->clients = Client::select('id', 'name', 'contact_person')->get();
        $this->products = Product::where('is_active', true)
            ->select('id', 'name', 'sku', 'unit_price', 'vat_rate')
            ->get();

        $cleaned = $this->cleanMessage($message);
        $warnings = [];

        $customerResult = $this->extractCustomer($cleaned);
        $items = $this->extractItems($cleaned);
        $dateResult = $this->extractDeliveryDate($cleaned);

        if (!$customerResult['name']) {
            $warnings[] = 'Could not identify customer name.';
        }
        if (empty($items)) {
            $warnings[] = 'No products or quantities detected.';
        }
        if (!$dateResult['date']) {
            $warnings[] = 'No delivery date detected. Defaulting to today.';
        }

        foreach ($items as $item) {
            if (!$item->product_id) {
                $warnings[] = "Product \"{$item->product_name}\" not found in catalog.";
            }
        }

        return new ParsedOrderDTO(
            raw_message: $message,
            customer_name: $customerResult['name'],
            client_id: $customerResult['id'],
            customer_confidence: $customerResult['confidence'],
            items: $items,
            delivery_date: $dateResult['date'] ?? now()->toDateString(),
            delivery_raw: $dateResult['raw'],
            warnings: $warnings,
        );
    }

    private function cleanMessage(string $message): string
    {
        $message = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $message);
        $message = preg_replace('/\r\n|\r/', "\n", $message);
        $message = preg_replace('/[ \t]+/', ' ', $message);

        return trim($message);
    }

    private function extractCustomer(string $text): array
    {
        $patterns = [
            '/^(?:hi|hello|hey|dear|salam|from|customer|client|order\s+for)[,:\s]+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)/im',
            '/^([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,2})\s+(?:need|want|order|request|would\s+like)/im',
            '/(?:for|from|customer|client)[:\s]+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)/im',
            '/^([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,2})\s*[-–:]/m',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $name = trim($m[1]);
                $match = $this->fuzzyMatchClient($name);

                return [
                    'name' => $match['name'] ?? $name,
                    'id' => $match['id'],
                    'confidence' => $match['confidence'],
                ];
            }
        }

        return ['name' => null, 'id' => null, 'confidence' => 0];
    }

    /**
     * @return ParsedOrderItemDTO[]
     */
    private function extractItems(string $text): array
    {
        $items = [];

        $patterns = [
            // "5 chargers", "10x covers", "3 pcs cable"
            '/(\d+)\s*(?:x|pcs?|pieces?|units?)?\s+([a-z][a-z\s]{1,40}?)(?:\s+and\s+|\s*[,\n&]+\s*|$)/i',
            // "chargers 5", "covers x10"
            '/([a-z][a-z\s]{1,40}?)\s+(?:x|qty:?\s*)?(\d+)(?:\s+and\s+|\s*[,\n&]+\s*|$)/i',
            // "chargers: 5"
            '/([a-z][a-z\s]{1,40?}):\s*(\d+)/i',
        ];

        $seen = [];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

            foreach ($matches as $m) {
                if (count($m) < 3) continue;

                $isQtyFirst = is_numeric(trim($m[1]));
                $qty = (int) trim($isQtyFirst ? $m[1] : $m[2]);
                $productRaw = trim($isQtyFirst ? $m[2] : $m[1]);

                $productRaw = preg_replace('/\b(and|or|also|plus|with|need|want|order|tomorrow|today|next|week|asap|urgent|please|pls|thx|thanks)\b/i', '', $productRaw);
                $productRaw = trim(preg_replace('/\s+/', ' ', $productRaw));

                if (strlen($productRaw) < 2 || $qty < 1 || $qty > 99999) continue;

                $key = strtolower($productRaw) . ':' . $qty;
                if (isset($seen[$key])) continue;
                $seen[$key] = true;

                $match = $this->fuzzyMatchProduct($productRaw);

                $items[] = new ParsedOrderItemDTO(
                    raw_text: $m[0],
                    quantity: $qty,
                    product_name: $match['name'] ?? $productRaw,
                    product_id: $match['id'],
                    unit_price: $match['unit_price'],
                    vat_rate: $match['vat_rate'],
                    confidence: $match['confidence'],
                );
            }
        }

        return $items;
    }

    private function extractDeliveryDate(string $text): array
    {
        $today = Carbon::today();

        $relativeDates = [
            '/\b(today|now|asap)\b/i' => $today,
            '/\b(tomorrow|tmr|tmrw)\b/i' => $today->copy()->addDay(),
            '/\b(day\s+after\s+tomorrow)\b/i' => $today->copy()->addDays(2),
            '/\bnext\s+week\b/i' => $today->copy()->addWeek()->startOfWeek(),
            '/\bin\s+(\d+)\s+days?\b/i' => null,
            '/\bafter\s+(\d+)\s+days?\b/i' => null,
        ];

        foreach ($relativeDates as $pattern => $date) {
            if (preg_match($pattern, $text, $m)) {
                if ($date === null && isset($m[1])) {
                    $date = $today->copy()->addDays((int) $m[1]);
                }

                return [
                    'date' => $date->toDateString(),
                    'raw' => $m[0],
                ];
            }
        }

        // Explicit dates: 15/01/2025, 2025-01-15, Jan 15, 15 Jan
        $datePatterns = [
            '/(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})/' => function ($m) {
                $y = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
                return Carbon::createFromFormat('Y-m-d', "$y-{$m[2]}-{$m[1]}");
            },
            '/(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})/' => function ($m) {
                return Carbon::createFromFormat('Y-m-d', "{$m[1]}-{$m[2]}-{$m[3]}");
            },
            '/(\d{1,2})\s*(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\w*/i' => function ($m) {
                return Carbon::parse("{$m[1]} {$m[2]}");
            },
            '/(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\w*\s*(\d{1,2})/i' => function ($m) {
                return Carbon::parse("{$m[2]} {$m[1]}");
            },
        ];

        foreach ($datePatterns as $pattern => $resolver) {
            if (preg_match($pattern, $text, $m)) {
                try {
                    $date = $resolver($m);
                    if ($date->isPast()) {
                        $date->addYear();
                    }

                    return [
                        'date' => $date->toDateString(),
                        'raw' => $m[0],
                    ];
                } catch (\Exception) {
                    continue;
                }
            }
        }

        return ['date' => null, 'raw' => null];
    }

    private function fuzzyMatchClient(string $name): array
    {
        $best = ['id' => null, 'name' => null, 'confidence' => 0];
        $nameLower = strtolower($name);

        foreach ($this->clients as $client) {
            $score = $this->similarityScore($nameLower, strtolower($client->name));
            $contactScore = $client->contact_person
                ? $this->similarityScore($nameLower, strtolower($client->contact_person))
                : 0;

            $maxScore = max($score, $contactScore);

            if ($maxScore > $best['confidence'] && $maxScore >= 0.4) {
                $best = [
                    'id' => $client->id,
                    'name' => $client->name,
                    'confidence' => round($maxScore, 2),
                ];
            }
        }

        return $best;
    }

    private function fuzzyMatchProduct(string $name): array
    {
        $best = ['id' => null, 'name' => null, 'unit_price' => null, 'vat_rate' => null, 'confidence' => 0];
        $nameLower = strtolower($name);

        foreach ($this->products as $product) {
            $nameScore = $this->similarityScore($nameLower, strtolower($product->name));

            $skuScore = 0;
            if ($product->sku && strtolower($product->sku) === $nameLower) {
                $skuScore = 1.0;
            }

            $containsScore = 0;
            if (str_contains(strtolower($product->name), $nameLower)) {
                $containsScore = strlen($nameLower) / strlen($product->name);
            }
            if (str_contains($nameLower, strtolower($product->name))) {
                $containsScore = max($containsScore, strlen($product->name) / strlen($nameLower));
            }

            $maxScore = max($nameScore, $skuScore, $containsScore);

            if ($maxScore > $best['confidence'] && $maxScore >= 0.35) {
                $best = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'unit_price' => (float) $product->unit_price,
                    'vat_rate' => (float) $product->vat_rate,
                    'confidence' => round($maxScore, 2),
                ];
            }
        }

        return $best;
    }

    private function similarityScore(string $a, string $b): float
    {
        if ($a === $b) return 1.0;

        similar_text($a, $b, $percent);
        $similar = $percent / 100;

        $lev = levenshtein($a, $b);
        $maxLen = max(strlen($a), strlen($b));
        $levScore = $maxLen > 0 ? 1 - ($lev / $maxLen) : 0;

        return ($similar * 0.6) + ($levScore * 0.4);
    }
}
