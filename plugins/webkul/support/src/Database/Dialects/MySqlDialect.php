<?php

namespace Webkul\Support\Database\Dialects;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MySqlDialect implements DatabaseDialect
{
    public function jsonArrayAgg(string $column): string
    {
        return "JSON_ARRAYAGG({$column})";
    }

    public function monthBucket(string $column): string
    {
        return "DATE_FORMAT({$column}, '%Y-%m')";
    }

    public function alterColumnType(string $table, string $column, string $blueprintMethod, string $postgresType, string $postgresUsing): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($column, $blueprintMethod) {
            $blueprint->{$blueprintMethod}($column)->change();
        });
    }
}
