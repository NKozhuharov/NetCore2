<?php
    trait BaseOrderBy
    {
        /**
         * @var string
         * The order by clause of the query
         */
        private $orderBy;

        /**
         * Sets the ORDER BY clause of the query
         * It must contain either ASC or DESC (case sensitive)
         * @param string $where - the body of the order by clause
         * @throws Exception
         */
        public function setOrderBy(string $orderBy)
        {
            if (!strstr($orderBy, self::ORDER_ASC) && !strstr($orderBy, self::ORDER_DESC)) {
                throw new Exception("Invalid order provided! Order must contain ASC or DESC!");
            }

            $this->orderBy = $orderBy;
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
    }
