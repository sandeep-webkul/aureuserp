<?php

namespace Webkul\Barcode\Console\Commands;

use Illuminate\Console\Command;
use Native\Mobile\NativeServiceProvider;
use Webkul\Barcode\Support\NativePhpMobilePatcher;

class PatchNativeCommand extends Command
{
    protected $signature = 'barcode:patch-native {--force : Replace supported NativePHP files with plugin stubs}';

    protected $description = 'Apply Barcode NativePHP mobile patches.';

    public function handle(NativePhpMobilePatcher $patcher): int
    {
        if (! class_exists(NativeServiceProvider::class)) {
            $this->components->warn('nativephp/mobile is not installed.');

            return self::FAILURE;
        }

        $patcher->apply((bool) $this->option('force'));

        $this->components->info(
            $this->option('force')
                ? 'Barcode NativePHP stubs copied.'
                : 'Barcode NativePHP patches applied.'
        );

        return self::SUCCESS;
    }
}
