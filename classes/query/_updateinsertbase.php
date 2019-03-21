<?php
    trait UpdateInsertBase
    {
        protected $fields;
        protected $values;
        
        public function setFields(array $fields)
        {
            $this->fields = $Core->db->escape($fields);
        }
        
        public function setValues(array $values)
        {
            $this->values = $Core->db->escape($values);
        }
        
        public function setFieldsAndValues(array $input)
        {
            global $Core;
            
            $this->fields = array();
            $this->values = array();
            
            foreach ($input as $field => $value) {
                $this->fields[] = $Core->db->escape($field);
                $this->values[] = $Core->db->escape($value);
            }
        }
        
        protected function addAValueToQuery(string &$query, $value)
        {
            if (empty($value) && !((is_numeric($value) && $value == '0'))) {
                $query .= 'NULL';
            } else {
                $query .= "'$value'";
            }
            $query .= ', ';
        }
        
        protected function validateBuildData()
        {
            if (empty($this->tableName)) {
                throw new exception ("Provide a table name!");
            }
            
            if (empty($this->fields)) {
                throw new exception ("Provide at least one field!");
            }
            
            if (empty($this->values)) {
                throw new exception ("Provide at least one value!");
            }
            
            if (count($this->fields) != count($this->values)) {
                throw new exception ("Fields and values have to be the same amount!");
            }
        }
    }
