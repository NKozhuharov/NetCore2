<?php
class UnauthorizedException extends BaseException
{
    /**
     * Create a new instance of the UnauthorizedException class
     * It will change the response header to 401
     * @param string $message - the exception message
     * @param array $data - additional data for the exception
     * @param string $className - the class name which threw the exception
     */
    public function __construct(string $message = null, array $data = null, string $className = null)
    {
        if ($message === null) {
            $message = 'Unauthorized';
        }

        parent::__construct($message, $data, $className);
        header('HTTP/1.1 401 Unauthorized', true, 401);
    }
}
