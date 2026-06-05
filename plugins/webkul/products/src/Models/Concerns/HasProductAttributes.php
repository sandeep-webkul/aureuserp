<?php

namespace Webkul\Product\Models\Concerns;

/**
 * Lets plugins contribute fillable attributes and casts to the shared Product
 * model at boot, so the unified Product form/table (rendered on every plugin's
 * ProductResource) can read and persist columns owned by other plugins.
 *
 * Registered on the base Product; subclasses inherit the merged attributes
 * through the normal model hierarchy. The `initialize*` hook is invoked
 * automatically by Eloquent for every model instance.
 */
trait HasProductAttributes
{
    /** @var array<int, string> */
    protected static array $contributedFillable = [];

    /** @var array<string, mixed> */
    protected static array $contributedCasts = [];

    /**
     * @param  array<int, string>  $attributes
     */
    public static function contributeFillable(array $attributes): void
    {
        static::$contributedFillable = array_values(array_unique([
            ...static::$contributedFillable,
            ...$attributes,
        ]));
    }

    /**
     * @param  array<string, mixed>  $casts
     */
    public static function contributeCasts(array $casts): void
    {
        static::$contributedCasts = array_merge(static::$contributedCasts, $casts);
    }

    public function initializeHasProductAttributes(): void
    {
        if (! empty(static::$contributedFillable)) {
            $this->mergeFillable(static::$contributedFillable);
        }

        if (! empty(static::$contributedCasts)) {
            $this->mergeCasts(static::$contributedCasts);
        }
    }
}
