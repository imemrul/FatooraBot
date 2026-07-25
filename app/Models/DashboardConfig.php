<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardConfig extends Model
{
    protected $fillable = ['user_id', 'widgets'];

    protected function casts(): array
    {
        return ['widgets' => 'array'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public static function defaults(): array
    {
        return [
            ['id' => 'stats', 'label' => 'Key Stats', 'visible' => true, 'order' => 0],
            ['id' => 'revenue_trend', 'label' => 'Revenue Trend', 'visible' => true, 'order' => 1],
            ['id' => 'collection_trend', 'label' => 'Collection Trend', 'visible' => true, 'order' => 2],
            ['id' => 'top_customers', 'label' => 'Top Customers', 'visible' => true, 'order' => 3],
            ['id' => 'low_stock', 'label' => 'Low Stock Alerts', 'visible' => true, 'order' => 4],
            ['id' => 'overdue', 'label' => 'Overdue Invoices', 'visible' => true, 'order' => 5],
            ['id' => 'aging', 'label' => 'Aging Summary', 'visible' => true, 'order' => 6],
        ];
    }
}
