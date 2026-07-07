<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Filament's own notification bell queries data->'format' via Eloquent's
        // JSON path syntax (vendor/filament/notifications/.../DatabaseNotifications.php).
        // Postgres's ->> operator only exists for json/jsonb columns, whereas the stock
        // Laravel notifications migration stub uses text (which happens to work on
        // MySQL, since MySQL's JSON functions parse any string at runtime). Postgres
        // also has no implicit text->jsonb cast, so ->change() needs a raw USING clause.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE jsonb USING data::jsonb');

            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->json('data')->change();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE text USING data::text');

            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->text('data')->change();
        });
    }
};
