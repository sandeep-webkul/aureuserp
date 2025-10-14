<?php

namespace Webkul\Chatter\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;
use Webkul\Chatter\Filament\Actions\TestChildAction;

class TestNestedModal extends Component implements HasActions, HasForms
{
    use InteractsWithActions, InteractsWithForms;

    public function testAction(): Action
    {
        return TestChildAction::make();

    }

    public function render()
    {
        return view('chatter::livewire.test-nested-modal');
    }
}
