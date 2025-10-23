<?php

namespace Webkul\Support\Filament\Infolists\Components;

use Closure;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Concerns\HasExtraItemActions;
use Filament\Infolists\Components\RepeatableEntry as BaseRepeatableEntry;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Size;
use Filament\Tables\Table\Concerns\HasColumnManager;
use Illuminate\Support\Arr;
use Webkul\Support\Filament\Infolists\Components\Repeater\TableColumn;

class RepeatableEntry extends BaseRepeatableEntry
{
    use HasColumnManager;
    use HasExtraItemActions;

    protected ?string $columnManagerSessionKey = null;

    protected bool|Closure|null $isRepeaterHasTableView = false;

    public function table(array|Closure|null $columns): static
    {
        $this->isRepeaterHasTableView = true;
        $this->tableColumns = $columns;

        return $this;
    }

    public function getColumnManagerSessionKey(): string
    {
        return $this->columnManagerSessionKey ??= 'repeatable_entry_'.$this->getStatePath().'_column_manager';
    }

    public function getMappedColumns(): array
    {
        $columns = $this->evaluate($this->tableColumns);

        if (! is_array($columns)) {
            $columns = [];
        }

        $savedState = session($this->getColumnManagerSessionKey(), []);

        return collect($columns)->map(function (TableColumn $column) use ($savedState): array {
            $columnName = $column->getName();

            $isToggled = data_get($savedState, "{$columnName}.isToggled", ! $column->isToggledHiddenByDefault());

            return [
                'type'                     => 'column',
                'name'                     => $columnName,
                'label'                    => $column->getLabel(),
                'isHidden'                 => $column->isHidden(),
                'isToggled'                => $isToggled,
                'isToggleable'             => $column->isToggleable(),
                'isToggledHiddenByDefault' => $column->isToggledHiddenByDefault(),
            ];
        })->toArray();
    }

    public function getTableColumns(): array
    {
        $columns = $this->evaluate($this->tableColumns);

        if (! is_array($columns)) {
            $columns = [];
        }

        $savedState = session($this->getColumnManagerSessionKey(), []);

        $visibleColumns = collect($columns)->filter(function (TableColumn $column) use ($savedState): bool {
            if ($column->isHidden()) {
                return false;
            }

            $columnName = $column->getName();

            if (data_get($savedState, $columnName)) {
                return data_get($savedState, "{$columnName}.isToggled", false);
            }

            return ! $column->isToggledHiddenByDefault();
        });

        return $visibleColumns->values()->toArray();
    }

    public function hasToggleableColumns(): bool
    {
        $columns = $this->evaluate($this->tableColumns) ?? [];

        return collect($columns)->contains(fn ($column) => $column->isToggleable());
    }

    public function getColumnManagerTriggerAction(): Action
    {
        $action = Action::make('openColumnManager')
            ->label(__('filament-tables::table.actions.column_manager.label'))
            ->iconButton()
            ->icon('heroicon-s-view-columns')
            ->color('gray')
            ->livewireClickHandlerEnabled(false)
            ->authorize(true);

        if ($this->modifyColumnManagerTriggerActionUsing) {
            $action = $this->evaluate($this->modifyColumnManagerTriggerActionUsing, [
                'action' => $action,
            ]) ?? $action;
        }

        if ($action->getView() === Action::BUTTON_VIEW) {
            $action->defaultSize(Size::Small->value);
        }

        return $action;
    }

    public function applyTableColumnManager(?array $columns = null): void
    {
        if (blank($columns)) {
            return;
        }

        $columnState = collect($columns)
            ->filter(fn ($column) => filled(data_get($column, 'name')) && ! is_null(data_get($column, 'isToggled')))
            ->mapWithKeys(fn ($column) => [
                data_get($column, 'name') => [
                    'isToggled'    => data_get($column, 'isToggled'),
                    'isToggleable' => data_get($column, 'isToggleable', true),
                ],
            ])
            ->toArray();

        session([$this->getColumnManagerSessionKey() => $columnState]);

        // 🔄 Refresh UI
        $this->dispatch('refresh');
    }

    public function resetTableColumnManager(): void
    {
        session()->forget($this->getColumnManagerSessionKey());
    }

    public function hasDeferredColumnManager(): bool
    {
        return false;
    }

