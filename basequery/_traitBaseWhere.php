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
        public function setWhere(string $where)
        {
            $this->where = trim($where);
        }
        
        /**
         * Gets the current WHERE clause of the query
         * @return string
         */
        public function getWhere()
        {
            return $this->where;
        }

        /**
         * Adds the WHERE clause of the query
         */
        protected function addWhereToQuery()
        {
            if (!empty($this->where)) {
                $this->query .= " WHERE {$this->where}";
            }
        }
    }
