<?php

namespace Webkul\Sale\Filament\Clusters\Orders\Resources\OrderInvoiceResource\Pages;

use Webkul\Sale\Filament\Clusters\Orders\Resources\InvoiceResource\Pages\EditInvoice as BaseEditInvoice;
use Webkul\Sale\Filament\Clusters\Orders\Resources\OrderInvoiceResource;

class EditInvoice extends BaseEditInvoice
{
    protected static string $resource = OrderInvoiceResource::class;
}
