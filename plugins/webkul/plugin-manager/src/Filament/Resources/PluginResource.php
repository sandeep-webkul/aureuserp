<?php

namespace Webkul\PluginManager\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Webkul\PluginManager\Filament\Resources\PluginResource\Pages;
use Webkul\Support\Models\Plugin;
use Webkul\Support\Package;

class PluginResource extends Resource
{
    protected static ?string $model = Plugin::class;

    public static function getNavigationGroup(): string
    {
        return 'Plugins';
    }

    protected static ?int $navigationSort = 100;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                split::make([
                    ImageColumn::make('id')
                        ->label('')
                        ->getStateUsing(function ($record) {
                            $package = $record->getPackage();
                            $icon = $package ? $package->icon : '';

                            return $icon ? asset("svg/{$icon}.svg") : 'heroicon-o-puzzle-piece';
                        })
                        ->imageSize(100)
                        ->grow(false),
                    stack::make([
                        Split::make([
                            TextColumn::make('name')
                                ->weight('semibold')
                                ->size(TextSize::Large)
                                ->formatStateUsing(fn(string $state): string => ucfirst($state))
                                ->grow(false),
                            TextColumn::make('latest_version')
                                ->label('Version')
                                ->default('1.0.0')
                                ->badge()
                                ->color('info')
                                ->prefix('v'),
                        ]),
                        TextColumn::make('summary')
                            ->color('gray')
                            ->limit(80)
                            ->wrap(),

                        Split::make([
                            TextColumn::make('is_installed')
                                ->badge()
                                ->inline()
                                ->grow(false)
                                ->formatStateUsing(function ($record) {
                                    if ($record->is_installed) {
                                        return 'Installed';
                                    } else {
                                        return 'Not Installed';
                                    }
                                })
                                ->color(function ($record) {
                                    if ($record->is_installed) {
                                        return 'success';
                                    } else {
                                        return 'gray';
                                    }
                                }),

                            TextColumn::make('dependencies_count')
                                ->label('Dependencies')
                                ->state(function ($record) {
                                    return count($record->getDependenciesFromConfig());
                                })
                                ->badge()
                                ->color('warning')
                                ->suffix(' Dependencies')
                                ->default(0),
                        ]),
                    ]),
                ]),
            ])
            ->contentGrid([
                'sm'  => 1,
                'md'  => 2,
                'lg'  => 2,
                'xl'  => 3,
                '2xl' => 4,
            ])
            ->filters([
                TernaryFilter::make('is_installed')
                    ->label('Installation Status')
                    ->placeholder('All plugins')
                    ->trueLabel('Installed')
                    ->falseLabel('Not Installed'),

                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All plugins')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),

                SelectFilter::make('author')
                    ->label('Author')
                    ->options([
                        'Webkul'      => 'Webkul',
                        'Third Party' => 'Third Party',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye'),

                    Action::make('install')
                        ->label('Install')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->visible(fn($record) => ! $record->is_installed)
                        ->requiresConfirmation()
                        ->modalHeading('Install Plugin')
                        ->modalDescription(fn($record) => "Are you sure you want to install the '{$record->name}' plugin? This will run migrations and seeders.")
                        ->modalSubmitActionLabel('Install Plugin')
                        ->action(function ($record) {
                            $php = escapeshellarg(PHP_BINARY);

                            $artisan = escapeshellarg(base_path('artisan'));

                            $cmd = "$php $artisan $record->name:install";

                            try {
                                exec($cmd, $output, $exitCode);

                                $record->update([
                                    'is_installed' => true,
                                    'is_active'    => true,
                                ]);

                                Notification::make()
                                    ->title('Plugin Installed Successfully')
                                    ->body("The '{$record->name}' plugin has been installed.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Installation Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),

                    Action::make('uninstall')
                        ->label('Uninstall')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                          ->modalWidth(Width::ExtraLarge)
                        ->visible(fn($record) => $record->is_installed)
                        ->modalHeading('Uninstall Plugin')
                       ->modalContent(function ($record) {
                            $dependents = $record->getDependentsFromConfig();
                            $package = $record->getPackage();
                            $tables = [];

                            if ($package && !empty($package->migrationFileNames)) {
                                foreach ($package->migrationFileNames as $migrationFile) {
                                    if (preg_match('/create_(.*?)_table/', $migrationFile, $matches) ||
                                        preg_match('/alter_(.*?)_table/', $migrationFile, $matches)) {
                                        $table = $matches[1];
                                        $count = \Schema::hasTable($table) ? DB::table($table)->count() : 0;
                                        if ($count > 0) {
                                            $tables[] = [
                                                'table' => $table,
                                                'count' => $count,
                                            ];
                                        }
                                    }
                                }
                            }

                            if (!empty($dependents)) {
                                foreach ($dependents as $dependent) {
                                    $dependentPlugin = Plugin::where('name', $dependent)->first();
                                    $dependentPackage = $dependentPlugin ? $dependentPlugin->getPackage() : null;
                                    if ($dependentPackage && !empty($dependentPackage->migrationFileNames)) {
                                        foreach ($dependentPackage->migrationFileNames as $migrationFile) {
                                            if (preg_match('/create_(.*?)_table/', $migrationFile, $matches) ||
                                                preg_match('/alter_(.*?)_table/', $migrationFile, $matches)) {
                                                $table = $matches[1];
                                                $count = \Schema::hasTable($table) ? DB::table($table)->count() : 0;
                                                if ($count > 0) { 
                                                    $exists = false;
                                                    foreach ($tables as $existingTable) {
                                                        if ($existingTable['table'] === $table) {
                                                            $exists = true;
                                                            break;
                                                        }
                                                    }
                                                    if (!$exists) {
                                                        $tables[] = [
                                                            'table' => $table,
                                                            'count' => $count,
                                                        ];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            return view('plugins::uninstall-modal', [
                                'record' => $record,
                                'dependents' => $dependents,
                                'tables' => $tables,
                            ]);
                        })
                        ->modalSubmitActionLabel('Uninstall Plugin')
                        ->action(function ($record) {
                            try {
                                $dependents = $record->getDependentsFromConfig();

                                if (!empty($dependents)) {
                                    foreach ($dependents as $dependent) {
                                        $dependentPlugin = Plugin::where('name', $dependent)->first();
                                        if ($dependentPlugin && $dependentPlugin->is_installed) {
                                            $php = escapeshellarg(PHP_BINARY);
                                            $artisan = escapeshellarg(base_path('artisan'));
                                            $cmd = "$php $artisan {$dependent}:uninstall --force";
                                            exec($cmd, $output, $exitCode);

                                            // Update plugin status in database
                                            $dependentPlugin->update([
                                                'is_installed' => false,
                                                'is_active' => false,
                                            ]);
                                            dump($dependentPlugin);
                                        }
                                    }
                                }

                                $php = escapeshellarg(PHP_BINARY);
                                $artisan = escapeshellarg(base_path('artisan'));
                                $cmd = "$php $artisan {$record->name}:uninstall";

                                exec($cmd, $output, $exitCode);

                                $record->update([
                                    'is_installed' => false,
                                    'is_active'    => false,
                                ]);

                                $message = "The '{$record->name}' plugin has been uninstalled.";
                                if (!empty($dependents)) {
                                    $dependentsStr = implode(', ', array_map('ucfirst', $dependents));
                                    $message .= " Dependents uninstalled: {$dependentsStr}.";
                                }

                                Notification::make()
                                    ->title('Plugin Uninstalled Successfully')
                                    ->body($message)
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Uninstallation Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->recordActionsAlignment('end')
            ->defaultSort('sort', 'asc')
            ->reorderable('sort')
            ->paginated([12, 24, 48]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plugin Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Plugin Name')
                                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                                    ->weight('bold')
                                    ->size('lg'),

                                TextEntry::make('latest_version')
                                    ->label('Version')
                                    ->badge()
                                    ->color('info'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                IconEntry::make('is_installed')
                                    ->label('Installation Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-s-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('gray'),

                                IconEntry::make('is_active')
                                    ->label('Active Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-s-check-badge')
                                    ->falseIcon('heroicon-o-minus-circle')
                                    ->trueColor('success')
                                    ->falseColor('warning'),
                            ]),

                        TextEntry::make('author')
                            ->label('Author')
                            ->badge()
                            ->default('Webkul'),

                        TextEntry::make('license')
                            ->label('License')
                            ->default('MIT')
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('summary')
                            ->label('Description')
                            ->columnSpanFull(),
                    ]),
                Group::make([
                    Section::make('Dependencies')
                        ->schema([
                            RepeatableEntry::make('dependencies')
                                ->label('Required Plugins')
                                ->state(function ($record) {
                                    $dependencies = [];
                                    foreach ($record->getDependenciesFromConfig() as $dep) {
                                        $dependencies[] = [
                                            'name' => $dep,
                                            'is_installed' => Package::isPluginInstalled($dep),
                                        ];
                                    }
                                    return $dependencies;
                                })
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('Plugin Name')
                                        ->formatStateUsing(fn(string $state): string => ucfirst($state))
                                        ->badge()
                                        ->color('warning'),

                                    IconEntry::make('is_installed')
                                        ->label('Installed')
                                        ->boolean()
                                        ->trueIcon('heroicon-s-check-circle')
                                        ->falseIcon('heroicon-o-x-circle')
                                        ->trueColor('success')
                                        ->falseColor('danger'),
                                ])
                                ->columns(2)
                                ->visible(fn($record) => count($record->dependencies ?? []) > 0)
                                ->placeholder('No dependencies required'),

                            RepeatableEntry::make('dependents')
                                ->label('Plugins That Depend On This')
                                ->state(function ($record) {
                                    $dependents = [];
                                    foreach ($record->getDependentsFromConfig() as $dep) {
                                        $dependents[] = [
                                            'name' => $dep,
                                            'is_installed' => Package::isPluginInstalled($dep),
                                        ];
                                    }
                                    return $dependents;
                                })
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('Plugin Name')
                                        ->formatStateUsing(fn(string $state): string => ucfirst($state))
                                        ->badge()
                                        ->color('info'),

                                    IconEntry::make('is_installed')
                                        ->label('Installed')
                                        ->boolean()
                                        ->trueIcon('heroicon-s-check-circle')
                                        ->falseIcon('heroicon-o-x-circle')
                                        ->trueColor('success')
                                        ->falseColor('gray'),
                                ])
                                ->columns(2)
                                ->visible(fn($record) => count($record->dependents ?? []) > 0)
                                ->placeholder('No dependents'),
                        ]),
                ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlugins::route('/'),

        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $packages = Plugin::getAllPluginPackages();
        $excludedPlugins = ['accounts', 'products', 'payments', 'full-calendar'];

        $installablePlugins = array_filter(array_keys($packages), function ($pluginName) use ($packages, $excludedPlugins) {
            return !in_array($pluginName, $excludedPlugins) && !$packages[$pluginName]->isCore;
        });

        return parent::getEloquentQuery()->whereIn('name', $installablePlugins);
    }
}
