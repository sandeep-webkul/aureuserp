<?php

namespace Webkul\Maintenance\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MaintenanceRequestState: string implements HasColor, HasIcon, HasLabel
{
    case NORMAL = 'normal';

    case BLOCKED = 'blocked';

    case DONE = 'done';

    public function getLabel(): string
    {
        return match ($this) {
            self::NORMAL  => __('maintenance::enums/maintenance-request-state.normal'),
            self::BLOCKED => __('maintenance::enums/maintenance-request-state.blocked'),
            self::DONE    => __('maintenance::enums/maintenance-request-state.done'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NORMAL  => 'gray',
            self::BLOCKED => 'danger',
            self::DONE    => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::NORMAL  => 'heroicon-o-wrench-screwdriver',
            self::BLOCKED => 'heroicon-o-no-symbol',
            self::DONE    => 'heroicon-o-check-badge',
        };
    }
}
