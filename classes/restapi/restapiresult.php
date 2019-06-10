<?php
class RestAPIResult
{
    /**
     * The output function of the RESTAPI class.
     * Adds the header 'Content-Type: application/json';
     * Outputs JSON formatted result, containing:
     * 'info'  => an array of objects, requested from the API, limited by the Core variable itemsPerPage;
     * 'total' => the number of total results for the request;
     * 'page'  => the current page number of the pagination; uses the currentPage variable of the Rewrite class;
     * 'limit' => the limit of the objects in the 'info' key; uses the Core variable itemsPerPage;
     * @param mixed $result - the result of the API
     * @param int $total - the total number of results
     */
    public function showResult($result, int $total)
    {
        global $Core;

        header('Content-Type: application/json');

        exit(
            json_encode(
                array(
                    'info'  => $result,
                    'total' => intval($total),
                    'page'  => intval($Core->Rewrite->currentPage),
                    'limit' => intval($Core->itemsPerPage),
                )
            )
        );
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
    public function showError(string $message, array $data)
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
