<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament-panels::form wire:submit="save">
            {{ $this->form }}
        </x-filament-panels::form>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium mb-4">Test Nested Modal</h3>
            @livewire('test-nested-modal')
        </div>
    </div>
</x-filament-panels::page>
