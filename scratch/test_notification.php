<?php
// Test script for finding users
define('ENVIRONMENT', 'development');
require __DIR__ . '/../public/index.php'; // wait, no, run from cli

// Since we cannot run web index from CLI easily, let's create a temp CLI controller or route.
