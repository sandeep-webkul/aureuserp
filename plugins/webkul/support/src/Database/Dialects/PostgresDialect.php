<?php

namespace Webkul\Support\Database\Dialects;

use Illuminate\Support\Facades\DB;

class PostgresDialect implements DatabaseDialect
{
    public function jsonArrayAgg(string $column): string
    {
        return "json_agg({$column})";
    }

    public function monthBucket(string $column): string
    {
        return "to_char({$column}, 'YYYY-MM')";
    }

    public function alterColumnType(string $table, string $column, string $blueprintMethod, string $postgresType, string $postgresUsing): void
    {
        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE {$postgresType} USING {$postgresUsing}");
    }
}
