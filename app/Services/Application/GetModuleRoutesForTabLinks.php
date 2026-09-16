<?php

namespace App\Services\Application;

use App\Models\Application\AppModule;
use App\Models\Application\AppModuleRoute;

/**
 * Class GetModuleRoutes.
 */
class GetModuleRoutesForTabLinks
{
    /**
     * All the routes for given module
     */
    private $moduleRoutes;

    private function __construct($where, $what)
    {
        $module = AppModule::where($where, $what)->first();
        if ($module == NULL) {
            $this->moduleRoutes = [];
        } else {
            $this->moduleRoutes = AppModuleRoute::where('module_id', $module->id)
                ->where('active', TRUE)
                ->orderBy('position', 'ASC')
                ->get();
        }
    }

    /**
     * Create a new instance with the model based on the controller name.
     * 
     * @param string $controller Define the controller name
     * @return self return a new instance
     */
    public static function byController(string $controller)
    {
        return new self('controller', $controller);
    }

    /**
     * This will return a array of the routes.
     * The key is the route name, the value holds the tab title and if a
     * vertical divider should be shown after the tab (see the "Divider after"
     * switch in the app settings module routes).
     * Exp: home => ['title' => 'Home', 'divider_after' => FALSE]
     */
    public function getRouteLinksArray()
    {
        $output = [];
        foreach ($this->moduleRoutes as $route) {
            $output[$route['route_name']] = [
                'title'         => $route['title'],
                'divider_after' => (bool) $route['divider_after'],
            ];
        }
        return $output;
    }
}
