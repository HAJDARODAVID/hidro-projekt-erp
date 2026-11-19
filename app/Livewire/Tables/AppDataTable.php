<?php

namespace App\Livewire\Tables;

use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

/**
 * Base for the app's rappasoft tables: our look (sharp edges, compact rows) and our defaults.
 * The package is not changed; everything is done through its configuration API and lifecycle hooks.
 *
 * Use: `class MyTable extends AppDataTable` instead of `extends DataTableComponent`.
 * configure() of the table runs after the defaults below, so a table can override any of them,
 * without calling parent::configure().
 *
 * The look lives in public/assets/css/application.css under `.app-table` (only tables extending this class get it).
 */
abstract class AppDataTable extends DataTableComponent
{
    /**CSS class on the wrapper of every table of this kind, the styles hang on it */
    public const WRAPPER_CLASS = 'app-table';

    /**Rows per page offered (the first one is the default), same as our own tables */
    public const PER_PAGE_OPTIONS = [15, 25, 50, 100];

    /**Search waits this long after typing, unless the table sets its own search mode (blur, live, ...) */
    protected int $searchDebounceMs = 300;

    /**Global modal opened by a row click (see openRowModal()), NULL = rows are not clickable this way */
    protected ?string $rowModal = NULL;

    /**Name of the param the row ID is passed to the modal with */
    protected string $rowModalParam = 'id';

    /**The row last opened in the modal (highlighted) */
    public $openedRowId = NULL;

    /**
     * Runs before configure() of the table.
     */
    public function configuring(): void
    {
        $this->setPerPageAccepted(self::PER_PAGE_OPTIONS)
            ->setEmptyMessage(translator('No results.'))
            ->setSearchPlaceholder(translator('Search'))
            ->setTableAttributes(['default' => TRUE, 'class' => 'table-sm table-hover align-middle mb-0'])
            ->setTheadAttributes(['default' => TRUE, 'class' => 'app-table-head']);

        if ($this->rowModal) {
            $this->setTrAttributes(fn($row) => [
                'class'      => 'app-table-clickable' . ((string) $this->openedRowId === (string) $row->{$this->getPrimaryKey()} ? ' app-table-opened' : ''),
                'wire:click' => 'openRowModal(' . json_encode((string) $row->{$this->getPrimaryKey()}) . ')',
            ]);
        }
    }

    /* ------------------------------------------------------ Fit to screen */

    /**The table is at most as high as the screen, the rows scroll (footer stays visible). Off by default. */
    protected bool $fitToScreen = FALSE;

    /**Space (px) kept free under the footer when fitting to the screen */
    protected int $fitToScreenBottomSpace = 12;

    /**Smallest height (px) of the rows area when fitting to the screen */
    protected int $fitToScreenMinHeight = 200;

    /**
     * Fit the table to the screen: with few rows it is as high as its rows, with many rows it ends at the
     * bottom of the screen (footer included) and the rows scroll, the column titles stay on top.
     * Call it in configure(), or set protected bool $fitToScreen = TRUE.
     */
    protected function setFitToScreenEnabled(int $bottomSpace = 12): static
    {
        $this->fitToScreen = TRUE;
        $this->fitToScreenBottomSpace = $bottomSpace;
        return $this;
    }

    /**
     * Alpine on the wrapper of the <table>: measures the free space (screen bottom - top of the rows area - footer)
     * on load, on resize of the window and when the rows change, and sets it as max height. Alpine keeps the
     * bound style when Livewire re-renders.
     */
    private function fitToScreenAttributes(): array
    {
        $space = (int) $this->fitToScreenBottomSpace;
        $min = (int) $this->fitToScreenMinHeight;

        return [
            'default'      => TRUE,
            'class'        => 'app-table-fit',
            'x-data'       => "{ maxHeight: null, fit() {"
                . " const root = this.\$el.closest('.app-table'); if (!root) return;"
                . " const footer = root.querySelector('.app-table-footer-block');"
                . " const footerHeight = footer ? footer.getBoundingClientRect().height : 0;"
                . " this.maxHeight = Math.max({$min}, Math.floor(window.innerHeight - this.\$el.getBoundingClientRect().top - footerHeight - {$space}));"
                . " } }",
            'x-init'       => "\$nextTick(() => fit()); new ResizeObserver(() => fit()).observe(\$el.firstElementChild ?? \$el)",
            'x-on:resize.window.debounce.100ms' => 'fit()',
            'x-bind:style' => "maxHeight ? { maxHeight: maxHeight + 'px' } : {}",
        ];
    }

    /**
     * Runs after configure() of the table.
     */
    public function configured(): void
    {
        if ($this->fitToScreen) $this->setTableWrapperAttributes($this->fitToScreenAttributes());

        $hasSearchMode = $this->hasSearchDebounce() || $this->hasSearchBlur() || $this->hasSearchDefer()
            || $this->hasSearchLazy() || $this->hasSearchLive() || $this->hasSearchThrottle();
        if (!$hasSearchMode && $this->searchDebounceMs > 0) $this->setSearchDebounce($this->searchDebounceMs);
    }

