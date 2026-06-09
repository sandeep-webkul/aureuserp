<?php

namespace Webkul\Support\Filament\Concerns;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;

/**
 * Turns a clustered SettingsPage into a "mirror" page that lives in the plugin's
 * own navigation context instead of the global Settings cluster.
 *
 * A mirror page should:
 *   - extend its clustered counterpart (to inherit the form, settings class,
 *     labels, icon and permission),
 *   - set `protected static ?string $cluster = null;`,
 *   - set a unique `$slug` (so it gets its own, non-cluster route),
 *   - set `protected static bool $shouldRegisterNavigation = false;`
 *     (the plugin exposes a single "Settings" item; siblings appear via the
 *     sub-navigation built below).
 *
 * The sub-navigation lists every mirror page sharing the same navigation group,
 * so navigating between a plugin's settings pages keeps the plugin sidebar.
 */
trait HasSettingsMirror
{
    public static function getCluster(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getSubNavigation(): array
    {
        $group = static::getNavigationGroup();

        return collect(Filament::getPages())
            ->filter(fn (string $page): bool => in_array(
                HasSettingsMirror::class,
                class_uses_recursive($page),
                true,
            ) && $page::getNavigationGroup() === $group)
            ->filter(fn (string $page): bool => $page::canAccess())
            ->sortBy(fn (string $page): int => $page::getNavigationSort() ?? 0)
            ->map(fn (string $page): NavigationItem => NavigationItem::make($page::getNavigationLabel())
                ->icon($page::getNavigationIcon())
                ->url($page::getUrl())
                ->isActiveWhen(fn (): bool => request()->routeIs($page::getRouteName())))
            ->values()
            ->all();
    }
}
