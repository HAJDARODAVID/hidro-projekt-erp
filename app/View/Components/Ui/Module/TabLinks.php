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
     * passes custom tab links) or an array as returned by GetModuleRoutesForTabLinks
     * with a divider side (NULL, 'left' or 'right').
     * A divider is shown between two tabs if the first one has it on the right
     * or the second one has it on the left, so it doesn't depend on the neighbour tab.
     * Dividers before the first and after the last tab are ignored.
     *
     * @return array
     */
    private function normalizeRoutes($routes)
    {
        $routeNames = array_keys($routes);
        $dividers = array_map(
            fn ($link) => is_array($link) ? ($link['divider'] ?? NULL) : NULL,
            array_values($routes)
        );

        foreach ($routeNames as $index => $routeName) {
            $link = $routes[$routeName];
            $hasNext = isset($routeNames[$index + 1]);
            $routes[$routeName] = [
                'title'         => is_array($link) ? ($link['title'] ?? NULL) : $link,
                'divider_after' => $hasNext && ($dividers[$index] === 'right' || $dividers[$index + 1] === 'left'),
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
