<?php
    class BaseSelect
    {
        const DEFAULT_SELECTED_FIELDS = '*';
        
        const ORDER_ASC = 'ASC';
        const ORDER_DESC = 'DESC';
        
        private $query;
        
        private $dbName;
        private $tableName;
        
        private $fields;
        
        private $id;
        
        private $where;
        
        private $orderBy;
        private $limit;
        
        public function tableName(string $tableName)
        {
            $this->tableName = $tableName;
        }
        
        public function dbName(string $dbName)
        {
            $this->dbName = $dbName;
        }
        
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
        
        public function where(string $where)
        {
            global $Core;
            $this->where = trim($Core->db->escape($where));
        }
        
        public function orderBy(string $orderBy)
        {
            if (!strstr($orderBy, self::ORDER_ASC) && !strstr($orderBy, self::ORDER_DESC)) {
                throw new Exception("Invalid order provided! Order must contain ASC or DESC!");
            }
            
            $this->orderBy = $orderBy;
        }
        
        public function limit(string $limit)
        {
            $this->limit = $limit;
        }
        
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
        
        private function addOrderByToQuery()
        {
            $this->query .= " ORDER BY ".$this->orderBy;
        }

        private function addLimitToQuery()
        {
            $this->query .= " LIMIT ".$this->limit;
        }
        
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
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    