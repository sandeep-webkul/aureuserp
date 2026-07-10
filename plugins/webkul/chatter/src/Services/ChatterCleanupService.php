<?php

namespace Webkul\Chatter\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Chatter\Models\Attachment;

class ChatterCleanupService
{
    /**
     * Chatter tables mapped to the column holding the morph type of the record they belong to.
     */
    protected const MORPH_TYPE_COLUMNS = [
        'chatter_attachments' => 'messageable_type',
        'chatter_messages'    => 'messageable_type',
        'chatter_followers'   => 'followable_type',
    ];

    /**
     * Delete the chatter records whose owning model no longer has a database table.
     *
     * Uninstalling a plugin rolls back only its own migrations, so its chatter records
     * outlive it and reappear against the reused ids once the plugin is installed again.
     */
    public static function purgeOrphanedRecords(): void
    {
        foreach (self::MORPH_TYPE_COLUMNS as $table => $typeColumn) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $orphanedTypes = DB::table($table)
                ->distinct()
                ->pluck($typeColumn)
                ->reject(fn (?string $type) => $type === null || self::morphedTableExists($type));

            if ($orphanedTypes->isEmpty()) {
                continue;
            }

            if ($table === 'chatter_attachments') {
                // Deleted through Eloquent so the model's `deleted` hook removes the stored files.
                Attachment::whereIn($typeColumn, $orphanedTypes)
                    ->chunkById(100, fn ($attachments) => $attachments->each->delete());

                continue;
            }

            DB::table($table)->whereIn($typeColumn, $orphanedTypes)->delete();
        }
    }

    protected static function morphedTableExists(string $type): bool
    {
        $model = Relation::getMorphedModel($type) ?? $type;

        if (! is_subclass_of($model, Model::class)) {
            return false;
        }

        return Schema::hasTable((new $model)->getTable());
    }
}
