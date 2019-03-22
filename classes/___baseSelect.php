<?php
    class BaseSelect
    {
        const DEFAULT_SELECTED_FIELDS = '*';
        
        const ORDER_ASC = 'ASC';
        const ORDER_DESC = 'DESC';
        
        /**
         * @var string
         * The body of the query
         */
        private $query;
        
        /**
         * @var string
         * The name of the databse
         */
        private $dbName;
        
        /**
         * @var string
         * The name of the table
         */
        private $tableName;
        
        /**
         * @var array
         * A collection of fields for the query
         */
        private $fields;
        
        private $id;
        
        /**
         * @var string
         * The where clause of the query
         */
        private $where;
        
        /**
         * @var string
         * The order by clause of the query
         */
        private $orderBy;
        
        /**
         * @var string
         * The limit of the query
         */
        private $limit;
        
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
         * Adds a field to the query
         * @param string $field - the name of the field
         * @param string $name - the "AS" name for the field
         */
        public function addField(string $field, string $name = null)
        {
            global $Core;
            
            $field = $Core->db->escape($field);
            $name = $Core->db->escape($name);
            
            if (!empty($name)) {
                $this->fields[] = "{$field} AS '{$name}'";
            } else {
                $this->fields[] = $field;
            }
        }
        
        public function id(int $id)
        {
            $this->id = $id;
        }
        
        /**
         * Sets the WHERE clause of the query
         * @param string $where - the body of the where clause
         */
        public function where(string $where)
        {
            global $Core;
            $this->where = trim($Core->db->escape($where));
        }
        
        /**
         * Sets the ORDER BY clause of the query
         * It must contain either ASC or DESC (case sensitive)
         * @param string $where - the body of the order by clause
         * @throws Exception
         */
        public function orderBy(string $orderBy)
        {
            if (!strstr($orderBy, self::ORDER_ASC) && !strstr($orderBy, self::ORDER_DESC)) {
                throw new Exception("Invalid order provided! Order must contain ASC or DESC!");
            }
            
            $this->orderBy = $orderBy;
        }
        
        /**
         * Adds the LIMIT clause to the query
         * It must cointain either ASC or DESC (case sensitive)
         * @param string $limit - the body of the limit clause
         * @throws Exception
         */
        public function limit(string $limit)
        {
            if (preg_match("{^\d+(?:,\d+)*$}", $limit)) {
                $this->limit = $limit;
            } else {
                throw new Exception("LIMIT ({$limit}) must contain only comma separated numbers!");
            }
        }
        
        /**
         * Adds the WHERE clause of the query
         */
        private function addWherePartToQuery()
        {
            if (!empty($this->where)) {
                if (!strstr($this->query, 'WHERE')) {
                    $this->query .= " WHERE ";
                } else {
                    if (substr($this->where, 0, 3) != 'AND' && substr($this->where, 0, 2) != "OR") {
                        throw new Exception("Id is already selected. Provide OR/AND to your WHERE statement!");
                    }
                }
                $this->query .= " {$this->where}";
            }
        }
        
        private function addIdToQuery()
        {
            if (!empty($this->id)) {
                $this->query .= " WHERE `id`={$this->id}";
            }
        }
        
        /**
         * Adds the ORDER BY clause of the query
         */
        private function addOrderByToQuery()
        {
            if (!empty($this->orderBy)) {
                $this->query .= " ORDER BY ".$this->orderBy;
            }
        }
        
        /**
         * Adds the LIMIT clause of the query
         */
        private function addLimitToQuery()
        {
            if (!empty($this->limit)) {
                $this->query .= " LIMIT ".$this->limit;
            }
        }
        
        /**
         * Builds the query from all of it's parts
         * Throws exception if the table name is not set
         * @throws Excpetion
         * @return string
         */
        public function buildQuery()
        {
            global $Core;
            
            $this->query = 'SELECT ';
            $this->query .= empty($this->fields) ? self::DEFAULT_SELECTED_FIELDS : implode(',', $this->fields);
            $this->query .= ' FROM ';
            
            if (empty($this->tableName)) {
                throw new Exception("Provide a table name!");
            }
            
            $this->query .= "`".(empty($this->dbName) ? $Core->dbName : $this->dbName)."`.`{$this->tableName}`";

            $this->addIdToQuery();
            $this->addWherePartToQuery();
            $this->addOrderByToQuery();
            $this->addLimitToQuery();
            
            return $this->query;
        }
        
        /**
         * Dumps (echoes) the body of the query
         */
        public function dumpQuery()
        {
            echo $this->buildQuery();
        }
    }
    