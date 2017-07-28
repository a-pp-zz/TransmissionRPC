<?php
namespace AppZz\Http\TransmissionRPC;

/**
 * Class Exception
 * @package AppZz\Http\TransmissionRPC
 */
class Exception extends \Exception {
    /**
     * Exception: Invalid arguments
     */
    const E_INVALIDARGS = -1;

    /**
     * Exception: Invalid Session-Id
     */
    const E_SESSIONID = -2;

    /**
     * Exception: Error while connecting
     */
    const E_CONNECTION = -3;

    /**
     * Exception: Error 401 returned, unauthorized
     */
    const E_AUTHENTICATION = -4;

	/**
	 * Exception constructor.
	 * @param null $message
	 * @param int $code
	 * @param \Exception|NULL $previous
	 */
    public function __construct ($message = NULL, $code = 0, \Exception $previous = NULL )
    {
        parent::__construct($message, $code, $previous);
    }
}