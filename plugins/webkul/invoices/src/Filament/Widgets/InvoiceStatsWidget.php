<?php

namespace Webkul\Invoice\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\Invoice\Models\Invoice;

class InvoiceStatsWidget extends BaseWidget
{
    use HasWidgetShield;
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $query = Invoice::query();

        $filters = $this->filters;

        if (! empty($filters['start_date'])) {
            $query->whereDate('invoice_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('invoice_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['salesperson_id'])) {
            $query->where('invoice_user_id', $filters['salesperson_id']);
        }

        if (! empty($filters['product_id'])) {
            $query->whereHas('lines', function ($q) use ($filters) {
                $q->where('display_type', 'product')
                    ->where('product_id', $filters['product_id']);
            });
        }

        $invoices = $query->get();

        $totalInvoiced = $invoices->sum('amount_total');
        $unpaidAmount = $invoices->where('payment_state', 'not_paid')->sum('amount_total');

        $invoiceCount = $invoices->count();
        $averageInvoice = $invoiceCount > 0 ? $totalInvoiced / $invoiceCount : 0;

        $paidCount = $invoices->where('payment_state', 'paid')->count();
        $unpaidCount = $invoices->where('payment_state', 'not_paid')->count();

        return [
            Stat::make('Invoiced', number_format($totalInvoiced, 2))
                ->description('Unpaid: '.number_format($unpaidAmount, 2))
                ->color('primary'),

            Stat::make('Average Invoice', number_format($averageInvoice, 2))
                ->description("Count: {$invoiceCount}")
                ->color('info'),

            Stat::make('Paid Invoices', $paidCount)
                ->color('success'),

            Stat::make('Unpaid Invoices', $unpaidCount)
                ->color('danger'),
        ];
    }
}
