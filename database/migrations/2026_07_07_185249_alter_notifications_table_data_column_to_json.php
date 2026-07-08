<?php

use Illuminate\Database\Migrations\Migration;

// Filament's own notification bell queries data->'format' via Eloquent's
// JSON path syntax (vendor/filament/notifications/.../DatabaseNotifications.php).
// Postgres's ->> operator only exists for json/jsonb columns, whereas the stock
// Laravel notifications migration stub uses text (which happens to work on
// MySQL, since MySQL's JSON functions parse any string at runtime).
return new class extends Migration
{
    public function up(): void
    {
        db_dialect()->alterColumnType('notifications', 'data', 'json', 'jsonb', 'data::jsonb');
    }

    public function down(): void
    {
        db_dialect()->alterColumnType('notifications', 'data', 'text', 'text', 'data::text');
    }
};
