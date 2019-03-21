<?php
    class InsertQuery extends Query
    {
        use UpdateInsertBase;
        
        public function __construct()
        {
            parent::__construct(self::TYPE_INSERT);
            $this->queryBody[] = self::TYPE_INSERT." INTO";
        }
        
        public function setInsertIgnore()
        {
            array_pop($this->queryBody);
            $this->queryBody[] = self::TYPE_INSERT." IGNORE INTO";
        }
        
        protected function buildSpecificPart()
        {
            $insertPart = '(';
            
            foreach ($this->fields as $field) {
                $insertPart .= "`{$field}`,";
            }
            
            $insertPart = substr($insertPart, 0, -1);
            
            $insertPart .= ") VALUES (";
            
            foreach ($this->values as $value) {
                $this->addAValueToQuery($insertPart, $value);
            }
            
            $insertPart = substr($insertPart, 0, -2);
            
            $insertPart .= ")";
            
            return $insertPart;
        }
        
        public function execute()
        {
            global $Core;
            
            return $Core->db->query(
                $this->getBody()
            );
        }
    }