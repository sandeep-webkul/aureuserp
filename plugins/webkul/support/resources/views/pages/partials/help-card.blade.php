<a href="{{ $card['url'] }}" target="_blank" rel="noopener noreferrer" class="group block h-full">
    <x-filament::section class="h-full transition group-hover:border-primary-500 group-hover:shadow-md dark:group-hover:border-primary-500">
        <div class="flex h-full flex-col gap-4">
            <div class="flex items-start justify-between">
                <div class="flex size-12 items-center justify-center rounded-lg bg-primary-500/10 text-primary-600 dark:text-primary-400">
                    <x-filament::icon :icon="$card['icon']" class="size-6" />
                </div>

                <x-filament::icon
                    icon="heroicon-o-arrow-up-right"
                    class="size-4 text-gray-400 transition group-hover:text-primary-500"
                />
            </div>

            <div class="flex flex-1 flex-col gap-1">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    {{ $card['title'] }}
                </h3>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $card['description'] }}
                </p>
            </div>

            <div class="mt-auto border-t border-gray-100 pt-4 text-sm text-gray-400 dark:border-white/10 dark:text-gray-500">
                {{ $card['link_label'] }}
            </div>
        </div>
    </x-filament::section>
</a>
