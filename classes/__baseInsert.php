<?php
    final class BaseInsert extends BaseQuery
    {
        use BaseInputQuery;
        
        /**
         * Creates a new instance of the BaseInsert class
         * @param string $tableName - the name for the table
         */
        public function __construct(string $tableName)
        {
            parent::__construct($tableName);
        }
        
        /**
         * Builds the query from all of it's parts
         * @return string
         */
        public function build()
        {
            global $Core;
            
            $this->validateFieldsAndValues();

            $this->query = "INSERT ";
            $this->query .= $this->ignore ? "IGNORE " : '';
            $this->query .= "INTO ";
            $this->query .= "`".(empty($this->dbName) ? $Core->dbName : $this->dbName)."`.`{$this->tableName}`";
            
            $this->query .= ' (';
            
            foreach ($this->fields as $field) {
                $this->query .= "`{$field}`,";
            }
            
            $this->query = substr($this->query, 0, -1);
            
            $this->query .= ") VALUES (";
            
            foreach ($this->values as $value) {
                $this->addAValueToQuery($value);
            }
            
            $this->query = substr($this->query, 0, -2);
            
            $this->query .= ")";

            return $this->query;
        }
    }
