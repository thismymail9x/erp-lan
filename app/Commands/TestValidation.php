<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestValidation extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:validation';
    protected $description = 'Tests validation rules.';

    public function run(array $params)
    {
        $validation = \Config\Services::validation();
        try {
            $validation->setRules([
                'identity_number' => 'permit_empty|is_unique_not_deleted[customers.identity_number,id,{id}]'
            ]);
            $data = ['identity_number' => '123456789'];
            $res = $validation->run($data);
            CLI::write("Result: " . ($res ? 'true' : 'false'));
        } catch (\Exception $e) {
            CLI::write("Caught Exception: " . get_class($e) . ': ' . $e->getMessage());
        } catch (\Error $e) {
            CLI::write("Caught Error: " . get_class($e) . ': ' . $e->getMessage());
        }
    }
}
