<?php
trait BaseOrderBy
{
    /**
     * @var string
     * The order by clause of the query
     */
    private $orderBy;

    /**
     * Sets the ORDER BY clause of the query
     * Thorws Error if the order does not contain ASC or DESC (case sensitive)
     * @param string $orderBy - the body of the order by clause
     * @throws Error
     */
    public function setOrderBy(string $orderBy)
    {
        if (
            $orderBy !== "" && 
            !strstr($orderBy, self::ORDER_ASC) && 
            !strstr($orderBy, self::ORDER_DESC) &&
            !strstr($orderBy, self::ORDER_FIELD)
        ) {
            throw new Error("Invalid order type provided, it must contain ASC, DESC or FIELD");
        }
            
        $this->orderBy = $orderBy;
    }
    
    /**
     * Adds an ORDER BY clause to the query
     * It can be called multiple times, to add additional orders
     * Thorws Error if the field or the order type are empty
     * Thorws Error if the order type is not ASC or DESC (case sensitive)
     * @param string $field - the field to order by
     * @param string $orderType - the order type (ASC or DESC)
     * @throws Error
     */
    public function addOrderBy(string $field, string $orderType) 
    {
        if (empty($field)) {
            throw new Error("Select a field to order by");
        }
        
        if (empty($orderType)) {
            throw new Error("Select order type");
        }
        
        if ($orderType !== self::ORDER_ASC && $orderType !== self::ORDER_DESC) {
            throw new Error("Invalid order type provided, it must be ASC or DESC");
        }
        
        if (!empty($this->orderBy)) {
            $this->orderBy .= ", {$field} {$orderType}";
        } else {
            $this->orderBy = "{$field} {$orderType}";
        }
    }

    /**
     * Adds the ORDER BY clause of the query
     */
    private function addOrderByToQuery()
    {
        if (!empty($this->orderBy)) {
            $this->query .= " ORDER BY ".$this->orderBy;
        }
    }
}