    /**
     * Our class is always on the wrapper, also when a table sets its own wrapper attributes.
     */
    public function getComponentWrapperAttributes(): array
    {
        $attributes = parent::getComponentWrapperAttributes();
        $attributes['class'] = trim(self::WRAPPER_CLASS . ' ' . ($attributes['class'] ?? ''));
        return $attributes;
    }

    /* ------------------------------------------------------------ Toolbar */

    /**Our toolbar (replaces the package toolbar, rendered in the package area before-tools) */
    public const TOOLBAR_VIEW = 'livewire.tables.toolbar';

    /**
     * The package toolbar is replaced by ours, it never renders.
     */
    #[Computed]
    public function shouldShowToolBar(): bool
    {
        return FALSE;
    }

    /**
     * Would the toolbar have anything to show (search, filters, column select, reorder, actions, our areas)?
     * The rows per page are in our footer, not here. setToolBarDisabled() / setToolsDisabled() hide our toolbar too.
     */
    #[Computed]
    public function showAppToolbar(): bool
    {
        if (!$this->getToolsStatus() || !$this->getToolBarStatus()) return FALSE;
        return $this->hasToolbarConfigurableAreas() || $this->hasToolbarActions() || $this->hasToolbarReorder()
            || $this->hasToolbarColumnSelect() || $this->displayToolbarSearch() || $this->displayToolbarFilters();
    }

    /* ------------------------------------------------------------- Footer */

    /**Our footer under the table: rows per page left, pagination right (rendered in the package area before-pagination) */
    public const FOOTER_VIEW = 'livewire.tables.footer';

    /**
     * The package pagination block is replaced by our footer. Its setting still decides:
     * setPaginationVisibilityDisabled() hides our footer.
     */
    public function paginationVisibilityIsEnabled(): bool
    {
        return FALSE;
    }

    public function showAppFooter(): bool
    {
        return parent::paginationVisibilityIsEnabled() || $this->showPaginationDropdown();
    }

    /**
     * Rows per page like our own tables: from the URL, otherwise the default (the first of PER_PAGE_OPTIONS).
     * The package keeps the choice in the session (per table, until signing out); we do not.
     */
    public function mountWithPagination(): void
    {
        $perPage = (int) ($this->perPage ?? 0); // set from the URL (perPage query string) when given
        $this->setPerPage(in_array($perPage, $this->getPerPageAccepted(), FALSE) ? $perPage : $this->getDefaultPerPage());
    }

    /**
     * Runs before every render: the package loads the session value here again; we only check the current value.
     */
    public function setupPagination(): void
    {
        if ($this->paginationIsDisabled()) return;
        if (!in_array((int) $this->getPerPage(), $this->getPerPageAccepted(), FALSE)) $this->setPerPage($this->getDefaultPerPage());
    }

    /**
     * A new rows per page choice: only checked and applied, not stored in the session.
     */
    public function updatedPerPage(int|string $value): void
    {
        $value = (int) $value;
        $this->setPerPage(in_array($value, $this->getPerPageAccepted(), FALSE) ? $value : $this->getDefaultPerPage());
        $this->resetPage($this->getComputedPageName());
    }

    /**
     * The tools block (sorting / filter pills, filter slide-down) keeps working without the package toolbar.
     */
    #[Computed]
    public function shouldShowTools(): bool
    {
        if (!$this->getToolsStatus()) return FALSE;
        $slideDown = $this->filtersAreEnabled() && $this->filtersVisibilityIsEnabled() && $this->hasVisibleFilters() && $this->isFilterLayoutSlideDown();
        return $this->showSortPillsSection() || $this->showFilterPillsSection() || $slideDown;
    }

    /* ------------------------------------------------- Areas we render in */

    /**
     * Package areas our views take: our toolbar in before-tools, our footer in before-pagination.
     * A view the table sets itself for one of these areas is still shown (see the 'own' params).
     */
    private function ourAreas(): array
    {
        return ['before-tools' => self::TOOLBAR_VIEW, 'before-pagination' => self::FOOTER_VIEW];
    }

    public function hasConfigurableAreaFor(string $area): bool
    {
        if ($area === 'before-tools') return $this->showAppToolbar() || parent::hasConfigurableAreaFor($area);
        if ($area === 'before-pagination') return $this->showAppFooter() || parent::hasConfigurableAreaFor($area);
        return parent::hasConfigurableAreaFor($area);
    }

    public function getConfigurableAreaFor(string $area): ?string
    {
        return $this->ourAreas()[$area] ?? parent::getConfigurableAreaFor($area);
    }

    public function getParametersForConfigurableArea(string $area): array
    {
        if (!isset($this->ourAreas()[$area])) return parent::getParametersForConfigurableArea($area);
        $own = parent::hasConfigurableAreaFor($area) ? parent::getConfigurableAreaFor($area) : NULL;
        return ['ownArea' => $own, 'ownAreaParams' => $own ? parent::getParametersForConfigurableArea($area) : []];
    }

    /**Toolbar areas of the package: left = around the search input, right = around the per-page select */
    public const TOOLBAR_AREAS = ['toolbar-left-start', 'toolbar-left-end', 'toolbar-right-start', 'toolbar-right-end'];

