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

        if ($Core->clientIsDeveloper()) {
            $message = $error->__toString();
            $message .= PHP_EOL;
            $message .= "Thrown in ".$error->getFile()." on line ".$error->getLine();
        } else {
            $message = $this->translateMessage($Core->generalErrorText);
        }

        $Core->RestAPIResult->showError($message, array());
    }

    /**
     * Transforms a BaseException into human readable error
     * @param BaseException $exception
     */
    protected function outputBaseException(BaseException $exception)
    {
        global $Core;

        $data = $exception->getData();
        if (!empty($data)) {
            foreach ($data as $field => $message) {
                $data[$field] = $this->translateMessage($message);
            }
        }

        $Core->RestAPIResult->showError($this->getExceptionMessage($exception), $data);
    }

    /**
     * Handle a Exception throwable
     * @param Exception $exception
     */
    public function Exception(Exception $exception)
    {
        global $Core;

        header("HTTP/1.1 400 Bad Request", true, 400);
        $Core->RestAPIResult->showError($this->getExceptionMessage($exception), array());
    }
}