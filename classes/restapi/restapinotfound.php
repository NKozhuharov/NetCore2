<?php
class RESTAPINotFound extends RESTAPI
{
    /**
     * Override the function to show a not found header and return empty result
     */
    public function getAll(int $limit = null, string $additional = null, string $orderBy = null)
    {
        $this->showHeaderNotFound();
    }
    
    /**
     * Override the function to show a not found header and return empty result
     */
    public function getById(int $rowId)
    {
        $this->showHeaderNotFound();
    }
    
    /**
     * Override the function to show a not found header and return empty result
     */
    public function insert(array $input, int $flag = null)
    {
        $this->showHeaderNotFound();
    }
    
    /**
     * Override the function to show a not found header and return empty result
     */
    public function updateById(int $objectId, array $input, string $additional = null)
    {
        $this->showHeaderNotFound();
    }
    
    /**
     * Override the function to show a not found header and return empty result
     */
    public function delete(string $additional)
    {
        $this->showHeaderNotFound();
    }
    
    /**
     * Override the function to show a not found header and return empty result
     */
    public function deleteById(int $rowId)
    {
        $this->showHeaderNotFound();
    }
}
