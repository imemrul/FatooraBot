# System Patterns: TradeOS UAE

## Architecture: Clean Architecture + Service Layer

```
Request → FormRequest → Controller → Service → Repository → Model
                                        ↓
                                       DTO
                                        ↓
                                   API Resource → Response
```

## Multi-Tenancy Pattern
- Single database, all tenant tables have `company_id` column
- Global scope `TenantScope` auto-filters queries by authenticated user's company
- Middleware `EnsureTenantContext` sets tenant context per request

## RBAC Pattern
- Companies have Roles, Roles have Permissions
- Policies enforce authorization at resource level
- Middleware `CheckPermission` for route-level checks

## Folder Structure
```
app/
├── DTOs/                    # Data Transfer Objects
├── Events/                  # Domain events
├── Http/
│   ├── Controllers/Api/     # API controllers
│   ├── Middleware/           # Tenant, permission middleware
│   ├── Requests/            # Form request validation
│   └── Resources/           # API resources (transformers)
├── Listeners/               # Event listeners
├── Models/                  # Eloquent models with scopes
├── Policies/                # Authorization policies
├── Repositories/
│   ├── Contracts/           # Repository interfaces
│   └── Eloquent/            # Eloquent implementations
├── Scopes/                  # Global query scopes (TenantScope)
└── Services/                # Business logic services

resources/js/
├── components/              # Vue components
├── composables/             # Vue composables
├── layouts/                 # Layout components
├── pages/                   # Page views
├── router/                  # Vue Router
├── stores/                  # Pinia stores
└── utils/                   # Helpers
```
