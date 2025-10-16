@php
    $record = $getRecord();
    $changes = is_array($record->properties) ? $record->properties : [];
@endphp

<div {{ $attributes->merge($getExtraAttributes())->class('') }}>
    @if ($record->body)
        <div class="text-sm leading-6 text-gray-700 dark:text-gray-300 [overflow-wrap:anywhere] max-w-full overflow-x-hidden [&_a]:[overflow-wrap:anywhere] [&_a]:text-primary-600 dark:[&_a]:text-primary-400 [&_a:hover]:underline [&_ul]:list-disc [&_ul]:ms-5 [&_ol]:list-decimal [&_ol]:ms-5 mb-6">
            {!! $record->body !!}
        </div>
    @endif

    <div class="grid grid-cols-3 gap-6">
        @if ($record->activityType?->name)
            <div class="flex flex-col gap-2.5">
                <span class="text-xs font-semibold tracking-wider text-gray-600 dark:text-gray-400">
                    @lang('chatter::views/filament/infolists/components/activities/content-text-entry.summary')
                </span>

                <span class="inline-flex items-center gap-2 px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg w-fit shadow-sm hover:shadow-md transition-shadow duration-200 font-medium text-sm">
                    {{ $record->activityType?->name }}
                </span>
            </div>
        @endif

        @if ($record->assignedTo)
            <div class="flex flex-col gap-2.5">
                <span class="text-xs font-semibold tracking-wider text-gray-600 dark:text-gray-400">
                    @lang('chatter::views/filament/infolists/components/activities/content-text-entry.assigned-to')
                </span>
                
                <div class="inline-flex items-center gap-2 px-2 py-1 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg w-fit hover:shadow-sm transition-shadow duration-200">
                    <x-filament-panels::avatar.user
                        size="sm"
                        :user="$record->assignedTo"
                        class="flex-shrink-0 ring-2 ring-white dark:ring-gray-700"
                    />

                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                        {{ $record->assignedTo->name }}
                    </span>
                </div>
            </div>
        @endif

        @if ($record->summary)
            <div class="flex flex-col gap-2.5">
                <span class="text-xs font-semibold tracking-wider text-gray-600 dark:text-gray-400">
                    @lang('chatter::views/filament/infolists/components/activities/content-text-entry.summary')
                </span>

                <div class="flex items-start gap-2">
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100 leading-relaxed">
                        {{ $record->summary }}
                    </span>
                </div>
            </div>
        @endif

        @if ($record->date_deadline)
            <div class="flex flex-col gap-2.5">
                <span class="text-xs font-semibold tracking-wider text-gray-600 dark:text-gray-400">
                    @lang('chatter::views/filament/infolists/components/activities/content-text-entry.due-date')
                </span>

                @php
                    $deadline = \Carbon\Carbon::parse($record->date_deadline);
                    $now = \Carbon\Carbon::now();
                    $daysDifference = $now->diffInDays($deadline, false);
                    $roundedDays = ceil(abs($daysDifference));

                    $deadlineDescription = $deadline->isToday()
                        ? __('chatter::views/filament/infolists/components/activities/content-text-entry.today')
                        : ($deadline->isFuture()
                            ? ($roundedDays === 1
                                ? __('chatter::views/filament/infolists/components/activities/content-text-entry.tomorrow')
                                : __('chatter::views/filament/infolists/components/activities/content-text-entry.due-in-days', ['days' => $roundedDays])
                            )
                            : ($roundedDays === 1
                                ? __('chatter::views/filament/infolists/components/activities/content-text-entry.one-day-overdue')
                                : __('chatter::views/filament/infolists/components/activities/content-text-entry.days-overdue', ['days' => $roundedDays])
                            )
                        );

                    $textColor = $deadline->isToday()
                        ? 'text-yellow-700 dark:text-yellow-400'
                        : ($deadline->isPast()
                            ? 'text-red-700 dark:text-red-400'
                            : 'text-green-700 dark:text-green-400'
                        );
                    
                    $bgColor = $deadline->isToday()
                        ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800'
                        : ($deadline->isPast()
                            ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
                            : 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
                        );
                @endphp

                <div class="flex items-center gap-3 px-4 py-2.5 {{ $bgColor }} border rounded-lg w-fit">
                    <span class="text-sm font-semibold {{ $textColor }}">
                        {{ $deadlineDescription }}
                    </span>

                    <x-filament::icon-button
                        icon="heroicon-m-question-mark-circle"
                        color="gray"
                        size="sm"
                        :tooltip="$deadline->format('F j, Y')"
                    />
                </div>
            </div>
        @endif
    </div>
</div>