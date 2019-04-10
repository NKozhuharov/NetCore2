<?php
    trait BaseLimit
    {
        /**
         * @var string
         * The limit of the query
         */
        private $limit;

        /**
         * Adds the LIMIT clause to the query
         * Throws Exception if limit or offset are less than 0
         * @param int $limit - how many rows to select
         * @param int $offset - how many rows to skip
         * @throws Exception
         */
        public function setLimit(int $limit, int $offset = null)
        {
            if ($limit < 0) {
                throw new Exception("Limit must be bigger than -1!");
            }

            $this->limit = $limit;
            if ($offset !== null) {
                if ($offset < 0) {
                    throw new Exception("Offset must be bigger than -1!");
                }
                $this->limit .= ', '.$offset;
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
    }
