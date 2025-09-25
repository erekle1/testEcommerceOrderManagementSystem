<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait CommonQueryScopes
{
    /**
     * Scope a query to filter by price range.
     */
    public function scopeFilterByPrice(Builder $query, ?float $minPrice = null, ?float $maxPrice = null): Builder
    {
        if ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        return $query;
    }

    /**
     * Scope a query to search by name.
     */
    public function scopeSearchByName(Builder $query, ?string $search = null): Builder
    {
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query;
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeFilterByCategory(Builder $query, ?int $categoryId = null): Builder
    {
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query;
    }

    /**
     * Scope a query to filter by stock availability.
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }
}
