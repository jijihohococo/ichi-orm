<?php

// SQL Server scenario-based configuration

$base = [
    'driver'        => 'sqlsrv',
    'host'          => '127.0.0.1',
    'dbname'        => 'test',
    'user_name'     => 'sa',
    'user_password' => 'P@ssw0rd12345!',
];

return [
    // Uses TCP port + charset + standard options
    'port_standard' => array_merge($base, [
        'port'          => 1433,
        'charset'       => 'utf8',
    ]),

    // Minimal connection: host + port only
    'port_minimal' => array_merge($base, [
        'port'          => 1433,
    ]),

    // Named pipes connection (Windows-specific, for local testing)
    'named_pipes' => array_merge($base, [
        'named_pipe'    => true,
    ]),

    // Connection with multiple active result sets enabled
    'mars_enabled' => array_merge($base, [
        'port'          => 1433,
        'mars'          => true,
    ]),

    // Connection with TrustServerCertificate (common in testing)
    'trust_certificate' => array_merge($base, [
        'port'          => 1433,
        'trust_server_certificate' => true,
    ]),
    // Binary encoding
    'charset_binary' => array_merge($base, [
        'port'          => 1433,
        'charset'       => 'binary',
        'trust_server_certificate' => true,
    ]),
    // System encoding
    'charset_system' => array_merge($base, [
        'port'          => 1433,
        'charset'       => 'system',
    ]),
];
