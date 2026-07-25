<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    private static ?int $apiCompanyId = null;

    public static function setApiCompanyId(int $companyId): void
    {
        self::$apiCompanyId = $companyId;
    }

    public static function clearApiCompanyId(): void
    {
        self::$apiCompanyId = null;
    }

    public function apply(Builder $builder, Model $model): void
    {
        $companyId = $this->resolveCompanyId();

        if ($companyId) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        } else {
            $builder->whereRaw('1 = 0');
        }
    }

    private function resolveCompanyId(): ?int
    {
        if (Auth::check()) {
            return Auth::user()->company_id;
        }

        if (self::$apiCompanyId) {
            return self::$apiCompanyId;
        }

        return null;
    }
}
