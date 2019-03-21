<?php
    class UpdateQuery extends Query
    {
        use UpdateInsertBase;
        use QueryWhere;
        use QueryLimit;
        use QueryOrder;
        
        public function __construct()
        {
            parent::__construct(self::TYPE_UPDATE);
            $this->queryBody[] = self::TYPE_UPDATE;
        }
        
        protected function buildSpecificPart()
        {
            $updatePart = 'SET ';
            
            $this->fields = array_values($this->fields);
            $this->values = array_values($this->values);
            
            for ($i=0; $i < count($this->fields); $i++) {
                $updatePart .= "`{$this->fields[$i]}` = ";
                $this->addAValueToQuery($updatePart, $this->values[$i]);
            }
            
            return substr($updatePart, 0, -2);
        }
        
        public function execute()
        {
            global $Core;
            
            return $Core->db->query(
                $this->getBody()
            );
        }
    }