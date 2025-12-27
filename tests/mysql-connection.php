<?php

require __DIR__ . '/../vendor/autoload.php';

use JiJiHoHoCoCo\IchiORM\Database\Connector;

$connector = new Connector();

$config = [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname' => 'test',
    'user_name' => 'root',
    'user_password' => 'root',
    'charset'  => 'utf8mb4'
];

try {
    $connector->createConnection('mysql', $config);
    $pdo = $connector->executeConnect('mysql');

    if ($pdo instanceof \PDO) {
        echo "MySQL connection successful\n";
        exit(0);
    }

    echo "MySQL connection failed: invalid PDO instance\n";
    exit(1);
} catch (Exception $e) {
    // createConnection / executeConnect will call showErrorPage on failure and exit,
    // but we catch any unexpected exceptions here to ensure non-zero exit.
    echo "MySQL connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
