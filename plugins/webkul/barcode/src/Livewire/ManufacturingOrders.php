<?php

namespace Webkul\Barcode\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Webkul\Manufacturing\Enums\ManufacturingOrderState;
use Webkul\Manufacturing\Models\Order;
use Webkul\Product\Models\Product;

class ManufacturingOrders extends Component
{
    public string $search = '';

    public ?string $notice = null;

    public string $noticeColor = 'info';

    public array $matchingOrderIds = [];

    public function openOrder()
    {
        $this->notice = null;
        $this->noticeColor = 'info';

        $orders = $this->findMatchingOrders($this->search);

        if ($orders->isEmpty()) {
            $this->matchingOrderIds = [];

            $this->notice = __('barcode::app.manufacturing.not-found');
            $this->noticeColor = 'warning';
            $this->dispatchNativeFeedback($this->notice);

            return null;
        }

        if ($orders->count() === 1) {
            return $this->redirectRoute('barcode.manufacturing-order', [
                'order' => $orders->first(),
                'scan'  => $this->normalizeBarcode($this->search),
            ], navigate: true);
        }

        $this->matchingOrderIds = $orders->pluck('id')->all();

        $this->notice = __('barcode::app.manufacturing.multiple-found', ['count' => $orders->count()]);
        $this->noticeColor = 'info';
        $this->dispatchNativeFeedback($this->notice);

        return null;
    }

    public function updatedSearch(): void
    {
        if ($this->search === '') {
            $this->matchingOrderIds = [];

            $this->notice = null;
            $this->noticeColor = 'info';
        }
    }

    public function render(): View
    {
        $orders = $this->matchingOrderIds !== []
            ? $this->baseQuery()->whereIn('id', $this->matchingOrderIds)->get()
            : $this->openOrders();

        return view('barcode::livewire.manufacturing-orders', [
            'orders' => $orders,
        ])->layout('barcode::layouts.app', [
            'title' => __('barcode::app.manufacturing.title'),
        ]);
    }

    private function baseQuery(): Builder
    {
        return Order::query()
            ->with(['product', 'uom', 'billOfMaterial'])
            ->orderByRaw('deadline_at is null')
            ->orderBy('deadline_at')
            ->orderBy('id');
    }

    private function openOrders(): Collection
    {
        return $this->baseQuery()
            ->whereNotIn('state', [
                ManufacturingOrderState::DONE->value,
                ManufacturingOrderState::CANCEL->value,
            ])
            ->when($this->search !== '', function (Builder $query): void {
                $search = mb_strtolower($this->search);

                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->whereRaw('LOWER(name) like ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(reference) like ?', ["%{$search}%"])
                        ->orWhereHas('product', function (Builder $productQuery) use ($search): void {
                            $productQuery
                                ->whereRaw('LOWER(name) like ?', ["%{$search}%"])
                                ->orWhereRaw('LOWER(reference) like ?', ["%{$search}%"])
                                ->orWhereRaw('LOWER(barcode) like ?', ["%{$search}%"]);
                        });
                });
            })
            ->limit(200)
            ->get();
    }

    private function findMatchingOrders(string $barcode): Collection
    {
        $barcode = $this->normalizeBarcode($barcode);

        if ($barcode === '') {
            return collect();
        }

        $orders = $this->baseQuery()
            ->whereNotIn('state', [
                ManufacturingOrderState::DONE->value,
                ManufacturingOrderState::CANCEL->value,
            ])
            ->where(function (Builder $query) use ($barcode): void {
                $query->whereRaw(db_dialect()->caseInsensitiveEquals('name'), [$barcode])
                    ->orWhereRaw(db_dialect()->caseInsensitiveEquals('reference'), [$barcode]);
            })
            ->get();

        if ($orders->isNotEmpty()) {
            return $orders;
        }

        if (! $product = $this->findProduct($barcode)) {
            return collect();
        }

        return $this->baseQuery()
            ->whereNotIn('state', [
                ManufacturingOrderState::DONE->value,
                ManufacturingOrderState::CANCEL->value,
            ])
            ->where('product_id', $product->id)
            ->get();
    }

    private function findProduct(string $barcode): ?Product
    {
        return Product::query()
            ->whereRaw(db_dialect()->caseInsensitiveEquals('barcode'), [$barcode])
            ->orWhereRaw(db_dialect()->caseInsensitiveEquals('reference'), [$barcode])
            ->first();
    }

    private function normalizeBarcode(string $barcode): string
    {
        $barcode = trim($barcode);
        $barcode = preg_replace('/\s+/', ' ', $barcode) ?: '';

        return trim($barcode, " \t\n\r\0\x0B#");
    }

    private function dispatchNativeFeedback(?string $message, bool $vibrate = false, string $duration = 'short'): void
    {
        if (! $message) {
            return;
        }

        $this->dispatch('barcode-native-feedback', message: $message, vibrate: $vibrate, duration: $duration);
    }
}
