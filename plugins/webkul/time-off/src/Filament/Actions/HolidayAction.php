<?php

namespace Webkul\TimeOff\Filament\Actions;

use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Webkul\Support\Filament\Infolists\Components\RepeatableEntry;
use Webkul\Support\Models\CalendarLeave;
use Webkul\TimeOff\Models\LeaveMandatoryDay;

class HolidayAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'time_off.holiday_action';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->hiddenLabel()
            ->icon('heroicon-o-lifebuoy')
            ->modalWidth(Width::TwoExtraLarge)
            ->slideOver()
            ->schema([
                TextEntry::make('public_holidays_empty')
                    ->hiddenLabel()
                    ->state(__('time-off::filament/actions/holiday-action.form.empty.public-holiday'))
                    ->color('gray')
                    ->visible(fn (): bool => CalendarLeave::query()->doesntExist()),

                RepeatableEntry::make('public_holidays')
                    ->label(__('time-off::filament/actions/holiday-action.form.placeholders.public-holiday'))
                    ->state(fn (): Collection => CalendarLeave::with('company')->get())
                    ->visible(fn (): bool => CalendarLeave::query()->exists())
                    ->schema([
                        Grid::make(2)
                            ->dense()
                            ->extraAttributes(['class' => 'items-center'])
                            ->schema([
                                Group::make()
                                    ->gap(false)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->hiddenLabel()
                                            ->weight(FontWeight::Medium),

                                        TextEntry::make('company_name')
                                            ->hiddenLabel()
                                            ->size(TextSize::Small)
                                            ->color('gray')
                                            ->state(fn (CalendarLeave $record): string => $record->company?->name ?? 'N/A'),
                                    ]),

                                TextEntry::make('date_range')
                                    ->hiddenLabel()
                                    ->alignEnd()
                                    ->state(fn (CalendarLeave $record): string => $record->date_from === $record->date_to
                                        ? (string) $record->date_from
                                        : "{$record->date_from} - {$record->date_to}"),
                            ]),
                    ]),

                TextEntry::make('mandatory_holidays_empty')
                    ->hiddenLabel()
                    ->state(__('time-off::filament/actions/holiday-action.form.empty.mandatory-holiday'))
                    ->color('gray')
                    ->visible(fn (): bool => LeaveMandatoryDay::query()->doesntExist()),

                RepeatableEntry::make('mandatory_holidays')
                    ->label(__('time-off::filament/actions/holiday-action.form.placeholders.mandatory-holiday'))
                    ->state(fn (): Collection => LeaveMandatoryDay::with('company')->get())
                    ->visible(fn (): bool => LeaveMandatoryDay::query()->exists())
                    ->schema([
                        Grid::make(2)
                            ->dense()
                            ->extraAttributes(['class' => 'items-center'])
                            ->schema([
                                Group::make()
                                    ->gap(false)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->hiddenLabel()
                                            ->weight(FontWeight::Medium),

                                        TextEntry::make('company_name')
                                            ->hiddenLabel()
                                            ->size(TextSize::Small)
                                            ->color('gray')
                                            ->state(fn (LeaveMandatoryDay $record): string => $record->company?->name ?? 'N/A'),
                                    ]),

                                TextEntry::make('date_range')
                                    ->hiddenLabel()
                                    ->alignEnd()
                                    ->state(fn (LeaveMandatoryDay $record): string => $record->start_date === $record->end_date
                                        ? (string) $record->start_date
                                        : "{$record->start_date} - {$record->end_date}"),
                            ]),
                    ]),
            ])
            ->modalIcon('heroicon-o-lifebuoy')
            ->label(__('time-off::filament/actions/holiday-action.title'))
            ->modalSubmitAction(false)
            ->modalCancelAction(false);
    }
}
