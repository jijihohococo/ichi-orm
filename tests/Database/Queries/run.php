<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/TestRunner.php';
require_once __DIR__ . '/Fixtures/TestDatabase.php';
require_once __DIR__ . '/Fixtures/Models.php';
require_once __DIR__ . '/DriverTestCase.php';

$files = [
    __DIR__ . '/Tests/CoreQueryTest.php',
    __DIR__ . '/Tests/WhereTest.php',
    __DIR__ . '/Tests/OrWhereTest.php',
    __DIR__ . '/Tests/WhereInTest.php',
    __DIR__ . '/Tests/WhereColumnTest.php',
    __DIR__ . '/Tests/JoinTest.php',
    __DIR__ . '/Tests/GroupingTest.php',
    __DIR__ . '/Tests/OrderingTest.php',
    __DIR__ . '/Tests/UnionTest.php',
    __DIR__ . '/Tests/SubqueryTest.php',
    __DIR__ . '/Tests/CrudTest.php',
    __DIR__ . '/Tests/PaginationTest.php',
    __DIR__ . '/Tests/RelationshipTest.php',
    __DIR__ . '/Tests/IdentifierTest.php',
    __DIR__ . '/Tests/SecurityRegressionTest.php',
    __DIR__ . '/Tests/ObserverTest.php',
    __DIR__ . '/Tests/CompatibilityTest.php',
];

foreach ($files as $file) {
    require_once $file;
}

$classes = [
    CoreQueryTest::class,
    WhereTest::class,
    OrWhereTest::class,
    WhereInTest::class,
    WhereColumnTest::class,
    JoinTest::class,
    GroupingTest::class,
    OrderingTest::class,
    UnionTest::class,
    SubqueryTest::class,
    CrudTest::class,
    PaginationTest::class,
    RelationshipTest::class,
    IdentifierTest::class,
    SecurityRegressionTest::class,
    ObserverTest::class,
    CompatibilityTest::class,
];

$drivers = [];

if (isset($argv[1])) {
    $drivers[] = $argv[1];
} else {
    $drivers = ['mysql', 'pgsql', 'sqlsrv'];
}

$exitCode = 0;

foreach ($drivers as $driver) {
    try {
        TestDatabase::boot($driver);
    } catch (Throwable $e) {
        echo "\n============================================================\n";
        echo "Driver: {$driver}\n";
        echo "DATABASE BOOT FAILED\n";
        echo get_class($e) . ': ' . $e->getMessage() . "\n";
        echo "============================================================\n";
        $exitCode = 1;
        continue;
    }

    $runner = new TestRunner();
    $result = $runner->run($classes, $driver);

    if ($result !== 0) {
        $exitCode = 1;
    }
}

exit($exitCode);
