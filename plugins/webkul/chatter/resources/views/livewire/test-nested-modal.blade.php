<div>
    <h1 class="text-xl font-bold mb-4">Test Nested Modal Component</h1>
    <p class="mb-4">Click the button below to open the outer modal, then the nested one to test dropdown positioning.</p>
    <x-filament::button wire:click="mountAction('testAction')">
        Open Outer SlideOver
    </x-filament::button>
    <x-filament-actions::modals />
</div>
