<?php

namespace Webkul\Purchase\Filament\Admin\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Purchase\Models\PurchaseOrder;
use Webkul\Purchase\Models\Bill;

class PurchaseStatsWidget extends BaseWidget
{
    use HasWidgetShield;
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $filters = $this->filters;

        $query = PurchaseOrder::query();

        // Apply filters
        if (!empty($filters['start_date'])) {
            $query->whereDate('order_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('order_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['salesperson_id'])) {
            $query->where('user_id', $filters['salesperson_id']);
        }

        if (!empty($filters['product_id'])) {
            $query->whereHas('lines', function ($q) use ($filters) {
                $q->where('product_id', $filters['product_id']);
            });
        }

        if (!empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        $orders = $query->get();

        $totalValue = $orders->sum('total');
        $totalOrders = $orders->count();

        $pendingDelivery = $orders->where('status', 'ordered')->whereNull('received_at')->count();
        $pendingApproval = $orders->where('status', 'quotation_pending')->count();

        $receivedPendingPayment = $orders
            ->where('status', 'received')
            ->where('payment_status', '!=', 'paid')
            ->count();

        // Fetch pending bills (if separate model)
        $billQuery = Bill::query();

        if (!empty($filters['start_date'])) {
            $billQuery->whereDate('bill_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $billQuery->whereDate('bill_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['vendor_id'])) {
            $billQuery->where('vendor_id', $filters['vendor_id']);
        }

        $pendingBills = $billQuery->where('status', '!=', 'paid')->count();

        return [
            Stat::make('Total Purchase Orders', $totalOrders)
                ->color('primary'),

            Stat::make('Total Purchase Value', number_format($totalValue, 2))
                ->color('success'),

            Stat::make('Pending Delivery', $pendingDelivery)
                ->description('Orders not yet received')
                ->color('warning'),

            Stat::make('Pending Quotation Approval', $pendingApproval)
                ->color('gray'),

            Stat::make('Received Pending Payment', $receivedPendingPayment)
                ->color('danger'),

            Stat::make('Pending Bills', $pendingBills)
                ->color('danger'),
        ];
    }
}
