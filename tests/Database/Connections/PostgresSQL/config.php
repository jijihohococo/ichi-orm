<?php

// PostgreSQL scenario-based configuration

$base = [
    'driver'        => 'pgsql',
    'host'          => '127.0.0.1',
    'dbname'        => 'test',
    'user_name'     => 'postgres',
    'user_password' => 'P@ssw0rd12345!',
];

return [
    // Uses TCP port + charset + standard options
    'port_standard' => array_merge($base, [
        'port'          => 5432,
        'charset'       => 'utf8',
    ]),

    // Minimal connection: host + port only
    'port_minimal' => array_merge($base, [
        'port'          => 5432,
    ]),

    // Connection with UTF-8 charset
    'charset_utf8' => array_merge($base, [
        'port'          => 5432,
        'charset'       => 'utf8',
    ]),

    // Connection with time zone
    'timezone_utc' => array_merge($base, [
        'port'          => 5432,
        'time_zone'     => 'UTC',
    ]),

    // Connection with application name
    'app_name' => array_merge($base, [
        'port'          => 5432,
        'application_name' => 'IchiORM_Test',
    ]),

    // Connection with SSL
    'ssl_require' => array_merge($base, [
        'port'          => 5432,
        'sslmode'       => 'require',
    ]),
];
