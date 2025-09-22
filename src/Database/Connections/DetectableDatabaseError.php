<?php

namespace JiJiHoHoCoCo\IchiORM\Database\Connections;

trait DetectableDatabaseError
{
    public function checkDetectableErrors($message)
    {
        return strpos($message, 'server has gone away') !== false ||
            strpos($message, 'no connection to the server') !== false ||
            strpos($message, 'Lost connection') !== false ||
            strpos($message, 'is dead or not enabled') !== false ||
            strpos($message, 'Error while sending') !== false ||
            strpos($message, 'decryption failed or bad record mac') !== false ||
            strpos($message, 'server closed the connection unexpectedly') !== false ||
            strpos($message, 'SSL connection has been closed unexpectedly') !== false ||
            strpos($message, 'Error writing data to the connection') !== false ||
            strpos($message, 'Resource deadlock avoided') !== false ||
            strpos($message, 'Transaction() on null') !== false ||
            strpos($message, 'child connection forced to terminate due to client_idle_limit') !== false ||
            strpos($message, 'query_wait_timeout') !== false ||
            strpos($message, 'reset by peer') !== false ||
            strpos($message, 'Physical connection is not usable') !== false ||
            strpos($message, 'TCP Provider: Error code 0x68') !== false ||
            strpos($message, 'ORA-03114') !== false ||
            strpos($message, 'Packets out of order. Expected') !== false ||
            strpos($message, 'Adaptive Server connection failed') !== false ||
            strpos($message, 'Communication link failure') !== false ||
            strpos($message, 'connection is no longer usable') !== false ||
            strpos($message, 'Login timeout expired') !== false ||
            strpos($message, 'Connection refused') !== false ||
            strpos($message, 'running with the --read-only option so it cannot execute this statement') !== false ||
            strpos($message, 'The connection is broken and recovery is not possible. The connection is marked by the client driver as unrecoverable. No attempt was made to restore the connection.') !== false ||
            strpos($message, 'SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: Try again') !== false ||
            strpos($message, 'SQLSTATE[HY000]: General error: 7 SSL SYSCALL error: EOF detected') !== false;
    }
}
