<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <div class="flex items-start gap-x-3">
        <x-filament-panels::avatar.user
            size="md"
            :user="$getRecord()->user"
            class="cursor-pointer"
        />

        <div class="min-w-0 flex-1 space-y-2 pt-[6px]">
            <div class="flex items-center gap-x-2">
                <div class="flex items-center gap-x-2.5">
                    <div class="text-base font-semibold leading-none align-bottom text-black cursor-pointer dark:text-gray-100 font-inter">
                        {{ $getRecord()->causer?->name }}
                    </div>

                    <div class="text-sm font-normal leading-none align-bottom text-gray-500 dark:text-gray-400 font-inter">
                        {{ $getRecord()->created_at->diffForHumans() }}
                    </div>
                </div>

                <div class="ml-auto shrink-0">
                    <x-filament-actions::group
                        size="md"
                        :tooltip="__('chatter::views/filament/infolists/components/activities/title-text-entry.more-action-tooltip')"
                        dropdown-placement="bottom-end"
                        :actions="[
                            ($this->markAsDoneAction)(['id' => $getRecord()->id]),
                            ($this->editActivity)(['id' => $getRecord()->id]),
                            ($this->cancelActivity)(['id' => $getRecord()->id]),
                        ]"
                        class="text-gray-600 dark:text-gray-300"
                    />
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
