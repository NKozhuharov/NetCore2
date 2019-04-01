<?php
    trait BaseInputQuery
    {
        /**
         * @var bool
         * If this is true, the query will be an INSERT IGNORE query.
         */
        private $ignore = false;

        /**
         * @var array
         * A collection of fields for the query
         */
        private $fields = array();

        /**
         * @var array
         * A collection of values for the query
         */
        private $values = array();

        /**
         * Allows to set the collection of fields one by one
         * Throws exception if the field name is empty
         * @param string $field - the name of the field
         * @param string $value - the value of the field
         * @throws Exception
         */
        public function addFieldAndValue(string $field, string $value)
        {
            global $Core;
            
            $field = $Core->db->escape(trim($field));
            
            if (empty($field)) {
                throw new Exception("Field names cannot be empty");
            }
            
            $this->fields[] = $field;
            $this->values[] = $Core->db->escape($value);
        }
        
        /**
         * Allows to set the collection of fields one by one
         * Throws exception if the field name is empty
         * @param string $field - the name of the field
         * @param string $value - the value of the field
         * @throws Exception
         */
        public function addField(string $field, string $value)
        {
            $this->addFieldAndValue($field, $value);
        }

        /**
         * Sets the collection of fields and values of the query with a single aray
         * Throws exception if a field name is empty
         * @param array $input - the array which is going to be inserted
         * @throws Exception
         */
        public function setFieldsAndValues(array $input)
        {
            $this->fields = array();
            $this->values = array();

            foreach ($input as $field => $value) {
                $this->addFieldAndValue($field, $value);
            }
        }

        /**
         * Choose whether the query is goign to be an IGNORE query
         * @param bool @ignore - if true, it will be an IGNORE query
         */
        public function setIgnore(bool $ignore = null)
        {
            $this->ignore = !empty($ignore) ? true : false;
        }

        /**
         * Adds a value to the query string, making sure it is NULL, when it's empty
         * @param mixed $value - the value to add
         */
        private function addAValueToQuery($value)
        {
            if (empty($value) && !((is_numeric($value) && $value == '0'))) {
                $this->query .= 'NULL';
            } else {
                $this->query .= "'$value'";
            }
            $this->query .= ', ';
        }

        /**
         * Validates the fields and values collections
         * Makes sure they are not empty and they are the same size
         */
        private function validateFieldsAndValues()
        {
            if (empty($this->fields)) {
                throw new exception ("Provide at least one field");
            }

            if (empty($this->values)) {
                throw new exception ("Provide at least one value");
            }

            if (count($this->fields) != count($this->values)) {
                throw new exception ("Fields and values have to be the same amount");
            }
        }
    }
