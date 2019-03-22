<?php
    abstract class BaseQuery
    {
        const ORDER_ASC = 'ASC';
        const ORDER_DESC = 'DESC';
        
         /**
         * @var string
         * The body of the query
         */
        protected $query;
        
        /**
         * @var string
         * The name of the databse
         */
        protected $dbName;
        
        /**
         * @var string
         * The name of the table
         */
        protected $tableName;
        
        /**
         * Builds the query from all of it's parts
         * @return string
         */
        public abstract function buildQuery();
        
        /**
         * Set the name of the table for the query
         * @param string $tableName - the name for the table
         */
        public function tableName(string $tableName)
        {
            $this->tableName = $tableName;
        }
        
        /**
         * Set the name of the database for the query
         * @param string $dbName - the name for the database
         */
        public function dbName(string $dbName)
        {
            $this->dbName = $dbName;
        }
        
        /**
         * Dumps (echoes) the body of the query
         */
        public function dumpQuery()
        {
            echo $this->buildQuery();
        }
    }