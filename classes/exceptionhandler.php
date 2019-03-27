<?php
class ExceptionHandler
{
    /**
     * Handle a Success throwable
     * @param Success $success
     */
    public function Success(Success $success)
    {
        global $Core;

        header('HTTP/1.1 200 OK', true, 200);

        if ($Core->ajax) {
            echo $this->handleAjaxMessage(str_replace(' ', '_', mb_strtolower($success->getMessage())), true);
        } else {
            echo str_replace(' ', '_', mb_strtolower($success->getMessage()));
        }
    }
    
    /**
     * Handle an Error throwable
     * @param Error $error
     */
    public function Error(Error $error)
    {
        global $Core;

        header('HTTP/1.1 400 Bad Request', true, 400);
        
        $message = $error->__toString();
        $message .= PHP_EOL;
        $message .= "Thrown in ".$error->getFile()." on line ".$error->getLine();                
                        
        if ($Core->ajax) {
            echo $this->handleAjaxMessage($message);
        } else {
            echo '<pre>';            
            echo $message;            
            echo '</pre>';            
        }
    }
    
    /**
     * Handle a BaseException throwable
     * @param BaseException $exception
     */
    public function BaseException(BaseException $exception)
    {
        global $Core;

        header('HTTP/1.1 400 Bad Request', true, 400);

        if ($Core->ajax) {
            echo $this->handleAjaxMessage(str_replace(' ', '_', mb_strtolower($exception->getMessage())));
        } else {
            echo str_replace(' ', '_', mb_strtolower($exception->getMessage()));
        }
        
        if (!empty($exception->getData())) {
            echo ':';
            foreach ($exception->getData() as $k => $v) {
                echo '<br>'.$Core->language->{$k}.' - '.$Core->language->{str_replace(' ', '_', mb_strtolower($v))};
            }
        }
    }
    
    /**
     * Handle a Exception throwable
     * @param Exception $exception
     */
    public function Exception(Exception $exception)
    {
        global $Core;

        header('HTTP/1.1 500 Internal Server Error', true, 500);

        if ($Core->ajax) {
            echo $this->handleAjaxMessage($exception->getMessage());
        } else {
            echo $exception->getMessage();
        }
    }
    
    /**
     * Shows the throwable message in the site javascript handler
     * Override if necessary
     * @param string $message - the message text
     * @param bool $isSuccess - set true, if it is a Success message
     */
    public function handleAjaxMessage(string $message, bool $isSuccess = null)
    {
        echo '<script>alert("'.$message.'")</script>';
    }
}
