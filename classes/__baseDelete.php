<?php
    final class BaseDelete extends BaseQuery
    {
        use BaseWhere;
        use BaseLimit;
        
        /**
         * Creates a new instance of the BaseDelete class
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
         * @param int $offset - how many rows to skip (forbidden in delete)
         * @throws Exception
         */
        public function setLimit(int $limit, int $offset = null)
        {
            if ($limit < 0) {
                throw new Exception("Limit must be bigger than -1!");
            }
            
            if ($offset !== null) {
                throw new Exception("Offset is not allowed in delete queries!");
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

            $this->query = 'DELETE FROM ';
            $this->query .= "`".(empty($this->dbName) ? $Core->dbName : $this->dbName)."`.`{$this->tableName}`";

            $this->addWherePartToQuery();
            $this->addLimitToQuery();

            return $this->query;
        }
    }