<?php

use Webkul\Maintenance\Filament\Clusters\Configurations;
use Webkul\Maintenance\Filament\Clusters\Configurations\Resources\EquipmentCategoryResource;
use Webkul\Maintenance\Filament\Clusters\Configurations\Resources\TeamResource;
use Webkul\Maintenance\Filament\Resources\EquipmentResource;

$basic = ['view_any', 'view', 'create', 'update'];
$delete = ['delete', 'delete_any'];
$forceDelete = ['force_delete', 'force_delete_any'];
$restore = ['restore', 'restore_any'];

return [
    'resources' => [
        'manage' => [
            EquipmentCategoryResource::class => [...$basic, ...$delete],
            EquipmentResource::class         => [...$basic, ...$delete, ...$restore, ...$forceDelete],
            TeamResource::class              => [...$basic, ...$delete, ...$restore, ...$forceDelete],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'exclude' => [
            Configurations::class,
        ],
    ],
];
