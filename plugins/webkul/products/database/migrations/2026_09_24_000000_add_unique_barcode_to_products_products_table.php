<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('products_products')
            ->where('barcode', '')
            ->update(['barcode' => null]);

        $duplicateGroups = DB::table('products_products')
            ->select('company_id', 'barcode')
            ->whereNotNull('barcode')
            ->groupBy('company_id', 'barcode')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $ids = DB::table('products_products')
                ->where('barcode', $group->barcode)
                ->when(
                    $group->company_id === null,
                    fn ($query) => $query->whereNull('company_id'),
                    fn ($query) => $query->where('company_id', $group->company_id),
                )
                ->orderBy('id')
                ->pluck('id');

            $clearIds = $ids->slice(1)->values();

            if ($clearIds->isEmpty()) {
                continue;
            }

            Log::warning('Cleared duplicate product barcodes to apply the unique (company_id, barcode) constraint.', [
                'company_id'  => $group->company_id,
                'barcode'     => $group->barcode,
                'kept_id'     => $ids->first(),
                'cleared_ids' => $clearIds->all(),
            ]);

            DB::table('products_products')
                ->whereIn('id', $clearIds->all())
                ->update(['barcode' => null]);
        }

        Schema::table('products_products', function (Blueprint $table) {
            $table->unique(['company_id', 'barcode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products_products', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'barcode']);
        });
    }
};
