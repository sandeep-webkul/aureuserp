<?php

namespace Webkul\Support\Filament\Infolists\Components;

use Exception;
use Filament\Forms\Components\Concerns\HasExtraItemActions;
use Filament\Infolists\Components\RepeatableEntry as BaseRepeatableEntry;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Table\Concerns\HasColumnManager;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Js;

class RepeatableEntry extends BaseRepeatableEntry
{
    use HasColumnManager;
    use HasExtraItemActions;

    protected function toEmbeddedTableHtml(): string
    {
        $items = $this->getItems();
        $tableColumns = $this->getTableColumns();
        $extraActions = $this->getExtraItemActions();

        $attributes = $this->getExtraAttributeBag()
            ->class([
                'fi-fo-table-repeater',
                'overflow-x-auto',
            ]);

        Log::info('RepeatableEntry items: ', collect($items)->toArray());

        if (empty($items)) {
            $attributes = $attributes
                ->merge([
                    'x-tooltip' => filled($tooltip = $this->getEmptyTooltip())
                        ? '{
                            content: '.Js::from($tooltip).',
                            theme: $store.theme,
                            allowHTML: '.Js::from($tooltip instanceof Htmlable).',
                        }'
                        : null,
                ], escape: false);

            $placeholder = $this->getPlaceholder();

            ob_start(); ?>

            <div <?= $attributes->toHtml() ?>>
                <?php if (filled($placeholder)) { ?>
                    <p class="fi-in-placeholder">
                        <?= e($placeholder) ?>
                    </p>
                <?php } ?>
            </div>

            <?php return $this->wrapEmbeddedHtml(ob_get_clean());
        }

        ob_start(); ?>

        <div <?= $attributes->toHtml() ?>>
            <table style="width: auto;">
                <thead>
                    <tr>
                        <?php foreach ($tableColumns as $column) { ?>
                            <th
                                class="<?= Arr::toCssClasses([
                                    'fi-wrapped' => $column->canHeaderWrap(),
                                    (($columnAlignment = $column->getAlignment()) instanceof Alignment) ? ('fi-align-'.$columnAlignment->value) : $columnAlignment,
                                ]) ?>"
                                style="<?= filled($columnWidth = $column->getWidth())
                                    ? 'width: '.$columnWidth.'; white-space: nowrap; min-width: fit-content; padding: 15px 5px;'
                                    : 'white-space: nowrap; min-width: fit-content; padding: 15px 5px;' ?>"
                            >
                                <?php if (! $column->isHeaderLabelHidden()) { ?>
                                    <?= e($column->getLabel()) ?>
                                <?php } else { ?>
                                    <span class="fi-sr-only">
                                        <?= e($column->getLabel()) ?>
                                    </span>
                                <?php } ?>
                            </th>
                        <?php } ?>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($items as $index => $item) { ?>
                        <tr>
                            <?php $counter = 0 ?>
                            <?php foreach ($item->getComponents(withHidden: true) as $component) { ?>
                                <?php throw_unless(
                                    $component instanceof Component,
                                    new Exception('Table repeatable entries must only contain schema components, but ['.$component::class.'] was used.'),
                                ) ?>
                                <?php if (count($tableColumns) > $counter) { ?>
                                    <?php $counter++ ?>
                                    <?php if ($component->isVisible()) { ?>
                                        <td>
                                            <div style="min-width: max-content; padding: 6px 2px"><?= $component->toHtml() ?></div>
                                        </td>
                                    <?php } else { ?>
                                        <td class="fi-hidden"></td>
                                    <?php } ?>
                                <?php } ?>
                            <?php } ?>
                            <?php if (! empty($extraActions)) { ?>
                                <td>
                                    <div style="min-width: max-content; padding: 6px 2px" class="flex items-center gap-2">
                                        <?php
                                            foreach ($extraActions as $action) {
                                                $action = $action(['item' => $index]);
                                                ?>
                                                <div x-on:click.stop>
                                                    <?= $action->toHtml() ?>
                                                </div>
                                        <?php } ?>
                                    </div>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <?php return $this->wrapEmbeddedHtml(ob_get_clean());
    }
}
