<?php

namespace Webkul\Support\Database\Dialects;

/**
 * Central point for SQL that MySQL and PostgreSQL express differently.
 *
 * Resolved via the container (see `db_dialect()`) so call sites never branch
 * on `DB::connection()->getDriverName()` themselves — add a method here
 * instead of a new inline driver check when the next incompatibility turns up.
 */
interface DatabaseDialect
{
    /**
     * SQL expression that aggregates a column's per-row values into a JSON array.
     */
    public function jsonArrayAgg(string $column): string;

    /**
     * SQL expression that formats a timestamp column into a 'YYYY-MM' bucket string,
     * for GROUP BY month reporting queries.
     */
    public function monthBucket(string $column): string;

    /**
     * Change a column's underlying type.
     *
     * @param  string  $blueprintMethod  Fluent `Illuminate\Database\Schema\Blueprint` column method to
     *                                    use on MySQL (e.g. 'date', 'json', 'text', 'string').
     * @param  string  $postgresType  Target Postgres column type (e.g. 'date', 'jsonb', 'varchar(255)').
     * @param  string  $postgresUsing  Postgres `USING` cast expression (e.g. 'name::date').
     */
    public function alterColumnType(string $table, string $column, string $blueprintMethod, string $postgresType, string $postgresUsing): void;
}
