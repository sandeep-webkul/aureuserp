@php
    $record = $getRecord();
    $changes = is_array($record->properties) ? $record->properties : [];
@endphp

<div {{ $attributes->merge($getExtraAttributes())->class('') }}>
    @switch($record->type)
        @case('note')
        @case('comment')
            <div class="flex flex-col gap-2">
                @if ($record->subject)
                    <div>
                        <span class="block text-xs font-medium tracking-wide text-gray-500 dark:text-gray-400">
                            @lang('chatter::views/filament/infolists/components/messages/content-text-entry.subject')
                        </span>

                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {!! $record->subject !!}
                        </div>
                    </div>
                @endif

                @if ($record->body)
                    <div class="text-sm leading-6 text-gray-700 [overflow-wrap:anywhere] overflow-x-hidden max-w-full [&_a]:[overflow-wrap:anywhere] [&_a]:text-primary-600 dark:[&_a]:text-primary-400 [&_a:hover]:underline [&_ul]:list-disc [&_ul]:ms-5 [&_ol]:list-decimal [&_ol]:ms-5 dark:text-white">
                        {!! $record->body !!}
                    </div>
                @endif

                @if ($record->attachments->isNotEmpty())
                    <section>
                        <div class="grid grid-cols-3 gap-4">
                            @foreach($record->attachments as $attachment)
                                @php    
                                    $fileExtension = strtolower(pathinfo($attachment->original_file_name, PATHINFO_EXTENSION));
                                    $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp']);
                                    $isPreviewable = $isImage || in_array($fileExtension, ['pdf']);

                                    $icon = match($fileExtension) {
                                        'pdf' => 'heroicon-o-document-text',
                                        'sql' => 'heroicon-o-database',
                                        'csv', 'xlsx', 'xls' => 'heroicon-o-table-cells',
                                        'md', 'txt' => 'heroicon-o-document',
                                        'zip', 'rar', '7z' => 'heroicon-o-archive-box',
                                        'doc', 'docx' => 'heroicon-o-document-text',
                                        'mp4', 'avi', 'mov' => 'heroicon-o-film',
                                        'mp3', 'wav', 'ogg' => 'heroicon-o-musical-note',
                                        default => 'heroicon-o-document',
                                    };
                                @endphp

                                <div class="flex items-center gap-3 px-2 py-3  w-[190] rounded-xl bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-700">
                                    @if ($isImage)
                                        <div class="relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 flex-shrink-0">
                                            <img
                                                src="{{ Storage::url($attachment->file_path) }}"
                                                alt="{{ $attachment->original_file_name }}"
                                                class="object-cover w-10 h-10"
                                                loading="lazy"
                                            />
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center flex-shrink-0 bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg border border-gray-200 dark:from-gray-800 dark:to-gray-800/50 dark:border-gray-700">
                                            <x-filament::icon 
                                                :icon="$icon" 
                                                class="w-10 h-10 text-gray-500 dark:text-gray-400" 
                                            />
                                        </div>
                                    @endif

                                    <div class="flex-1 min-w-0 flex flex-col gap-2">
                                        <p class="text-sm font-semibold text-gray-900 truncate leading-tight dark:text-gray-100" title="{{ $attachment->original_file_name }}">
                                            {{ $attachment->original_file_name }}
                                        </p>
                                        
                                        <div class="flex items-center gap-4">
                                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">
                                                {{ strtoupper($fileExtension) }}
                                            </p>
                                            
                                            @if($isPreviewable)
                                                <x-filament::icon-button
                                                    icon="heroicon-m-eye"
                                                    color="gray"
                                                    size="xs"
                                                    tag="a"
                                                    :href="Storage::url($attachment->file_path)"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                />
                                            @endif

                                            <x-filament::icon-button
                                                icon="heroicon-m-arrow-down-tray"
                                                color="primary"
                                                size="xs"
                                                tag="a"
                                                :href="Storage::url($attachment->file_path)"
                                                download="{{ $attachment->original_file_name }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            />
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            @break
        @case('notification')
            @if ($record->body)
                <div class="font-inter text-base leading-none tracking-normal align-bottom text-black dark:text-gray-100  max-w-full">
                    {!! $record->body !!}
                </div>
            @endif

            @if (
                count($changes) > 0
                && $record->event !== 'created'
            )
                <div class="mt-3 overflow-hidden shadow-sm rounded-xl bg-white/70 ring-1 ring-black/5 dark:bg-gray-900/60 dark:ring-white/5">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50/80 dark:border-gray-800 dark:bg-gray-800/60">
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-arrow-path class="w-5 h-5 text-primary-600 dark:text-primary-400"/>

                            <h3 class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">
                                @lang('chatter::views/filament/infolists/components/messages/content-text-entry.changes-made')
                            </h3>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach($changes as $field => $change)
                            @if(is_array($change))
                                <div class="p-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        @if($field === 'title')
                                            <x-heroicon-m-pencil-square class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                                        @elseif($field === 'due_date')
                                            <x-heroicon-m-calendar class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                                        @else
                                            <x-heroicon-m-arrow-path class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                                        @endif

                                        <span class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">
                                            @lang('chatter::views/filament/infolists/components/messages/content-text-entry.modified', [
                                                'field' => ucwords(str_replace('_', ' ', $field)),
                                            ])

                                            @isset($change['type'])
                                                <span class="inline-flex items-center text-xs rounded-md">
                                                    {{ ucfirst($change['type']) }}
                                                </span>
                                            @endisset
                                        </span>
                                    </div>

                                    <div class="pl-6 mt-2 space-y-2">
                                        @if(isset($change['old_value']))
                                            <div class="flex items-center gap-2 group">
                                                <span class="flex-shrink-0">
                                                    <x-heroicon-m-minus-circle
                                                        class="w-4 h-4"
                                                        @style([
                                                            'color: rgb(var(--danger-500))',
                                                        ])
                                                    />
                                                </span>

                                                <span
                                                    class="text-sm text-gray-600 transition-colors dark:text-gray-400"
                                                    @style([
                                                        'color: rgb(var(--danger-500))',
                                                    ])
                                                >
                                                    @if($field === 'due_date')
                                                        {{ \Carbon\Carbon::parse($change['old_value'])->format('F j, Y') }}
                                                    @else
                                                        @if (is_array($change['old_value']))
                                                            {{ implode(', ', $change['old_value']) }}
                                                        @else
                                                            {!! $change['old_value'] !!}
                                                        @endif
                                                    @endif
                                                </span>
                                            </div>
                                        @endif

                                        @if(isset($change['new_value']))
                                            <div class="flex items-center gap-2 group">
                                                <span class="flex-shrink-0">
                                                    <x-heroicon-m-plus-circle
                                                        class="w-4 h-4"
                                                        @style([
                                                            'color: rgb(var(--success-500))',
                                                        ])
                                                    />
                                                </span>

                                                <span class="text-sm font-medium text-gray-900 transition-colors dark:text-gray-100"
                                                        @style([
                                                            'color: rgb(var(--success-500))',
                                                        ])>
                                                    @if($field === 'due_date')
                                                        {{ \Carbon\Carbon::parse($change['new_value'])->format('F j, Y') }}
                                                    @else
                                                        @if (is_array($change['new_value']))
                                                            {{ implode(', ', $change['new_value']) }}
                                                        @else
                                                            {!! $change['new_value'] !!}
                                                        @endif
                                                    @endif
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            @break
    @endSwitch
</div>
