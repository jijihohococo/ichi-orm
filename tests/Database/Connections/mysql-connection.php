<?php

require __DIR__ . '/../vendor/autoload.php';

use JiJiHoHoCoCo\IchiORM\Database\Connector;

$connector = new Connector();

// Load scenarios and pick one via CLI arg; default covers most options
$scenarios = require __DIR__ . '/config.php';
$scenario = isset($argv[1]) ? $argv[1] : 'port_modes_full';
if (!isset($scenarios[$scenario])) {
    echo "Unknown scenario: {$scenario}\n";
    echo "Available scenarios: " . implode(', ', array_keys($scenarios)) . "\n";
    exit(1);
}
$config = $scenarios[$scenario];
$driver = 'mysql';

try {
    $connector->createConnection($driver, $config);
    $pdo = $connector->executeConnect($driver);

    if ($pdo instanceof \PDO) {
        echo "MySQL connection successful ({$scenario})\n";
        exit(0);
    }

    echo "MySQL connection failed: invalid PDO instance ({$scenario})\n";
    exit(1);
} catch (Exception $e) {
    // createConnection / executeConnect will call showErrorPage on failure and exit,
    // but we catch any unexpected exceptions here to ensure non-zero exit.
    echo "MySQL connection failed ({$scenario}): " . $e->getMessage() . "\n";
    exit(1);
}
