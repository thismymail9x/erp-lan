<?php
$host = 'localhost';
$user = 'luatanborqy7_erp';
$pass = '4EkhR7pvQUSJpxbxaLZV';
$db   = 'luatanborqy7_erp';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sqls = [
        "ALTER TABLE employees ADD COLUMN insurance_salary DECIMAL(15,2) DEFAULT 0 AFTER salary_base",
        "ALTER TABLE employees ADD COLUMN diligence_allowance DECIMAL(15,2) DEFAULT 0 AFTER allowance_base",
        "ALTER TABLE employees ADD COLUMN petrol_allowance DECIMAL(15,2) DEFAULT 0 AFTER diligence_allowance",
        "ALTER TABLE employees ADD COLUMN dependent_count INT DEFAULT 0 AFTER petrol_allowance",
        
        "ALTER TABLE payrolls ADD COLUMN insurance_salary DECIMAL(15,2) DEFAULT 0 AFTER salary_base",
        "ALTER TABLE payrolls ADD COLUMN salary_per_day DECIMAL(15,2) DEFAULT 0 AFTER total_standard_days",
        "ALTER TABLE payrolls ADD COLUMN taxable_income DECIMAL(15,2) DEFAULT 0 AFTER actual_working_days",
        "ALTER TABLE payrolls ADD COLUMN diligence_allowance DECIMAL(15,2) DEFAULT 0 AFTER salary_allowance",
        "ALTER TABLE payrolls ADD COLUMN petrol_allowance DECIMAL(15,2) DEFAULT 0 AFTER diligence_allowance",
        "ALTER TABLE payrolls ADD COLUMN si_employer DECIMAL(15,2) DEFAULT 0 AFTER salary_bonus",
        "ALTER TABLE payrolls ADD COLUMN si_employee DECIMAL(15,2) DEFAULT 0 AFTER si_employer",
        "ALTER TABLE payrolls ADD COLUMN dependent_deduction DECIMAL(15,2) DEFAULT 0 AFTER si_employee",
        "ALTER TABLE payrolls ADD COLUMN pit_tax DECIMAL(15,2) DEFAULT 0 AFTER dependent_deduction",
        "ALTER TABLE payrolls ADD COLUMN total_deductions DECIMAL(15,2) DEFAULT 0 AFTER pit_tax",

        // Update migration table manually since spark failed
        "INSERT INTO migrations (version, class, group, namespace, time, batch) VALUES (1715243191, 'App\\Database\\Migrations\\UpdatePayrollColumnsForImageLayout', 'default', 'App', UNIX_TIMESTAMP(), (SELECT MAX(batch)+1 FROM (SELECT batch FROM migrations) AS tmp))"
    ];

    foreach ($sqls as $sql) {
        try {
            $pdo->exec($sql);
            echo "Success: $sql\n";
        } catch (Exception $e) {
            echo "Error: $sql - " . $e->getMessage() . "\n";
        }
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
