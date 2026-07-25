<?php

namespace App\Providers;

use App\Events\InvoiceCreated;
use App\Listeners\LogInvoiceCreated;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Policies\ClientPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\ProductPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);

        // Events
        Event::listen(InvoiceCreated::class, LogInvoiceCreated::class);

        // Policies
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);

        // Owner bypasses all gates
        Gate::before(function ($user, $ability) {
            if ($user->isSuperAdmin() || $user->isOwner()) {
                return true;
            }
        });
    }
}
