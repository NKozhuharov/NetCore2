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
         * Sets the collection of fields of the query
         * @param array $fields - the fields which are going to be inserted
         */
        public function setFields(array $fields)
        {
            global $Core;

            if (!empty($this->fields)) {
                throw new exception ("Use only the `setFields` and `setValues` functions or only the `setFieldsAndValues` function");
            }

            $this->fields = $Core->db->escape($fields);
        }

        /**
         * Sets the collection of values of the query
         * @param array $fields - the value which are going to be inserted
         */
        public function setValues(array $values)
        {
            global $Core;

            if (!empty($this->values)) {
                throw new exception ("Use only the `setFields` and `setValues` functions or only the `setFieldsAndValues` function");
            }

            $this->values = $Core->db->escape($values);
        }

        /**
         * Sets the collection of fields and values of the query with a single aray
         * @param array $input - the array which is going to be inserted
         */
        public function setFieldsAndValues(array $input)
        {
            global $Core;

            if (!empty($this->fields) || !empty($this->values)) {
                throw new exception ("Use only the `setFields` and `setValues` functions or only the `setFieldsAndValues` function");
            }

            $this->fields = array();
            $this->values = array();

            foreach ($input as $field => $value) {
                $this->fields[] = $Core->db->escape($field);
                $this->values[] = $Core->db->escape($value);
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
