<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\DeliveryResource\Pages;

use Webkul\Inventory\Filament\Clusters\Operations\Resources\DeliveryResource\Pages\EditDelivery as BaseEditDelivery;
use Webkul\Sale\Filament\Clusters\Orders\Resources\DeliveryResource;

class EditDelivery extends BaseEditDelivery
{
    protected static string $resource = DeliveryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()], shouldGuessMissingParameters: true);
    }
}
