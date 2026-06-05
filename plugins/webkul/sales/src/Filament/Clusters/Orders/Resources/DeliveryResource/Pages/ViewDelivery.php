<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\DeliveryResource\Pages;

use Webkul\Inventory\Filament\Clusters\Operations\Resources\DeliveryResource\Pages\ViewDelivery as BaseViewDelivery;
use Webkul\Sale\Filament\Clusters\Orders\Resources\DeliveryResource;

class ViewDelivery extends BaseViewDelivery
{
    protected static string $resource = DeliveryResource::class;
}
