<?php
    trait BaseWhere
    {
        /**
         * @var string
         * The where clause of the query
         */
        protected $where;
        
        /**
         * Sets the WHERE clause of the query
         * @param string $where - the body of the where clause
         */
        public function where(string $where)
        {
            global $Core;
            
            if (stristr($where, "'")) {
                throw new Exception ("No quotes are allowed in the basic where method! Use 'whereWithoutEscape' instead!");
            }
            
            $this->where = trim($Core->db->escape($where));
        }
        
        /**
         * Sets the WHERE clause of the query
         * This WILL not escape the query, you have to do that yourself!!
         * USE WITH CAUTION
         * @param string $where - the body of the where clause
         */
        public function whereWithoutEscape(string $where)
        {
            $this->where = trim($where);
        }
        
        /**
         * Sets the WHERE clause of the query
         * @param string $where - the body of the where clause
         */
        public function setWhere(string $where)
        {
            $this->where($where);
        }
        
        /**
         * Adds the WHERE clause of the query
         */
        protected function addWherePartToQuery()
        {
            if (!empty($this->where)) {
                $this->query .= " WHERE {$this->where}";
            }
        }
    }
