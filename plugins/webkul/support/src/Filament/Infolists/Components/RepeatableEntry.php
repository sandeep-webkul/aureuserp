<?php

namespace Webkul\Support\Filament\Infolists\Components;

use Exception;
use Filament\Infolists\Components\RepeatableEntry as BaseRepeatableEntry;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\Js;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ComponentSlot;

use function Filament\Support\generate_href_html;

class RepeatableEntry extends BaseRepeatableEntry
{
    protected function toEmbeddedTableHtml(): string
    {
        $items = $this->getItems();
        $tableColumns = $this->getTableColumns();

        $attributes = $this->getExtraAttributeBag()
            ->class([
                'fi-in-table-repeatable',
            ])->merge([
                'style' => 'overflow-x: auto; max-width: 100%;',
            ], escape: false);

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
            <table>
                <thead>
                    <tr>
                        <?php foreach ($tableColumns as $column) { ?>
                            <th
                                class="<?= Arr::toCssClasses([
                                    'fi-wrapped' => $column->canHeaderWrap(),
                                    (($columnAlignment = $column->getAlignment()) instanceof Alignment) ? ('fi-align-'.$columnAlignment->value) : $columnAlignment,
                                ]) ?>"
                                <?php if (filled($columnWidth = $column->getWidth())) { ?>
                                    style="width: <?= $columnWidth ?>"
                                <?php } ?>
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
                    <?php foreach ($items as $item) { ?>
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
                                            <?= $component->toHtml() ?>
                                        </td>
                                    <?php } else { ?>
                                        <td class="fi-hidden"></td>
                                    <?php } ?>
                                <?php } ?>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <?php return $this->wrapEmbeddedHtml(ob_get_clean());
    }

    public function wrapEmbeddedHtml(string $html): string
    {
        $view = $this->getEntryWrapperAbsoluteView();

        if ($view !== 'filament-infolists::components.entry-wrapper') {
            return view($this->getEntryWrapperAbsoluteView(), [
                'entry' => $this,
                'slot'  => new ComponentSlot($html),
            ])->toHtml();
        }

        $hasInlineLabel = $this->hasInlineLabel();
        $alignment = $this->getAlignment();
        $label = $this->getLabel();
        $labelSrOnly = $this->isLabelHidden();
        $action = $this->getAction();
        $url = $this->getUrl();

        $wrapperTag = match (true) {
            filled($url)    => 'a',
            filled($action) => 'button',
            default         => 'div',
        };

        if (! $alignment instanceof Alignment) {
            $alignment = filled($alignment) ? (Alignment::tryFrom($alignment) ?? $alignment) : null;
        }

        $aboveLabelSchema = $this->getChildSchema($this::ABOVE_LABEL_SCHEMA_KEY)?->toHtmlString();
        $belowLabelSchema = $this->getChildSchema($this::BELOW_LABEL_SCHEMA_KEY)?->toHtmlString();
        $beforeLabelSchema = $this->getChildSchema($this::BEFORE_LABEL_SCHEMA_KEY)?->toHtmlString();
        $afterLabelSchema = $this->getChildSchema($this::AFTER_LABEL_SCHEMA_KEY)?->toHtmlString();
        $beforeContentSchema = $this->getChildSchema($this::BEFORE_CONTENT_SCHEMA_KEY)?->toHtmlString();
        $afterContentSchema = $this->getChildSchema($this::AFTER_CONTENT_SCHEMA_KEY)?->toHtmlString();

        $attributes = $this->getExtraEntryWrapperAttributesBag()
            ->class([
                'fi-in-entry',
                'fi-in-entry-has-inline-label' => $hasInlineLabel,
            ])->merge([
                'style' => 'width: 100%;',
            ], escape: false);

        $contentAttributes = (new ComponentAttributeBag)
            ->merge([
                'type'              => ($wrapperTag === 'button') ? 'button' : null,
                'wire:click'        => $wireClickAction = $action?->getLivewireClickHandler(),
                'wire:loading.attr' => ($wrapperTag === 'button') ? 'disabled' : null,
                'wire:target'       => $wireClickAction,
            ], escape: false)
            ->class([
                'fi-in-entry-content',
                (($alignment instanceof Alignment) ? "fi-align-{$alignment->value}" : (is_string($alignment) ? $alignment : '')),
            ]);

        ob_start(); ?>

        <div <?= $attributes->toHtml() ?>>
            <?php if (filled($label) && $labelSrOnly) { ?>
                <dt class="fi-in-entry-label fi-hidden">
                    <?= e($label) ?>
                </dt>
            <?php } ?>

            <?php if ((filled($label) && (! $labelSrOnly)) || $hasInlineLabel || $aboveLabelSchema || $belowLabelSchema || $beforeLabelSchema || $afterLabelSchema) { ?>
                <div class="fi-in-entry-label-col">
                    <?= $aboveLabelSchema?->toHtml() ?>

                    <?php if ((filled($label) && (! $labelSrOnly)) || $beforeLabelSchema || $afterLabelSchema) { ?>
                        <div class="fi-in-entry-label-ctn">
                            <?= $beforeLabelSchema?->toHtml() ?>

                            <?php if (filled($label) && (! $labelSrOnly)) { ?>
                                <dt class="fi-in-entry-label">
                                    <?= e($label) ?>
                                </dt>
                            <?php } ?>

                            <?= $afterLabelSchema?->toHtml() ?>
                        </div>
                    <?php } ?>

                    <?= $belowLabelSchema?->toHtml() ?>
                </div>
            <?php } ?>

            <div class="fi-in-entry-content-col">
                <?= $this->getChildSchema($this::ABOVE_CONTENT_SCHEMA_KEY)?->toHtml() ?>

                <dd class="fi-in-entry-content-ctn">
                    <?= $beforeContentSchema?->toHtml() ?>

                    <<?= $wrapperTag ?> <?php if ($wrapperTag === 'a') {
                        echo generate_href_html($url, $this->shouldOpenUrlInNewTab())->toHtml();
                    } ?> <?= $contentAttributes->toHtml() ?>>
                        <?= $html ?>
                    </<?= $wrapperTag ?>>

                    <?= $afterContentSchema?->toHtml() ?>
                </dd>

                <?= $this->getChildSchema($this::BELOW_CONTENT_SCHEMA_KEY)?->toHtml() ?>
            </div>
        </div>

        <?php return ob_get_clean();
    }
}
