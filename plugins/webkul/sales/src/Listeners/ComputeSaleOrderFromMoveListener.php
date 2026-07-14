<?php

namespace Webkul\Sale\Listeners;

use Webkul\Account\Events\MoveCancelled;
use Webkul\Account\Events\MoveConfirmed;
use Webkul\Account\Events\MoveDrafted;
use Webkul\Account\Events\MoveReversed;
use Webkul\PluginManager\Package;
use Webkul\Sale\Facades\SaleOrder as SaleOrderFacade;
use Webkul\Sale\Models\Order;

class ComputeSaleOrderFromMoveListener
{
    public function handle(MoveConfirmed|MoveCancelled|MoveDrafted|MoveReversed $event): void
    {
        if (! Package::isPluginInstalled('sales')) {
            return;
        }

        Order::query()
            ->whereHas('accountMoves', fn ($query) => $query->whereKey($event->move->id))
            ->get()
            ->each(fn (Order $order) => SaleOrderFacade::computeSaleOrder($order));
    }
}
