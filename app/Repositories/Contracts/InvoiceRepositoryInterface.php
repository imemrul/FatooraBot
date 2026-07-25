<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface InvoiceRepositoryInterface extends BaseRepositoryInterface
{
    public function paginateWithRelations(int $perPage = 15): LengthAwarePaginator;
}
