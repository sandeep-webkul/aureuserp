<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\InvoiceResource\Pages;

use Webkul\Invoice\Filament\Clusters\Customers\Resources\InvoiceResource\Pages\ViewInvoice as BaseViewInvoice;
use Webkul\Sale\Filament\Clusters\Orders\Resources\InvoiceResource;

class ViewInvoice extends BaseViewInvoice
{
    protected static string $resource = InvoiceResource::class;
}
