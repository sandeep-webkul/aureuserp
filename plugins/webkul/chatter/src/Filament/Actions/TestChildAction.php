<?php

namespace Webkul\Chatter\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;

class TestChildAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'test-child';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Test Child Modal')
            ->modalHeading('Child Modal - Test Dropdown Positioning')
            ->modalDescription('Open the dropdown below to see if it positions relative to this child modal or the parent.')
            ->schema(function ($form) {
                return $form
                    ->schema([
                        Group::make()
                            ->extraAttributes(['class' => 'fi-absolute-positioning-context'])
                            ->schema([
                                Select::make('test_select')
                                    ->label('Test Dropdown')
                                    ->options([
                                        'option1' => 'Option 1',
                                        'option2' => 'Option 2',
                                        'option3' => 'Option 3',
                                        'option4' => 'Option 4 (longer to test positioning)',
                                    ])
                                    ->searchable()
                                    ->preload()
                                    ->position('top')
                                    ->live()
                                    ->required(),
                            ]),
                    ])
                    ->extraAttributes(['class' => 'fi-absolute-positioning-context']);
            })
            ->modalSubmitActionLabel('Close')
            ->slideOver(false);
    }
}
