<?php
class RESTAPINotFound extends RESTAPI
{
    public function getAll(int $limit = null, string $additional = null, string $orderBy = null)
    {
        header('HTTP/1.0 404 Not Found', true, 404);
    }
    
    public function getById(int $rowId)
    {
        header('HTTP/1.0 404 Not Found', true, 404);
    }
    
    public function insert(array $input, int $flag = null)
    {
        header('HTTP/1.0 404 Not Found', true, 404);
    }
    
    public function updateById(int $objectId, array $input, string $additional = null)
    {
        header('HTTP/1.0 404 Not Found', true, 404);
    }
    
    public function delete(string $additional)
    {
        header('HTTP/1.0 404 Not Found', true, 404);
    }
    
    public function deleteById(int $rowId)
    {
        header('HTTP/1.0 404 Not Found', true, 404);
    }
}
