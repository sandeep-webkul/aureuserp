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

        <div class="flex-grow space-y-1 pt-[2px]">
            <div class="flex items-center justify-between gap-x-3">
                <div class="flex items-center gap-x-2.5">
                    <div class="text-base font-semibold leading-none align-bottom text-black cursor-pointer dark:text-gray-100 font-inter">
                        {{ $getRecord()->causer?->name }}
                    </div>

                    <div class="text-sm font-normal leading-none align-bottom text-gray-500 dark:text-gray-400 font-inter">
                        {{ $getRecord()->created_at->diffForHumans() }}
                    </div>
                </div>

                <div class="flex items-center flex-shrink-0 gap-1">
                    <x-filament::icon-button
                        wire:click="pinMessage({{ $getRecord()->id }})"
                        :icon="$getRecord()->pinned_at ? 'icon-un-pin' : 'icon-pin'"
                        :color="$getRecord()->pinned_at ? 'primary' : 'gray'"
                        :tooltip="$getRecord()->pinned_at ? __('chatter::views/filament/infolists/components/messages/title-text-entry.unpin') : __('chatter::views/filament/infolists/components/messages/title-text-entry.pin')"
                        :label="$getRecord()->pinned_at ? __('chatter::views/filament/infolists/components/messages/title-text-entry.unpin') : __('chatter::views/filament/infolists/components/messages/title-text-entry.pin')"
                        class="!p-1.5"
                    />
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>