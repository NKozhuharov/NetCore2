<?php
    trait QueryWhere
    {
        public function addWhere()
        {
            $this->queryBody[] = 'WHERE';
        }

        public function openBracket()
        {
            $this->queryBody[] = "(";
        }
        
        public function closeBracket()
        {
            $this->queryBody[] = ")";
        }
        
        public function addAND()
        {
            $this->queryBody[] = "AND";
        }
        
        public function addOR()
        {
            $this->queryBody[] = "OR";
        }
        
        public function addExpression($leftCriteria, $comparison, $rightCriteria)
        {
            $this->queryBody[] = "{$leftCriteria} {$comparison} {$rightCriteria}";
        }
    }
