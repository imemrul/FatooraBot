<?php

namespace App\Services\WhatsApp\Commands;

use App\Models\Product;
use App\Models\WhatsAppConversation;
use App\Services\InventoryService;
use App\Services\WhatsApp\WhatsAppApiClient;

class StockCommands
{
    public function __construct(
        private readonly WhatsAppApiClient $api,
        private readonly InventoryService $inventoryService,
    ) {}

    public function check(string $phone, string $query, WhatsAppConversation $conv): void
    {
        if (empty($query) || in_array(strtolower($query), ['stock', 'stock check', 'inventory', 'stock level'])) {
            $this->alerts($phone, $conv);
            return;
        }

        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $query);
        $products = Product::where('company_id', $conv->company_id)
            ->where(fn ($q) => $q->where('name', 'ilike', "%{$escaped}%")->orWhere('sku', 'ilike', "%{$escaped}%"))
            ->with('inventoryLevels.warehouse:id,name')
            ->limit(5)->get();

        if ($products->isEmpty()) {
            $this->api->sendText($phone, "❌ No products found for: {$query}", $conv->company_id);
            return;
        }

        $lines = ["📦 *Stock Levels*\n"];
        foreach ($products as $p) {
            $total = $p->inventoryLevels->sum('quantity');
            $emoji = $total <= 0 ? '🔴' : ($total <= $p->low_stock_threshold ? '🟡' : '🟢');
            $lines[] = "{$emoji} *{$p->name}* ({$p->sku})";
            foreach ($p->inventoryLevels as $level) {
                $lines[] = "   {$level->warehouse->name}: {$level->quantity}";
            }
            $lines[] = "   Total: {$total}";
        }

        $this->api->sendText($phone, implode("\n", $lines), $conv->company_id);
    }

    public function alerts(string $phone, WhatsAppConversation $conv): void
    {
        $lowStock = $this->inventoryService->getLowStockProducts($conv->company_id);

        if ($lowStock->isEmpty()) {
            $this->api->sendText($phone, "✅ All stock levels are healthy!", $conv->company_id);
            return;
        }

        $lines = ["⚠️ *Stock Alerts*\n"];
        foreach ($lowStock->take(15) as $p) {
            $emoji = $p->total_stock <= 0 ? '🔴' : '🟡';
            $lines[] = "{$emoji} {$p->name} — {$p->total_stock} left (threshold: {$p->low_stock_threshold})";
        }

        $this->api->sendText($phone, implode("\n", $lines), $conv->company_id);
    }
}
