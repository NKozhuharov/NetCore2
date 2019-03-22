<?php
    class BaseDelete extends BaseQuery
    {
        use BaseWhere;
        
        public function buildQuery()
        {
            global $Core;
            
            if (empty($this->tableName)) {
                throw new Exception("Provide a table name!");
            }
            
            $this->query = 'DELETE FROM ';
            $this->query .= "`".(empty($this->dbName) ? $Core->dbName : $this->dbName)."`.`{$this->tableName}`";
            
            $this->addWherePartToQuery();
            
            return $this->query;
        }
        
        public function execute()
        {
            global $Core;
            
            return $Core->db->query($this->buildQuery());
        }
    }