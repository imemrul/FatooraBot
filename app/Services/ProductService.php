<?php

namespace App\Services;

use App\DTOs\ProductDTO;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function find(int $id): Product
    {
        return $this->repository->findOrFail($id);
    }

    public function create(ProductDTO $dto): Product
    {
        return $this->repository->create($dto->toArray());
    }

    public function update(Product $product, ProductDTO $dto): Product
    {
        return $this->repository->update($product, $dto->toArray());
    }

    public function delete(Product $product): bool
    {
        return $this->repository->delete($product);
    }
}
