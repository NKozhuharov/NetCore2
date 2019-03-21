<?php
    trait QueryLimit
    {
        public function offsetAndLimit(int $offset, int $limit)
        {
            $this->queryBody[] = "LIMIT {$offset}, {$limit}";
        }
        
        public function limit(int $limit)
        {
            $this->queryBody[] = "LIMIT {$limit}";
        }
    }
