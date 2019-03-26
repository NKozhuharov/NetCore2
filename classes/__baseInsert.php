<?php
    final class BaseInsert extends BaseQuery
    {
        use BaseInputQuery;
        
        /**
         * @var bool
         * Set the query to ON DUPLICATE KEY UPDATE query
         */
        protected $updateOnDuplicate = false;
        
        /**
         * Creates a new instance of the BaseInsert class
         * @param string $tableName - the name for the table
         */
        public function __construct(string $tableName)
        {
            parent::__construct($tableName);
        }
        
        /**
         * Set the query to ON DUPLICATE KEY UPDATE query (or not)
         * It will throw exception if INSERT IGNORE QUERY is already chosen
         * @param bool $onDuplicateKeyUpdate - set the query to ON DUPLICATE KEY UPDATE query (or not)
         * @throws Exception
         */
        public function setUpdateOnDuplicate(bool $onDuplicateKeyUpdate)
        {
            if ($onDuplicateKeyUpdate && $this->ignore) {
                throw new Exception("INSERT IGNORE query already selected! Choose between INSERT INGORE or ON DUPLICATE KEY UPDATE!");
            }
            
            $this->updateOnDuplicate = $onDuplicateKeyUpdate;
        }
        
        /**
         * Add the ON DUPLICATE KEY UPDATE statement to the query
         */
        private function addOnDuplicateKeyUpdateToQuery()
        {
            if ($this->updateOnDuplicate) {
                $this->query .= " ON DUPLICATE KEY UPDATE ";
                $this->fields = array_values($this->fields);
                $this->values = array_values($this->values);
                
                for ($i = 0; $i < count($this->fields); $i++) {
                    $this->query .= "`{$this->fields[$i]}` = ";
                    $this->addAValueToQuery($this->values[$i]);
                }
                
                $this->query = substr($this->query, 0, -2);
            }
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
            
            $this->addOnDuplicateKeyUpdateToQuery();

            return $this->query;
        }
    }