    protected function toEmbeddedTableHtml(): string
    {
        $items = $this->getItems();
        $visibleColumns = $this->getTableColumns();

        $isReorderableWithButtons = method_exists($this, 'isReorderableWithButtons') && $this->isReorderableWithButtons();
        $isReorderableWithDragAndDrop = method_exists($this, 'isReorderableWithDragAndDrop') && $this->isReorderableWithDragAndDrop();

        $isCloneable = method_exists($this, 'isCloneable') && $this->isCloneable();
        $isDeletable = method_exists($this, 'isDeletable') && $this->isDeletable();

        $extraItemActions = property_exists($this, 'extraItemActions') ? $this->extraItemActions : [];
        $hasColumnManagerDropdown = $this->hasToggleableColumns();
        $columnManagerApplyAction = $this->getColumnManagerTriggerAction();
        $mappedColumns = $this->getMappedColumns();

        $attributes = $this->getExtraAttributeBag()->class(['fi-fo-table-repeater', 'overflow-x-auto']);

        if (empty($items)) {
            $placeholder = $this->getPlaceholder();

            ob_start(); ?>
            <div <?= $attributes->toHtml() ?>>
                <?php if (filled($placeholder)) { ?>
                    <p class="fi-in-placeholder"><?= e($placeholder) ?></p>
                <?php } ?>
            </div>
            <?php return $this->wrapEmbeddedHtml(ob_get_clean());
        }

        ob_start(); ?>

        <div <?= $attributes->toHtml() ?>>
            <table style="width: auto;">
                <thead>
                    <tr>
                        <?php if ((count($items) > 1) && ($isReorderableWithButtons || $isReorderableWithDragAndDrop)) { ?>
                            <th style="width: 45px"></th>
                        <?php } ?>

                        <?php foreach ($visibleColumns as $tableColumn) { ?>
                            <th
                                class="<?= Arr::toCssClasses([
                                    'fi-wrapped' => $tableColumn->canHeaderWrap(),
                                    (($columnAlignment = $tableColumn->getAlignment()) instanceof Alignment)
                                        ? ('fi-align-'.$columnAlignment->value)
                                        : $columnAlignment,
                                ]) ?>"
                                style="<?= filled($columnWidth = $tableColumn->getWidth())
                                    ? 'width: '.$columnWidth.'; white-space: nowrap; min-width: fit-content; padding: 15px 5px;'
                                    : 'white-space: nowrap; min-width: fit-content; padding: 15px 5px;' ?>"
                            >
                                <?php if (! $tableColumn->isHeaderLabelHidden()) { ?>
                                    <?= e($tableColumn->getLabel()) ?>
                                <?php } else { ?>
                                    <span class="fi-sr-only"><?= e($tableColumn->getLabel()) ?></span>
                                <?php } ?>
                            </th>
                        <?php } ?>

                        <?php if (count($extraItemActions) || $isCloneable || $isDeletable) { ?>
                            <th class="text-center align-middle">
                                <?php if ($hasColumnManagerDropdown) { ?>
                                    <div x-data="{ open: false, toggle() { this.open = !this.open } }">
                                        <button type="button" @click="toggle" class="fi-icon-btn fi-size-md fi-ac-icon-btn-action">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="fi-icon fi-size-md" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true" data-slot="icon">
                                                <path d="M15 3.75H9v16.5h6V3.75ZM16.5 20.25h3.375c1.035 0 1.875-.84 1.875-1.875V5.625c0-1.036-.84-1.875-1.875-1.875H16.5v16.5ZM4.125 3.75H7.5v16.5H4.125a1.875 1.875 0 0 1-1.875-1.875V5.625c0-1.036.84-1.875 1.875-1.875Z" />
                                            </svg>
                                        </button>

                                        <div x-show="open" @click.outside="open = false" class="absolute right-0 z-10 mt-2 w-64 rounded-lg border border-gray-200 bg-white shadow-lg p-2">
                                            <h2 class="font-semibold text-gray-700 mb-2"><?= __('Manage Columns') ?></h2>

                                            <?php foreach ($mappedColumns as $column) { ?>
                                                <label class="flex items-center justify-between py-1">
                                                    <span><?= e($column['label']) ?></span>
                                                    <input type="checkbox"
                                                        <?= $column['isToggled'] ? 'checked' : '' ?>
                                                        @change="$wire.applyTableColumnManager([{ name: '<?= e($column['name']) ?>', isToggled: $event.target.checked }])"
                                                    >
                                                </label>
                                            <?php } ?>

                                            <div class="mt-3 text-right">
                                                <button type="button" class="px-3 py-1 text-sm rounded bg-primary-600 text-white hover:bg-primary-700"
                                                    @click="$wire.applyTableColumnManager()">
                                                    <?= e($columnManagerApplyAction->getLabel()) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </th>
                        <?php } ?>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($items as $itemKey => $item) { ?>
                        <?php
                            $visibleExtraItemActions = collect($extraItemActions)->filter(fn (Action $action) => $action(['record' => $itemKey])->isVisible())->values()->all();
                        ?>
                        <tr>
                            <?php if ((count($items) > 1) && ($isReorderableWithButtons || $isReorderableWithDragAndDrop)) { ?>
                                <td style="width: 45px;"></td>
                            <?php } ?>

                            <?php foreach ($item->getComponents(withHidden: true) as $component) { ?>
                                <?php throw_unless(
                                    $component instanceof Component,
                                    new Exception('Table repeatable entries must only contain schema components, but ['.$component::class.'] was used.')
                                ); ?>

                                <?php if ($component->isVisible()) { ?>
                                    <td><div style="min-width: max-content; padding:6px 2px"><?= $component->toHtml() ?></div></td>
                                <?php } else { ?>
                                    <td class="fi-hidden"></td>
                                <?php } ?>
                            <?php } ?>

                            <?php if (count($visibleExtraItemActions) || $isCloneable || $isDeletable) { ?>
                                <td>
                                    <?php if (count($visibleExtraItemActions) > 0) { ?>
                                        <div class="items-center justify-center gap-2">
                                            <?php foreach ($visibleExtraItemActions as $extraItemAction) { ?>
                                                <div>
                                                    <?= $extraItemAction(['item' => $itemKey])->toHtml() ?>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
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
