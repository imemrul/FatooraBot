<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

class CurrencyService
{
    public function list(): Collection
    {
        return Currency::orderBy('code')->get();
    }

    public function create(array $data): Currency
    {
        return Currency::create($data);
    }

    public function update(Currency $currency, array $data): Currency
    {
        $currency->update($data);
        return $currency->fresh();
    }

    public function delete(Currency $currency): void
    {
        $currency->delete();
    }

    public function seedDefaults(int $companyId): void
    {
        $defaults = [
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'د.إ', 'rate_to_base' => 1],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'rate_to_base' => 3.6725],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'rate_to_base' => 4.0200],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'rate_to_base' => 4.6500],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => '﷼', 'rate_to_base' => 0.9793],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'rate_to_base' => 0.0437],
        ];

        foreach ($defaults as $c) {
            Currency::firstOrCreate(
                ['company_id' => $companyId, 'code' => $c['code']],
                array_merge($c, ['company_id' => $companyId])
            );
        }
    }

    public function convert(float $amount, string $fromCode, string $toCode, int $companyId): float
    {
        if ($fromCode === $toCode) return $amount;

        $from = Currency::where('company_id', $companyId)->where('code', $fromCode)->firstOrFail();
        $to = Currency::where('company_id', $companyId)->where('code', $toCode)->firstOrFail();

        $baseAmount = $from->convertToBase($amount);
        return $to->convertFromBase($baseAmount);
    }
}
