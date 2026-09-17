<?php

require __DIR__ . '/../../../../vendor/autoload.php';

use JiJiHoHoCoCo\IchiORM\Database\Connector;

function run_sqlite_scenario(string $scenario): void
{
    $scenarios = require __DIR__ . '/config.php';

    if (!isset($scenarios[$scenario])) {
        echo "Unknown scenario: {$scenario}\n";
        echo "Available scenarios: " .
            implode(', ', array_keys($scenarios)) .
            "\n";
        exit(1);
    }

    $config = $scenarios[$scenario];
    $connector = new Connector();
    $driver = 'sqlite';

    try {
        $connector->createConnection($driver, $config);
        $pdo = $connector->executeConnect($driver);

        if ($pdo instanceof \PDO) {
            echo "SQLite connection successful ({$scenario})\n";
            exit(0);
        }

        echo "SQLite connection failed: invalid PDO instance ({$scenario})\n";
        exit(1);
    } catch (Exception $e) {
        echo "SQLite connection failed ({$scenario}): " .
            $e->getMessage() .
            "\n";
        exit(1);
    }
}