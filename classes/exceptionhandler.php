<?php
class ExceptionHandler
{
    const MESSAGE_BREAKDOWN_DELIMITER = '%%';
    const DONT_TRANSLATE_DELIMITER = '##';

    /**
     * Parses the exception message and makes sure it can be translated
     * @param Exception $exception - the thrown exception
     * @return string
     */
    protected function getExceptionMessage($exception)
    {
        if(!is_string($exception)){
            $message = $exception->getMessage();
        } else {
            $message = $exception;
        }


        if (empty($message)) {
            return '';
        }

        return $this->translateMessage($message);
    }

    /**
     * Parses the exception message and returns the translated (or not) text
     * @param string $message - the thrown exception
     * @return string
     */
    protected function translateMessage(string $message)
    {
        global $Core;

        $scripts = $Core->globalFunctions->getBetweenAll($message, '<script', '</script>');

        foreach ($scripts as $script) {
            $message = str_replace('<script'.$script.'</script>', '', $message);
        }

        $noscripts = $Core->globalFunctions->getBetweenAll($message, '<noscript', '</noscript>');

        foreach ($noscripts as $noscript) {
            $message = str_replace('<noscript'.$noscript.'</noscript>', '', $message);
        }

        $styles = $Core->globalFunctions->getBetweenAll($message, '<style', '</style>');

        foreach ($styles as $style) {
            $message = str_replace('<style'.$style.'</style>', '', $message);
        }

        $message = explode(self::MESSAGE_BREAKDOWN_DELIMITER, $message);
        $translation = '';

        foreach ($message as $messagePart) {
            if (
                mb_substr($messagePart, 0, mb_strlen(self::DONT_TRANSLATE_DELIMITER)) !== self::DONT_TRANSLATE_DELIMITER &&
                mb_substr(
                    $messagePart,
                    -(mb_strlen(self::DONT_TRANSLATE_DELIMITER)),
                    mb_strlen(self::DONT_TRANSLATE_DELIMITER)
                ) !== self::DONT_TRANSLATE_DELIMITER
            ) {
                if ($Core->allowMultylanguage) {
                    $messagePart = str_replace(' ', '_', mb_strtolower($messagePart));
                    $messagePart = trim($messagePart, ' _');
                    $translation .= $Core->Language->{$messagePart}.' ';
                } else{
                    $translation .= $messagePart.' ';
                }

            } else {
                $translation .= mb_substr(
                    $messagePart,
                    mb_strlen(self::DONT_TRANSLATE_DELIMITER),
                    -(mb_strlen(self::DONT_TRANSLATE_DELIMITER))
                );
            }
        }

        $message = trim($translation);

        foreach ($scripts as $script) {
            $message .= '<script'.$script.'</script>';
        }

        foreach ($scripts as $script) {
            $message .= '<noscript'.$script.'</noscript>';
        }

        foreach ($scripts as $script) {
            $message .= '<style'.$script.'</style>';
        }

        return $message;
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
            $this->handleAjaxMessage($this->getExceptionMessage($success), true);
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

        header('HTTP/1.1 500 Internal Server Error', true, 500);

        if ($Core->clientIsDeveloper()) {
            $message = $error->__toString();
            $message .= PHP_EOL;
            $message .= "Thrown in ".$error->getFile()." on line ".$error->getLine();
        } else {
            $message = $this->translateMessage($Core->generalErrorText);
        }

        if ($Core->ajax) {
            $this->handleAjaxMessage($message);
        } else {
            echo '<pre style="font-family: unset;">';
            echo $message;
            echo '</pre>';
        }
    }

    /**
     * Transforms a BaseException into human readable error
     * @param BaseException $exception
     */
    protected function outputBaseException(BaseException $exception)
    {
        global $Core;

        $data = $exception->getData();

        if ($Core->ajax) {
            if (!empty($data)) {
                foreach ($data as $fieldName => $message) {
                    unset($data[$fieldName]);

                    $data[$this->translateMessage($fieldName)] = $this->translateMessage($message);
                }
            }

            $this->handleAjaxMessage($this->getExceptionMessage($exception), false, $data);
        } else {
            echo $this->getExceptionMessage($exception);

            if (!empty($data)) {
                echo ':';

                foreach ($data as $fieldName => $message) {
                    echo '<br>';

                    if (!is_numeric($fieldName)) {
                        $this->translateMessage($fieldName).' - ';
                    }

                    echo $this->translateMessage($message);
                }
            }
        }
        unset ($data);
    }

    /**
     * Handle a ForbiddenException throwable
     * @param ForbiddenException $exception
     */
    public function ForbiddenException(ForbiddenException $exception)
    {
        header('HTTP/1.1 401 Unauthorized', true, 401);

        $this->outputBaseException($exception);
    }

    /**
     * Handle a BaseException throwable
     * @param BaseException $exception
     */
    public function BaseException(BaseException $exception)
    {
        header('HTTP/1.1 400 Bad Request', true, 400);

        $this->outputBaseException($exception);
    }

    /**
     * Handle a Exception throwable
     * @param Exception $exception
     */
    public function Exception(Exception $exception)
    {
        global $Core;

        header('HTTP/1.1 400 Bad Request', true, 400);

        $message = $this->getExceptionMessage($exception->getMessage());

        if ($Core->ajax) {
            $this->handleAjaxMessage($message);
        } else {
            echo '<pre style="font-family: unset;">';
            echo $message;
            echo '</pre>';
        }
    }

    /**
     * Shows the throwable message in the site javascript handler
     * Override if necessary
     * @param string $message - the message text
     * @param bool $isSuccess - set true, if it is a Success message
     */
    public function handleAjaxMessage(string $message, bool $isSuccess = null, array $data = null)
    {
        echo $message;

        if ($data) {
            if (!empty($data)) {
                echo ':';

                foreach ($data as $fieldName => $message) {
                    echo '<br>';

                    if (!is_numeric($fieldName)) {
                        echo $fieldName.' - ';
                    }

                    echo $message;
                }
            }
        }
    }
}
