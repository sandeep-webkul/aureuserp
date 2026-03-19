<?php

namespace Webkul\Invoice\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Invoice\Filament\Widgets;
use Webkul\Product\Models\Product;
use Webkul\Security\Models\User;

class Invoices extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;
    use HasPageShield;

    protected static string $routePath = 'invoice';

    protected static function getPagePermission(): ?string
    {
        return 'page_invoice_invoices';
    }

    public static function getNavigationLabel(): string
    {
        return __('invoices::filament/pages/dashboard.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('projects::filament/pages/dashboard.navigation.group');
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return null;
    }

    public function filtersForm(Schema $form): Schema
    {
        return $form->schema([
            Section::make()
                ->schema([
                    Select::make('date_range')
                        ->label('Date Range')
                        ->options([
                            'week'     => 'Last 7 days',
                            'month'    => 'Last 30 days',
                            '3_months' => 'Last 3 months',
                            '6_months' => 'Last 6 months',
                            'year'     => 'Last 1 year',
                            '3_years'  => 'Last 3 years',
                        ])
                        ->default('month')
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            $endDate = Carbon::now()->format('Y-m-d');
                            switch ($state) {
                                case 'week':
                                    $startDate = Carbon::now()->subDays(7)->format('Y-m-d');
                                    break;
                                case 'month':
                                    $startDate = Carbon::now()->subMonth()->format('Y-m-d');
                                    break;
                                case '3_months':
                                    $startDate = Carbon::now()->subMonths(3)->format('Y-m-d');
                                    break;
                                case '6_months':
                                    $startDate = Carbon::now()->subMonths(6)->format('Y-m-d');
                                    break;
                                case 'year':
                                    $startDate = Carbon::now()->subYear()->format('Y-m-d');
                                    break;
                                case '3_years':
                                    $startDate = Carbon::now()->subYears(3)->format('Y-m-d');
                                    break;
                                default:
                                    $startDate = Carbon::now()->subMonth()->format('Y-m-d');
                                    break;
                            }

                            $set('start_date', $startDate);
                            $set('end_date', $endDate);
                        }),

                    DatePicker::make('start_date')
                        ->label('Start Date')
                        ->maxDate(fn(Get $get) => $get('end_date') ?: now())
                        ->default(fn(Get $get) => $get('start_date') ?: now()->subMonth()->format('Y-m-d'))
                        ->reactive()
                        ->hidden()
                        ->native(false),

                    DatePicker::make('end_date')
                        ->label('End Date')
                        ->minDate(fn(Get $get) => $get('start_date') ?: now())
                        ->maxDate(now())
                        ->default(fn(Get $get) => $get('end_date') ?: now()->format('Y-m-d'))
                        ->reactive()
                        ->hidden()
                        ->native(false),
                    Select::make('product_id')
                        ->label('Product')
                        ->options(fn() => Product::pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->placeholder('All Products')
                        ->reactive(),

                    Select::make('salesperson_id')
                        ->label('Salesperson')
                        ->options(fn() => User::pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->placeholder('All Salespersons')
                        ->reactive(),

                ])
                ->columns(3),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            Widgets\InvoiceStatsWidget::make(),
            Widgets\RevenueOverTimeWidget::make(),
            Widgets\TopInvoicesWidget::make(),
            Widgets\TopCustomersWidget::make(),
            Widgets\TopSalespersonsWidget::make(),
        ];
    }
}
