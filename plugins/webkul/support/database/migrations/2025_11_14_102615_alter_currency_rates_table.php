<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // PostgreSQL has no implicit varchar->date cast, so Blueprint::change()
        // (which emits a bare ALTER COLUMN ... TYPE date) fails there; MySQL
        // allows the implicit cast, so its ->change() path is left untouched.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE currency_rates ALTER COLUMN name TYPE date USING name::date');

            return;
        }

        Schema::table('currency_rates', function (Blueprint $table) {
            $table->date('name')->change();
        });
    }

    public function down()
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE currency_rates ALTER COLUMN name TYPE varchar(255) USING name::text');

            return;
        }

        Schema::table('currency_rates', function (Blueprint $table) {
            $table->string('name')->change();
        });
    }
};
