<?php

namespace App\Models\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::queueAudit($model, 'created', [], $model->getAuditableAttributes());
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();
            if (empty($dirty)) return;

            $old = array_intersect_key($model->getOriginal(), $dirty);
            $new = $dirty;

            $exclude = $model->auditExclude ?? [];
            $old = array_diff_key($old, array_flip($exclude));
            $new = array_diff_key($new, array_flip($exclude));

            if (empty($new)) return;

            static::queueAudit($model, 'updated', $old, $new);
        });

        static::deleted(function ($model) {
            static::queueAudit($model, 'deleted', $model->getAuditableAttributes(), []);
        });
    }

    protected static function queueAudit($model, string $action, array $old, array $new): void
    {
        $user = Auth::user();
        $companyId = $model->company_id ?? $user?->company_id;

        if (!$companyId) return;

        $data = [
            'company_id' => $companyId,
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'action' => $action,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'auditable_label' => $model->getAuditLabel(),
            'old_values' => empty($old) ? null : $old,
            'new_values' => empty($new) ? null : $new,
            'ip_address' => Request::ip(),
            'user_agent' => mb_substr(Request::userAgent() ?? '', 0, 255),
            'created_at' => now(),
        ];

        // Queue the write to avoid blocking the request
        dispatch(function () use ($data) {
            AuditLog::create($data);
        })->afterCommit();
    }

    public function getAuditLabel(): string
    {
        return $this->invoice_number
            ?? $this->order_number
            ?? $this->name
            ?? $this->sku
            ?? "#{$this->getKey()}";
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    private function getAuditableAttributes(): array
    {
        $exclude = array_merge(
            $this->auditExclude ?? [],
            ['created_at', 'updated_at', 'remember_token', 'password']
        );

        return array_diff_key($this->attributesToArray(), array_flip($exclude));
    }
}
