<?php

namespace App\Controllers;

use Config\Services;
use Exception;

class TempMigrator extends BaseController
{
    public function index()
    {
        $migrations = Services::migrations();

        try {
            if ($migrations->latest()) {
                echo "<h1>Migration Success!</h1>";
                echo "<p>Database has been updated to the latest version.</p>";
            } else {
                echo "<h1>No pending migrations found.</h1>";
            }
        } catch (Exception $e) {
            echo "<h1>Migration Failed!</h1>";
            echo "<pre>" . $e->getMessage() . "</pre>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }
}
