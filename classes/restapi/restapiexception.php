<?php
class RestApiException extends ExceptionHandler
{
    /**
     * Handle an Error throwable
     * @param Error $error
     */
    public function Error(Error $error)
    {
        global $Core;

        header('HTTP/1.1 400 Bad Request', true, 400);
        
        if ($Core->clientIsDeveoper()) {     
            $message = $error->__toString();
            $message .= PHP_EOL;
            $message .= "Thrown in ".$error->getFile()." on line ".$error->getLine();
        } else {
            $message = $this->translateMessage($Core->generalErrorText);
        }

        $this->returnError($message, array());
    }
    
    /**
     * Handle a BaseException throwable
     * @param BaseException $exception
     */
    public function BaseException(BaseException $exception)
    {
        header('HTTP/1.1 400 Bad Request', true, 400);
        
        $data = $exception->getData();
        if (!empty($data)) {
            foreach ($data as $field => $message) {
                $data[$field] = $this->translateMessage($message);   
            }
        }
        
        $this->returnError($this->getExceptionMessage($exception), $data);
    }
    
    /**
     * Handle a Exception throwable
     * @param Exception $exception
     */
    public function Exception(Exception $exception)
    {
        header("Bad Request", true, 400);
        $this->returnError($this->getExceptionMessage($exception), array());
    }
    
    /**
     * This function handles output, when an error occurs.
     * Adds the header 'Content-Type: application/json';
     * Outputs JSON formatted result, containing:
     * error => the message of the error
     * data  => additional data to explain the message
     * @param string $message - the message of the error
     * @param array $data - additional data to explain the message
     */
    private function returnError(string $message, array $data)
    {
        header('Content-Type: application/json');
        exit(
            json_encode(
                array(
                    'error'  => $message,
                    'data'   => json_encode($data),
                )
            )
        );
    }
}