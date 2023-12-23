<?php

namespace JiJiHoHoCoCo\IchiORM\Database\Connections;

trait DetectableDatabaseError{

	public function checkDetectableErrors($message){
		return strpos($message, 'server has gone away') !== FALSE ||
		strpos($message,'no connection to the server') !== FALSE ||
		strpos($message,'Lost connection') !== FALSE ||
		strpos($message,'is dead or not enabled') !== FALSE ||
		strpos($message,'Error while sending') !== FALSE ||
		strpos($message,'decryption failed or bad record mac') !== FALSE ||
		strpos($message,'server closed the connection unexpectedly') !== FALSE ||
		strpos($message,'SSL connection has been closed unexpectedly') !== FALSE ||
		strpos($message,'Error writing data to the connection') !== FALSE ||
		strpos($message,'Resource deadlock avoided') !== FALSE ||
		strpos($message,'Transaction() on null') !== FALSE ||
		strpos($message,'child connection forced to terminate due to client_idle_limit') !== FALSE ||
		strpos($message,'query_wait_timeout') !== FALSE ||
		strpos($message,'reset by peer') !== FALSE ||
		strpos($message,'Physical connection is not usable') !== FALSE ||
		strpos($message,'TCP Provider: Error code 0x68') !== FALSE ||
		strpos($message,'ORA-03114') !== FALSE ||
		strpos($message,'Packets out of order. Expected') !== FALSE ||
		strpos($message,'Adaptive Server connection failed') !== FALSE ||
		strpos($message,'Communication link failure') !== FALSE ||
		strpos($message,'connection is no longer usable') !== FALSE ||
		strpos($message,'Login timeout expired') !== FALSE ||
		strpos($message,'Connection refused') !== FALSE ||
		strpos($message,'running with the --read-only option so it cannot execute this statement') !== FALSE ||
		strpos($message,'The connection is broken and recovery is not possible. The connection is marked by the client driver as unrecoverable. No attempt was made to restore the connection.') !== FALSE ||
		strpos($message,'SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: Try again') !== FALSE ||
		strpos($message,'SQLSTATE[HY000]: General error: 7 SSL SYSCALL error: EOF detected') !== FALSE;
	}
}