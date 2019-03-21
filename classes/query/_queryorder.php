<?php
    trait QueryOrder
    {
        private function validateOrderType(string $orderType)
        {
            if ($orderType !== self::ORDER_TYPE_ASC && $orderType !== self::ORDER_TYPE_DESC) {
                throw new exception ("Only ASC and DESC are allowed for order type!");
            }
        }
        
        public function addOrderBy()
        {
            $this->queryBody[] = "ORDER BY";
        }
        
        public function addOrderByField(string $field, string $orderType)
        {
            $this->validateOrderType($orderType);
            
            $this->queryBody[] = "`{$field}` {$orderType}";
        }
        
        public function addOrderByFields(array $data)
        {
            $orderByExpression = '';
            foreach ($data as $field => $orderType) {
                if (!empty($orderType)) {
                    $this->validateOrderType($orderType);
                }
                
                $orderByExpression .= "{$field} {$orderType},";
            }
            
            $orderByExpression = substr($orderByExpression,0, -1);
            $orderByExpression = str_replace(' ,',',',$orderByExpression);
            
            $this->queryBody[] = trim($orderByExpression);
        }
    }
