<?php

// Scenario-based configuration to exercise MySQLConnection branches

$base = [
    'driver'        => 'mysql',
    'host'          => '127.0.0.1',
    'dbname'        => 'test',
    'user_name'     => 'root',
    'user_password' => 'root',
];

return [
    // Uses TCP port + modes + charset + collation + time_zone + isolation_level
    'port_modes_full' => array_merge($base, [
        'port'          => 3306,
        'charset'       => 'utf8mb4',
        'collation'     => 'utf8mb4_unicode_ci',
        'time_zone'     => '+00:00',
        'isolation_level'=> 'READ COMMITTED',
        'modes'         => [
            'STRICT_TRANS_TABLES',
            'NO_ZERO_DATE',
            'ERROR_FOR_DIVISION_BY_ZERO',
        ],
    ]),

    // Uses TCP port + strict mode instead of modes, with other options
    'port_strict_full' => array_merge($base, [
        'port'          => 3306,
        'charset'       => 'utf8mb4',
        'collation'     => 'utf8mb4_unicode_ci',
        'time_zone'     => '+00:00',
        'isolation_level'=> 'REPEATABLE READ',
        'strict'        => true,
    ]),

    // Uses Unix socket + modes branch; suitable on Linux/macOS (not Windows)
    'socket_modes_full' => array_merge($base, [
        'unix_socket'   => '/var/run/mysqld/mysqld.sock',
        'charset'       => 'utf8mb4',
        'collation'     => 'utf8mb4_unicode_ci',
        'time_zone'     => '+00:00',
        'isolation_level'=> 'SERIALIZABLE',
        'modes'         => ['STRICT_TRANS_TABLES'],
    ]),

    // Minimal DSN: host + dbname only (no port/socket, no extras)
    'host_only' => array_merge($base, []),

    // DSN via TCP port with only time_zone (exercises time_zone branch without charset/collation)
    'port_timezone_only' => array_merge($base, [
        'port'          => 3306,
        'time_zone'     => '+00:00',
    ]),
];
