<?php

namespace App\Http\Controllers;

class FinanceAccountingController extends Controller
{
    //**Define the module name */
    protected $module = 'finance-accounting';

    public function moduleConfig()
    {
        $this->setMainTitle('Finance & Accounting')
            ->setTabLinks();
    }

    /**
     * Get the payroll module.
     */
    public function getPayrollModule()
    {
        return $this->setMainTitle('Finance & Accounting - Payroll')
            ->module('payroll');
    }
}
