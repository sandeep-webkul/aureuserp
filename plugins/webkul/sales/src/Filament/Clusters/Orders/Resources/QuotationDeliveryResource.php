<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources;

use Filament\Resources\ParentResourceRegistration;
use Filament\Resources\Pages\Page;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\DeliveryResource as BaseDeliveryResource;
use Webkul\Inventory\Models\Delivery;
use Webkul\Sale\Filament\Clusters\Orders;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationDeliveryResource\Pages\EditDelivery;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationDeliveryResource\Pages\ManageMoves;
use Webkul\Sale\Filament\Clusters\Orders\Resources\QuotationDeliveryResource\Pages\ViewDelivery;

class QuotationDeliveryResource extends BaseDeliveryResource
{
    protected static ?string $model = Delivery::class;

    protected static ?string $parentResource = QuotationResource::class;

    protected static ?string $slug = 'deliveries';

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $cluster = Orders::class;

    public static function getParentResourceRegistration(): ?ParentResourceRegistration
    {
        return QuotationResource::asParent()
            ->relationship('deliveries')
            ->inverseRelationship('saleOrder');
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewDelivery::class,
            EditDelivery::class,
            ManageMoves::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'view' => ViewDelivery::route('/{record}/view'),
            'edit' => EditDelivery::route('/{record}/edit'),
            'moves' => ManageMoves::route('/{record}/moves'),
        ];
    }
}
