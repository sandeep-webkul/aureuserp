<?php

namespace Webkul\PluginManager\Filament\Resources\PluginResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Webkul\PluginManager\Filament\Resources\PluginResource;
use Webkul\Support\Models\Plugin;

class ListPlugins extends ListRecords
{
    protected static string $resource = PluginResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_plugins')
                ->label('Sync Available Plugins')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Sync Plugins')
                ->modalDescription('This will scan the plugins directory and register any new plugins found.')
                ->modalSubmitActionLabel('Sync Plugins')
                ->action(function () {
                    try {
                        $packages = Plugin::getAllPluginPackages();
                        $synced = 0;

                        foreach ($packages as $pluginName => $package) {
                            if ($package->isCore) {
                                continue;
                            }

                            $composerPath = base_path("plugins/webkul/{$pluginName}/composer.json");
                            $composerData = [];
                            if (file_exists($composerPath)) {
                                $composerData = json_decode(file_get_contents($composerPath), true);
                            }

                            $plugin = Plugin::updateOrCreate(
                                ['name' => $pluginName],
                                [
                                    'author'         => $composerData['authors'][0]['name'] ?? 'Webkul',
                                    'summary'        => $composerData['description'] ?? $package->description ?? '',
                                    'description'    => $composerData['description'] ?? $package->description ?? '',
                                    'latest_version' => $composerData['version'] ?? '1.0.0',
                                    'license'        => $composerData['license'] ?? 'MIT',
                                    'is_active'      => true,
                                    'is_installed'   => false,
                                    'sort'           => 1,
                                ]
                            );

                            $dependencies = $plugin->getDependenciesFromConfig();
                            if (!empty($dependencies)) {
                                $dependencyIds = [];
                                foreach ($dependencies as $dependencyName) {
                                    $dependencyPlugin = Plugin::where('name', $dependencyName)->first();
                                    if ($dependencyPlugin) {
                                        $dependencyIds[] = $dependencyPlugin->id;
                                    }
                                }
                                $plugin->dependencies()->sync($dependencyIds);
                            }

                            if ($plugin->wasRecentlyCreated) {
                                $synced++;
                            }
                        }

                        Notification::make()
                            ->title('Plugins Synced Successfully')
                            ->body("Found and synced {$synced} new plugin(s).")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Sync Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
