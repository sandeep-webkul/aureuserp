<?php

namespace Webkul\Support\Filament\Concerns;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;

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
