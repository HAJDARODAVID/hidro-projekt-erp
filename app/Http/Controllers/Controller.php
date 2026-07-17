<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Services\Application\GetModuleRoutesForTabLinks;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**Define the module name */
    protected $module = NULL;

    /**Sets the module title */
    protected $mainTitle = NULL;

    /**Sets tabs links array */
    protected $tabLinks = [];

    /**Store the request */
    protected $request;

    /**Sets the index tab icon  */
    protected $specialIndexIcon = NULL;

    /**All the additional middleware attributes for a controller */
    protected $middlewareAttributes = [];

    /**Define the module actions */
    protected $moduleAction = [];

    /**Define the module actions */
    protected $additionalAction = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
        if (method_exists(get_class($this), 'moduleConfig')) $this->moduleConfig();

        /**
         * Set the middleware for a controller .
         * The auth middleware is set for all controllers  
         */
        $this->middleware(array_merge(['auth'], $this->middlewareAttributes));
    }

    /**
     * Method for returning a view where the livewire lives.
     * 
     * @param string $component Set the livewire component name, by default index
     * @return view Livewire module 
     */
    protected function module($component = 'index')
    {
        /**Check if Livewire component for a module exists */
        $livewire = class_exists("App\\Livewire\\Modules\\" . $this->stringConverter($this->module) . '\\' . $this->stringConverter($component));

        return view('module-container', [
            'livewire'         => $livewire,
            'module'           => $this->module ?? NULL,
            'component'        => $component,
            'mainTitle'        => $this->mainTitle,
            'tabLinks'         => $this->tabLinks,
            'specialIndexIcon' => $this->specialIndexIcon,
            'moduleAction'     => $this->moduleAction,
            'additionalAction' => $this->additionalAction,
        ]);
    }

    /**
     * Sets the main module title. The title will be displayed no matter of the Livewire component.
     * 
     * @param string $mainTitle Name of the overall main title.
     * @return Controller 
     */
    protected function setMainTitle(string $mainTitle)
    {
        $this->mainTitle = $mainTitle;
        return $this;
    }

    /**
     * Sets the main module tab links component data.
     * The array has to be a a key value pair where the key is the route name and the value the displayed name.
     * Exp: home => Home (Good example right?)
     * Use it in moduleConfig() to add to all methods, but you can overwrite the links in the method itself.
     * If the $tabLinks array is empty it will check if there are registered routes for this module. 
     * 
     * @param array $tabLinks Pass here custom routes.
     * @return Controller 
     */
    protected function setTabLinks(array $tabLinks = [])
    {
        $controller = $this->getRequestControllerName();
        if ($controller === null || $controller == 'Livewire') return $this;
        if (empty($tabLinks)) $tabLinks = GetModuleRoutesForTabLinks::byController($controller)->getRouteLinksArray();

        $this->tabLinks = $tabLinks;
        return $this;
    }

    /**
     * When setting the tabs with a index page if you want a special icon set it here.
     * 
     * @param string $icon Pass here the Bootstrap icon 
     * @return Controller
     */
    protected function specialIndexIcon($icon)
    {
        $this->specialIndexIcon = $icon;
        return $this;
    }

    /**
     * StringConverter is a helping method for converting string from first-test to FirstTest
     * 
     * @param string $string String to be converted.
     * @return string The converted string Exp: FirstTest
     */
    private function stringConverter(string|NULL $string)
    {
        $output = '';
        $explosion = explode('-', $string);
        foreach ($explosion as $value) {
            $output .= ucfirst($value);
        }
        return $output;
    }

    /**
     * Get the controller name from the request.
     */
    private function getRequestControllerName()
    {
        $route = $this->request->route();
        if (! $route) return null;

        $action = $route->getActionName();
        $explodedAction = explode("@", $action);
        /**Check if Livewire */
        $livewireCheck = explode('\\', $explodedAction[0]);
        if ($livewireCheck[0] == 'Livewire') return 'Livewire';
        return class_basename($explodedAction[0]);
    }

    /**
     * Set all the middleware that need to be used in this controller.
     * 
     * @param array $middleware All aliases for the controller
     * @return Controller
     */
    protected function middlewareAttributes(...$middleware)
    {
        $this->middlewareAttributes = $middleware;
        return $this;
    }

    /**
     * Set module actions.
     * Use in constructor for all the method to have access, od redefine it in the method.
     * Note: use the full path for the component.
     * 
     * @param array $actions
     * @return Controller
     */
    public function setModuleActions(...$actions)
    {
        $this->moduleAction = $actions;
        return $this;
    }

    /**
     * Set additional module actions.
     * Meant to use in a method to define additional action.
     * Note: use the full path for the component.
     * 
     * @param array $actions
     * @return Controller
     */
    public function setAdditionalActions(...$actions)
    {
        $this->additionalAction = $actions;
        return $this;
    }
}
