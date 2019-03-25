<?php
    final class BaseUpdate extends BaseQuery
    {
        use BaseInputQuery;
        use BaseWhere;
        use BaseOrderBy;
        use BaseLimit;
        
        /**
         * Creates a new instance of the BaseInsert class
         * @param string $tableName - the name for the table
         */
        public function __construct(string $tableName)
        {
            parent::__construct($tableName);
        }
        
        /**
         * Adds the LIMIT clause to the query
         * Throws Exception if limit is less than 0 or ofsset is set
         * @param int $limit - how many rows to select
         * @param int $offset - how many rows to skip (forbidden in update)
         * @throws Exception
         */
        public function setLimit(int $limit, int $offset = null)
        {
            if ($limit < 0) {
                throw new Exception("Limit must be bigger than -1!");
            }
            
            if ($offset !== null) {
                throw new Exception("Offset is not allowed in update queries!");
            }
            
            $this->limit = $limit;
        }
        
        /**
         * Builds the query from all of it's parts
         * @return string
         */
        public function build()
        {
            global $Core;
            
            $this->validateFieldsAndValues();

            $this->query = "UPDATE ";
            $this->query .= $this->ignore ? "IGNORE " : '';
            $this->query .= "`".(empty($this->dbName) ? $Core->dbName : $this->dbName)."`.`{$this->tableName}`";
            $this->query .= " SET ";
            
            $this->fields = array_values($this->fields);
            $this->values = array_values($this->values);
            
            for ($i=0; $i < count($this->fields); $i++) {
                $this->query .= "`{$this->fields[$i]}` = ";
                $this->addAValueToQuery($this->values[$i]);
            }
            
            $this->query = substr($this->query, 0, -2);
            
            $this->addWhereToQuery();
            $this->addOrderByToQuery();
            $this->addLimitToQuery();

            return $this->query;
        }
    }
