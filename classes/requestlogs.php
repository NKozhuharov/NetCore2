<?php
class RequestLogs extends Base
{   
    /**
     * Creates a new instance of the RequestLogs Base model
     */
    public function __construct()
    {
        $this->tableName = 'request_logs';
        $this->parentField = 'user_id';
        $this->orderType = self::ORDER_DESC;
        
        $this->logRequest();
    }
    
    /**
     * Logs the current request in the request_logs table
     * Works if $Core->logRequests is set to true, a user is logged in, the request is not empty
     * and the request is not from the system messages
     */
    private function logRequest()
    {
        global $Core;
        
        if (
            $Core->{$Core->userModel}->logged_in &&
            isset($_REQUEST) && 
            !empty($_REQUEST) && 
            isset($_SERVER['REQUEST_URI']) && 
            (empty($Core->messagesModel) || $Core->Rewrite->url != $Core->{$Core->messagesModel}->link)
        ) {
            $this->insert(
                array(
                    'user_id' => $Core->{$Core->userModel}->id, 
                    'request' => urldecode(http_build_query(array('page_url' => $_SERVER['REQUEST_URI']) + $_REQUEST))
                )
            );
        }
    }
    
    /**
     * Draws the logs. Use in the admin panel
     * @param array $logs
     */
    public function drawLogs(array $logs)
    {
        global $Core;
        echo '<pre>';
        foreach ($logs as $k => $v) {
            if (is_array($v)) {
                echo $Core->language->{mb_strtolower(str_replace(' ', '_', $k))}.":".PHP_EOL;
                $this->drawLogs($v);
            } else {
                echo $Core->language->{mb_strtolower(str_replace(' ', '_', $k))}.": $v".PHP_EOL;
                if ($k === 'page_url') {
                    echo '<div style="border-bottom: 1px solid #666;"></div><br>';
                }
            }
        }
        echo '</pre>';
    }
}
