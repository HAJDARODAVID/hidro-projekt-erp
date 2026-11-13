<?php

namespace App\View\Components\Ui;

use App\Models\User;
use App\Services\Application\Menu\MenuRenderer;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

/**
 * A menu built with the menu builder (app control center > Menu builder).
 *
 * <x-ui.app-menu />                         the "main" menu for the logged in user
 * <x-ui.app-menu menu="reports" />          another menu (key or ID)
 * <x-ui.app-menu :user="$user" :interactive="false" />   preview: as another user, links do not navigate
 */
class AppMenu extends Component
{
    public array $items;

    public string $uid;

    public function __construct(
        public string|int $menu = \App\Models\Application\AppMenu::KEY_MAIN,
        public ?User $user = NULL,
        public ?string $currentRoute = NULL,
        public bool $interactive = TRUE,
        public bool $preview = FALSE,
    ) {
        $this->user ??= Auth::user();
        $this->items = MenuRenderer::for($this->user)->tree($menu, $currentRoute, $preview);
        $this->uid = 'app-menu-' . (is_int($menu) || ctype_digit((string) $menu) ? 'id' . $menu : $menu);
    }

    public function render(): View|Closure|string
    {
        return view('components.ui.app-menu');
    }
}
