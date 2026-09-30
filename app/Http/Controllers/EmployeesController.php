<?php

namespace App\Http\Controllers;

class EmployeesController extends Controller
{
    //**Define the module name */
    protected $module = 'employees';

    public function moduleConfig()
    {
        $this->setMainTitle('Radnici: hidro-projekt')
            ->setTabLinks()->specialIndexIcon('table');
    }

    /**
     * Get the main employees module. 
     * This will display a table of all the (active) employees
     */
    public function index()
    {
        return $this->module();
    }

    /**
     * Get the module for the individual workers info.
     * Display all the information of a worker, and enabled editing.
     */
    public function getWorkerInfo()
    {
        return $this->module('worker-info');
    }

    /**
     * Module for the available workplaces.
     * CRUD for the company workplaces.
     */
    public function getWorkplacesInfo()
    {
        return $this->module('workplaces-info');
    }

    /**
     * Module for the subcontractors.
     * List of the subcontractor companies, with the info (workers, ...) of the selected one.
     */
    public function getSubcontractorModule()
    {
        $this->setMainTitle('Radnici: kooperanti');
        return $this->module('subcontractor');
    }
}