    /**Global modals the toolbar buttons of this table may open (filled by toolbarButtons()) */
    protected array $toolbarModals = [];

    /**
     * Our buttons in the toolbar, by default right after the search input. Call it in configure().
     *
     * Each button: text, icon (bootstrap icon), type (pri | lig | dan | suc | war | ..., like <x-ui.btn>), title,
     * can (ACL permission, the button is hidden without it), visible (bool, hidden when FALSE), confirm (question before running) and one of:
     *   'action' => 'methodOfTheTable' (+ 'param')     a method of this table
     *   'modal'  => 'global-modal-name' (+ 'params')   a global modal (config/global-modal.php)
     *   'route'  => 'route.name' (+ 'params')          a link
     *
     * Exp: $this->toolbarButtons([['text' => translator('New user'), 'icon' => 'person-plus', 'type' => 'pri', 'modal' => 'admin-user']]);
     */
    protected function toolbarButtons(array $buttons, string $area = 'toolbar-left-end'): static
    {
        $acl = \App\Services\Application\Acl\AclService::current();
        $visible = [];
        foreach ($buttons as $button) {
            if (!empty($button['can']) && !$acl->can($button['can'])) continue;
            if (array_key_exists('visible', $button) && !$button['visible']) continue;
            if (!empty($button['modal'])) $this->toolbarModals[] = $button['modal'];
            $visible[] = $button + ['text' => NULL, 'icon' => NULL, 'type' => 'lig', 'title' => NULL, 'confirm' => NULL];
        }
        if (!$visible) return $this; // nothing the user may use: no empty area in the toolbar

        return $this->toolbarView($area, 'livewire.tables.toolbar-buttons', ['buttons' => $visible]);
    }

    /**
     * Any of our own Blade in a toolbar area (filters, selects, info ...). The view runs in the context of the table,
     * so wire:model / wire:click talk to the table component. Call it in configure().
     */
    protected function toolbarView(string $area, string $view, array $params = []): static
    {
        if (!in_array($area, self::TOOLBAR_AREAS, TRUE)) throw new \InvalidArgumentException("Unknown toolbar area: {$area}");
        $this->setConfigurableArea($area, [$view, $params]);
        return $this;
    }

    /**
     * A toolbar button with 'modal': open it (only the modals of this table's buttons).
     */
    public function openToolbarModal(string $modal, array $params = []): void
    {
        if (!in_array($modal, $this->toolbarModals, TRUE)) return;
        $this->dispatch('open-global-modal', component: $modal, params: $params);
    }

    /**
     * Row click (set $rowModal): open the row in the global modal (config/global-modal.php).
     * Buttons inside a row should stop the click (wire:click.stop / onclick="event.stopPropagation()").
     */
    public function openRowModal($id): void
    {
        if (!$this->rowModal) return;
        $this->openedRowId = $id;
        $this->dispatch('open-global-modal', component: $this->rowModal, params: [$this->rowModalParam => $id]);
    }

    /* ------------------------------------------------------------ Columns */

    /**
     * Date (or date time) column, shown in our format; empty values as "–".
     */
    protected function dateColumn(string $title, string $field, string $format = 'd.m.Y'): Column
    {
        return Column::make($title, $field)
            ->format(function ($value) use ($format) {
                if ($value === NULL || $value === '') return '–';
                try {
                    return \Illuminate\Support\Carbon::parse($value)->format($format);
                } catch (\Throwable $th) {
                    return $value;
                }
            });
    }

    /**
     * Active / inactive badge for a boolean (or 1/0) field.
     */
    protected function statusColumn(string $title, string $field, string $activeLabel = 'Active', string $inactiveLabel = 'Inactive'): Column
    {
        return Column::make($title, $field)
            ->format(fn($value) => self::badge(
                (bool) $value ? translator($activeLabel) : translator($inactiveLabel),
                (bool) $value ? 'success' : 'secondary'
            ))
            ->html();
    }

    /**
     * Badge column: the value mapped to a label and a color, e.g. [1 => ['Active', 'success'], 0 => ['Inactive', 'secondary']].
     * Values not in the map are shown as they are.
     */
    protected function badgeColumn(string $title, string $field, array $map): Column
    {
        return Column::make($title, $field)
            ->format(function ($value) use ($map) {
                // compare as strings, so 1, '1' and TRUE-ish DB values match the same key
                foreach ($map as $key => $badge) {
                    if ((string) $key === (string) $value) {
                        [$label, $color] = array_values((array) $badge) + [NULL, 'secondary'];
                        return self::badge(translator((string) $label), (string) $color);
                    }
                }
                return e((string) $value);
            })
            ->html();
    }

    /**Our badge (sharp edges), escaped */
    public static function badge(string $label, string $color = 'secondary'): HtmlString
    {
        $color = preg_match('/^[a-z-]+$/', $color) ? $color : 'secondary';
        return new HtmlString('<span class="badge text-bg-' . $color . ' no-border-radius">' . e($label) . '</span>');
    }
}
