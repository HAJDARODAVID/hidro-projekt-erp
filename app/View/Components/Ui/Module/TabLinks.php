<?php

namespace App\View\Components\Ui\Module;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

class TabLinks extends Component
{
    public $routes;
    public $specialIndexIcon;
    /**
     * Create a new component instance.
     */
    public function __construct(
        $routes = [],
        $specialIndexIcon = NULL,
    ) {
        $this->routes = $this->normalizeRoutes($this->checkIfRoutesExists($routes));
        $this->specialIndexIcon = $specialIndexIcon;
    }

    /**
     * Check if the given route exists
     * 
     * @return array
     */
    private function checkIfRoutesExists($routes)
    {
        foreach ($routes as $routeName => $title) {
            if (!(Route::has($routeName))) unset($routes[$routeName]);
        }
        return $routes;
    }

    /**
     * Make sure every route is an array with a title and a divider_after flag.
     * The value can be a plain title (Exp: home => 'Home', used when a controller
     * passes custom tab links) or an array as returned by GetModuleRoutesForTabLinks.
     * 
     * @return array
     */
    private function normalizeRoutes($routes)
    {
        foreach ($routes as $routeName => $link) {
            $routes[$routeName] = [
                'title'         => is_array($link) ? ($link['title'] ?? NULL) : $link,
                'divider_after' => is_array($link) ? (bool) ($link['divider_after'] ?? FALSE) : FALSE,
            ];
        }
        return $routes;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.module.tab-links');
    }
}
