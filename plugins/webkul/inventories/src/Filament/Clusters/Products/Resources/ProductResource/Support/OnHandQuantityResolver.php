<?php

namespace Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource\Support;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\ProductQuantity;

class OnHandQuantityResolver
{
    /**
     * @var array<int, float>
     */
    protected array $totals = [];

    public function get(Model $record, mixed $scope = null): float
    {
        $key = $record->getKey();

        if (! array_key_exists($key, $this->totals)) {
            $this->load($this->products($record, $scope));
        }

        return $this->totals[$key] ?? 0.0;
    }

    /**
     * @param  Collection<int, Model>  $products
     */
    protected function load(Collection $products): void
    {
        $ids = $products
            ->map(fn (Model $product): int => $product->getKey())
            ->all();

        $variants = Product::query()
            ->whereIn('parent_id', $ids)
            ->get(['id', 'parent_id']);

        $sums = $this->sumQuantities(array_merge($ids, $variants->pluck('id')->all()));

        $variantsByParent = $variants->groupBy('parent_id');

        foreach ($products as $product) {
            $key = $product->getKey();

            $quantity = $product->is_configurable
                ? $variantsByParent
                    ->get($key, collect())
                    ->sum(fn (Product $variant): float => (float) ($sums[$variant->getKey()] ?? 0))
                : (float) ($sums[$key] ?? 0);

            $rounding = $product->uom?->rounding;

            $this->totals[$key] = $rounding
                ? float_round($quantity, precisionRounding: $rounding)
                : $quantity;
        }
    }

    /**
     * @param  array<int, int>  $productIds
     * @return array<int, float>
     */
    protected function sumQuantities(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        [$quantityScope] = (new Product)->getLocationFilters();

        return ProductQuantity::query()
            ->whereIn('product_id', $productIds)
            ->where(fn (Builder $query) => $quantityScope($query))
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as total')
            ->pluck('total', 'product_id')
            ->all();
    }

    /**
     * @return Collection<int, Model>
     */
    protected function products(Model $record, mixed $scope): Collection
    {
        $products = match (true) {
            $scope instanceof Paginator, $scope instanceof CursorPaginator => collect($scope->items()),
            $scope instanceof Collection                                   => $scope,
            default                                                        => collect(),
        };

        $products = $products
            ->filter(fn (mixed $product): bool => $product instanceof Model)
            ->values();

        if (! $products->contains(fn (Model $product): bool => $product->getKey() === $record->getKey())) {
            $products->push($record);
        }

        return $products;
    }
}
