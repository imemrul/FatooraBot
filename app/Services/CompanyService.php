<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CompanyService
{
    public function updateProfile(Company $company, array $data): Company
    {
        $company->update($data);

        if (!$company->isOnboarded() && $this->isProfileComplete($company)) {
            $company->update(['onboarded_at' => now()]);
        }

        return $company->fresh();
    }

    public function uploadLogo(Company $company, UploadedFile $file): Company
    {
        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }

        $path = $file->store('logos/' . $company->id, 'public');

        $company->update(['logo_path' => $path]);

        return $company->fresh();
    }

    public function deleteLogo(Company $company): Company
    {
        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
            $company->update(['logo_path' => null]);
        }

        return $company->fresh();
    }

    private function isProfileComplete(Company $company): bool
    {
        return $company->name
            && $company->phone
            && $company->address
            && $company->city;
    }
}
