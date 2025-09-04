<?php

namespace Webkul\Invoice\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Invoice\Models\Invoice;

class RevenueOverTimeWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Revenue Over Time';

    protected static bool $isLazy = false; // Ensure it loads immediately

    protected function getType(): string
    {
        return 'line'; // Can be 'line' or 'bar'
    }

    public function getColumnSpan(): int|string
    {
        return 'full'; // 'full' = 12 columns, full width
    }

    protected function getData(): array
    {
        $query = Invoice::query()
            ->where('payment_state', 'paid');

        // 🧠 Apply dashboard filters
        if (! empty($this->filters['start_date'])) {
            $query->whereDate('invoice_date', '>=', $this->filters['start_date']);
        }

        if (! empty($this->filters['end_date'])) {
            $query->whereDate('invoice_date', '<=', $this->filters['end_date']);
        }

        if (! empty($this->filters['salesperson_id'])) {
            $query->where('invoice_user_id', $this->filters['salesperson_id']);
        }

        if (! empty($this->filters['product_id'])) {
            $query->whereHas('lines', function (Builder $q) {
                $q->where('display_type', 'product')
                    ->where('product_id', $this->filters['product_id']);
            });
        }

        // 📅 Group by day and sum amount_total
        $results = $query->selectRaw('DATE(invoice_date) as date, SUM(amount_total) as total')
            ->groupByRaw('DATE(invoice_date)')
            ->orderByRaw('DATE(invoice_date)')
            ->get();

        // 🧾 Convert to labels and data
        $labels = $results->pluck('date')->map(fn ($date) => date('M d', strtotime($date)))->toArray();
        $data = $results->pluck('total')->map(fn ($amount) => round((float) $amount, 2))->toArray();

        return [
            'datasets' => [
                [
                    'label'           => 'Revenue',
                    'data'            => $data,
                    'backgroundColor' => '#3b82f6', // Optional styling
                ],
            ],
            'labels' => $labels,
        ];
    }
}
