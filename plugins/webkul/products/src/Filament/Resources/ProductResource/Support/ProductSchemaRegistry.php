<?php

namespace Webkul\Product\Filament\Resources\ProductResource\Support;

use Closure;

/**
 * Central registry that lets any plugin contribute fields, columns, infolist
 * entries and eager-loads to the shared Product form / table / infolist.
 *
 * Contributions are registered once at plugin boot (guarded implicitly by the
 * plugin being installed) and folded into named slots by ProductForm /
 * ProductInfolist / ProductsTable at render time. Because every plugin's
 * ProductResource delegates schema building to those classes, one registration
 * appears on every plugin's product screen.
 *
 * Slot reference:
 *   form:     left.general.after, left.inventory (replace-or-default),
 *             left.append, right.settings.after, right.pricing.fields,
 *             right.append, hidden
 *   infolist: left.general.after, left.inventory (replace-or-default),
 *             left.append, right.append
 *   table:    columns, filters.append, filters.reject (array of names),
 *             actions, bulkActions, groups
 */
class ProductSchemaRegistry
{
    /** @var array<string, array<int, array{0:int,1:Closure}>> */
    protected static array $form = [];

    /** @var array<string, array<int, array{0:int,1:Closure}>> */
    protected static array $infolist = [];

    /** @var array<string, array<int, array{0:int,1:Closure}>> */
    protected static array $table = [];

    /** @var array<int, string> */
    protected static array $eagerLoad = [];

    public static function form(string $slot, Closure $factory, int $priority = 0): void
    {
        static::$form[$slot][] = [$priority, $factory];
    }

    public static function infolist(string $slot, Closure $factory, int $priority = 0): void
    {
        static::$infolist[$slot][] = [$priority, $factory];
    }

    public static function table(string $slot, Closure $factory, int $priority = 0): void
    {
        static::$table[$slot][] = [$priority, $factory];
    }

    /**
     * @param  array<int, string>  $relations
     */
    public static function eagerLoad(array $relations): void
    {
        static::$eagerLoad = array_values(array_unique([...static::$eagerLoad, ...$relations]));
    }

    /**
     * @return array<int, string>
     */
    public static function eagerLoads(): array
    {
        return static::$eagerLoad;
    }

    public static function hasFormSlot(string $slot): bool
    {
        return ! empty(static::$form[$slot]);
    }

    public static function hasInfolistSlot(string $slot): bool
    {
        return ! empty(static::$infolist[$slot]);
    }

    /**
     * @return array<int, mixed>
     */
    public static function renderForm(string $slot, mixed ...$args): array
    {
        return static::resolve(static::$form[$slot] ?? [], $args);
    }

    /**
     * @return array<int, mixed>
     */
    public static function renderInfolist(string $slot, mixed ...$args): array
    {
        return static::resolve(static::$infolist[$slot] ?? [], $args);
    }

    /**
     * @return array<int, mixed>
     */
    public static function renderTable(string $slot, mixed ...$args): array
    {
        return static::resolve(static::$table[$slot] ?? [], $args);
    }

    /**
     * Priority-sort the slot's factories, invoke them, flatten array results.
     *
     * @param  array<int, array{0:int,1:Closure}>  $entries
     * @param  array<int, mixed>  $args
     * @return array<int, mixed>
     */
    protected static function resolve(array $entries, array $args): array
    {
        usort($entries, fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $out = [];

        foreach ($entries as [$priority, $factory]) {
            $result = $factory(...$args);

            if (is_array($result)) {
                $out = array_merge($out, $result);
            } elseif ($result !== null) {
                $out[] = $result;
            }
        }

        return $out;
    }
}
