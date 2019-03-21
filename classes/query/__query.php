<?php
    abstract class Query
    {
        const ORDER_TYPE_ASC  = 'ASC';
        const ORDER_TYPE_DESC = 'DESC';

        const TYPE_INSERT = 'INSERT';
        const TYPE_SELECT = 'SELECT';
        const TYPE_UPDATE = 'UPDATE';
        const TYPE_DELETE = 'DELETE';

        private $type;

        protected $tableName;

        protected $queryBody;
    
        protected abstract function validateBuildData();
         
        protected abstract function buildSpecificPart();
        
        public abstract function execute();

        public function __construct(string $type)
        {
            if (
                $type != self::TYPE_INSERT &&
                $type != self::TYPE_SELECT &&
                $type != self::TYPE_UPDATE &&
                $type != self::TYPE_DELETE
            ) {
                throw new exception ("Query supports one of the following types - INSERT, SELECT, UPDATE, DELETE!");
            }

            $this->type = $type;
        }

        public function setTable(string $tableName)
        {
            $this->tableName = $tableName;
        }

        public function getBody()
        {
            return implode(' ', $this->queryBody);
        }

        public function addDbNameAndTableNameToQuery(string $tableName, string $dbName = null)
        {
            global $Core;
            $this->queryBody[] = "`".(empty($dbName) ? "{$Core->dbName}" : "{$dbName}")."`.`$tableName`";
        }
        
        public final function build()
        {
            $this->validateBuildData();
            
            $this->queryBody[] = $this->addDbNameAndTableNameToQuery($this->tableName);
            
            $this->queryBody[] = $this->buildSpecificPart();
        }
    }
