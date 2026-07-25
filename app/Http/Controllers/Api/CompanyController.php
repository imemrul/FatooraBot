<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Requests\UploadLogoRequest;
use App\Http\Resources\CompanyResource;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(private readonly CompanyService $service) {}

    public function show(Request $request): CompanyResource
    {
        return new CompanyResource($request->user()->company);
    }

    public function update(UpdateCompanyRequest $request): JsonResponse
    {
        $company = $this->service->updateProfile(
            $request->user()->company,
            $request->validated(),
        );

        return response()->json([
            'company' => new CompanyResource($company),
            'message' => $company->isOnboarded()
                ? 'Company profile updated.'
                : 'Company profile saved. Complete all required fields to finish setup.',
        ]);
    }

    public function uploadLogo(UploadLogoRequest $request): JsonResponse
    {
        $company = $this->service->uploadLogo(
            $request->user()->company,
            $request->file('logo'),
        );

        return response()->json([
            'company' => new CompanyResource($company),
            'message' => 'Logo uploaded successfully.',
        ]);
    }

    public function deleteLogo(Request $request): JsonResponse
    {
        if (!$request->user()->isOwner()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $company = $this->service->deleteLogo($request->user()->company);

        return response()->json([
            'company' => new CompanyResource($company),
            'message' => 'Logo removed.',
        ]);
    }
}
