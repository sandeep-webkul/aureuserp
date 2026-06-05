<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\InvoiceResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\ManagePayments as BaseManagePayments;
use Webkul\Sale\Filament\Clusters\Orders\Resources\InvoiceResource;

class ManagePayments extends BaseManagePayments
{
    protected static string $resource = InvoiceResource::class;
}
