<?php

namespace App\Commands;

use App\Services\EmployeeStageService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SyncEmployeeStages extends BaseCommand
{
    protected $group = 'HR';
    protected $name = 'employees:sync-stages';
    protected $description = 'Reconcile employee stages from entry role and join date.';

    public function run(array $params)
    {
        (new EmployeeStageService())->synchronize();
        CLI::write('Employee stages synchronized.', 'green');
    }
}
