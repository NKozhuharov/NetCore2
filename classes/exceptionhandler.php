<?php
class ExceptionHandler
{
    /**
     * Parses the exception message and makes sure it can be translated
     * @param Exception $exception - the thrown exception
     * @return string
     */
    private function getExceptionMessage($exception)
    {
        $message = $exception->getMessage();

        if (empty($message)) {
            return '';
        } else if (stristr($message, '<script') && strstr($message, '</script>')) {
            return $message;
        }

        return $this->translateMessage($message);
    }

    /**
     * Parses the exception message and returns the translated (or not) text
     * @param string $message - the thrown exception
     * @return string
     */
    private function translateMessage(string $message)
    {
        global $Core;

        if (substr_count($message, '%%') > 1) {

            $message = explode('%%', $message);
            $translation = '';
            foreach ($message as $messagePart) {
                $messagePart = str_replace(' ', '_', mb_strtolower($messagePart));
                $messagePart = trim($messagePart, ' _');
                $translation .= $Core->Language->{$messagePart}.' ';
            }
            return trim($translation);
        }

        return $Core->Language->{str_replace(' ', '_', mb_strtolower($message))};
    }

    /**
     * Handle a Success throwable
     * @param Success $success
     */
    public function Success(Success $success)
    {
        global $Core;

        header('HTTP/1.1 200 OK', true, 200);

        if ($Core->ajax) {
            echo $this->handleAjaxMessage($this->getExceptionMessage($success), true);
        } else {
            echo $this->getExceptionMessage($success);
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
            echo '<pre style="font-family: unset;">';
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
            echo $this->handleAjaxMessage($this->getExceptionMessage($exception));
        } else {
            echo $this->getExceptionMessage($exception);
        }

        if (!empty($exception->getData())) {
            echo ':';
            foreach ($exception->getData() as $fieldName => $message) {
                echo '<br>'.$this->translateMessage($fieldName).' - '.$this->translateMessage($message);
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
        ?>
        <div><div id="alert"><?php echo $message; ?></div></div>
        <?php
    }
}
