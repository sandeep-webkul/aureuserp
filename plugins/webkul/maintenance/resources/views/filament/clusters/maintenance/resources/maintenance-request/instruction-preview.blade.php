@php
    use Illuminate\Support\Facades\Storage;
    use Webkul\Maintenance\Models\MaintenanceRequest;

    $record = $getRecord();

    $instructionType = $record?->instruction_type;
    $instructionPdf = $record?->instruction_pdf;
    $instructionGoogleSlide = $record?->instruction_google_slide;
    $instructionText = $record?->instruction_text;

    if (isset($get)) {
        $instructionType = $get('instruction_type') ?: $instructionType;
        $instructionPdf = $get('instruction_pdf') ?: $instructionPdf;
        $instructionGoogleSlide = $get('instruction_google_slide') ?: $instructionGoogleSlide;
        $instructionText = $get('instruction_text') ?: $instructionText;
    }

    if (is_array($instructionPdf)) {
        $instructionPdf = reset($instructionPdf) ?: null;
    }

    $pdfUrl = filled($instructionPdf) ? Storage::disk('public')->url($instructionPdf) : null;
    $externalUrl = filled($instructionGoogleSlide) ? $instructionGoogleSlide : null;
    $previewUrl = $externalUrl;

    if (filled($externalUrl) && str_contains($externalUrl, 'docs.google.com')) {
        $previewUrl = preg_replace('/\/edit(?:\?.*)?(?:#.*)?$/', '/preview', $externalUrl) ?? $externalUrl;
    }

    $instructionTypeLabel = filled($instructionType)
        ? __('maintenance::filament/clusters/maintenance/resources/maintenance-request.form.sections.request.tabs.instructions.fields.instruction-type-options.'.str_replace('_', '-', $instructionType))
        : '—';
@endphp

<div class="space-y-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-3">
            <div>
                <p class="text-sm font-medium text-gray-500">
                    {{ __('maintenance::filament/clusters/maintenance/resources/maintenance-request.infolist.sections.request.entries.name') }}
                </p>

                <p class="text-base font-semibold text-gray-900">
                    {{ $record instanceof MaintenanceRequest ? $record->name : '—' }}
                </p>
            </div>

            <div class="text-right">
                <p class="text-sm font-medium text-gray-500">
                    {{ __('maintenance::filament/clusters/maintenance/resources/maintenance-request.infolist.sections.request.entries.instruction-type') }}
                </p>

                <p class="text-sm font-semibold text-gray-900">
                    {{ $instructionTypeLabel }}
                </p>
            </div>
        </div>

        <div class="pt-4">
            @if ($instructionType === 'text')
                <div class="min-h-48 whitespace-pre-wrap rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                    {{ filled($instructionText) ? $instructionText : '—' }}
                </div>
            @elseif ($instructionType === 'pdf' && filled($pdfUrl))
                <div class="space-y-3">
                    <a
                        href="{{ $pdfUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-500"
                    >
                        {{ basename((string) $instructionPdf) }}
                    </a>

                    <iframe
                        src="{{ $pdfUrl }}"
                        title="{{ $record instanceof MaintenanceRequest ? $record->name : __('maintenance::models/maintenance-request.title') }}"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50"
                        style="height: 800px; min-height: 800px;"
                    ></iframe>
                </div>
            @elseif ($instructionType === 'google_slide' && filled($previewUrl))
                <div class="space-y-3">
                    <a
                        href="{{ $externalUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center break-all text-sm font-medium text-primary-600 hover:text-primary-500"
                    >
                        {{ $externalUrl }}
                    </a>

                    <iframe
                        src="{{ $previewUrl }}"
                        title="{{ $record instanceof MaintenanceRequest ? $record->name : __('maintenance::models/maintenance-request.title') }}"
                        class="block w-full rounded-lg border border-gray-200 bg-gray-50"
                        style="height: 800px; min-height: 800px;"
                        allowfullscreen
                    ></iframe>
                </div>
            @else
                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-500">
                    —
                </div>
            @endif
        </div>
    </div>
</div>
