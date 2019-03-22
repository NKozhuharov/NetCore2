<?php
    class Base2
    {
        const ORDER_ASC = 'ASC';
        const ORDER_DESC = 'DESC';

        //main variables
        /**
         * @var string
         * The name of the table. THIS IS REQUIRED!!!
         */
        protected $tableName;

        /**
         * @var TableFields
         * An instance of TableFields, which has the data for all of the fields in the table for the model
         */
        protected $tableFields;

        /**
         * @var string
         * Allows the user to set a default ordering for the select queries
         * This is the field, which is used for ordering
         */
        protected $orderByField = 'id';

        /**
         * @var string
         * Allows the user to set a default ordering for the select queries
         * This is the order type
         */
        protected $orderByType = 'ASC';
        
        /**
         * @var int
         * Allows the user to set a cache time for all the select queries
         */
        protected $queryCacheTime = 0;

        //additional field settings
        /**
         * @var bool
         * Allows the user to select all timestamp fields as UNIX_TIMESTAMPs
         */
        protected $returnTimestamps = false;

        /**
         * @var string
         * Allows the user to create a column in the database, which can be use to hide certain rows
         * This is the name of the field (column)
         */
        protected $hiddenFieldName = 'hidden';

        /**
         * @var string
         * Allows the user to create a column in the database, which can be use to hide certain rows
         * Changing this property will override the code, which hides the rows (and it will show them)
         */
        protected $showHiddenRows = false;

        /**
         * @var array
         * Allows the user to automatically store and get data in delimiter separated fields
         * This is a list of the fields
         */
        protected $explodeFields = array();
        /**
         * @var string
         * Allows the user to automatically store and get data in delimiter separated fields
         * This is the delmiter of the fields
         */
        protected $explodeDelimiter = '|';


        //translations
        protected $translationFields = array();     //fields in the table, which has translations in the {table}_lang

        protected $translateResult = true;

        /**
         * Ensures that tableFields variable is initialized; if not it will initialize it
         * Throws Exception if the table name is not provided
         * @throws Exception
         */
        private function checkTableFields()
        {
            if (empty($this->tableName)) {
                throw new Exception("Provide a table name for the model!");
            }

            if (empty($this->tableFields)) {
                $this->tableFields = new TableFields($this->tableName);
            }
        }

        /**
         * Retuns the current TableName
         * @return string
         */
        public function getTableName()
        {
            return $this->tableName;
        }

        /**
         * Changes the current TableName.
         * Doing so will re-initialize the tableFields
         * Use with caution!!
         */
        public function changeTableName(string $name)
        {
            if (empty($name)) {
                throw new Exception("Error in ".get_class($this).": Empty table name!");
            }
            $this->tableName = $name;
            $this->tableFields = new TableFields($this->tableName);
        }

        /**
         * Retuns the current order by field
         * @return string
         */
        public function getOrderByField()
        {
            return $this->orderByField;
        }

        /**
         * Retuns the current order by type
         * @return string
         */
        public function getOrderByType()
        {
            return $this->orderByType;
        }

        /**
         * Set the order by field to a new value
         * Throws Exception if the provided field does not exist
         * @param string $field - the name of the new field
         * @throws Exception
         */
        public function changeOrderByField(string $field)
        {
            if (!array_key_exists($field, $this->tableFields->getFields())) {
                throw new Exception("This field does not exist!");
            }
            $this->orderByField = $field;
        }

        /**
         * Set the order by type to ASC
         */
        public function changeOrderByTypeToASC()
        {
            $this->orderByType = self::ORDER_ASC;
        }

        /**
         * Set the order by type to DESC
         */
        public function changeOrderByTypeToDESC()
        {
            $this->orderByType = self::ORDER_DESC;
        }
        
        /**
         * This will attempt to translate all select queries from now on
         */
        public function turnOnTranslation()
        {
            $this->translateResult = true;
        }
        
        /**
         * This will attempt to translate all select queries from now on
         */
        public function turnOffTranslation()
        {
            $this->translateResult = false;
        }
        
        /**
         * It will reset the cache for all select queries from now on
         */
        public function resetQueryCache()
        {
            $this->queryCacheTime = -1;
        }
        
        /**
         * It will turn off the cache for all select queries from now on
         */
        public function turnOffQueryCahce()
        {
            $this->queryCacheTime = 0;
        }
        
        /**
         * It will turn on the cache for all select queries from now on
         * It will throw Exception if the provided parameter is <= 0
         * @param int $cacheTime - allows to set the specific cache time (in minutes)
         * @throws Exception
         */
        public function turnOnQueryCache(int $cacheTime)
        {
            if ($cacheTime <= 0) {
                throw new Exception("Query cache time should be bigger than 0");
            }
            
            $this->queryCacheTime = $cacheTime;
        }

        //PRIVATE ADDITIONAL SELECT FUNCTIONS
    
        /**
         * Add all fields of type 'timestamp' to the current select query
         * It will return the current query, containing the additional fields
         * @param BaseSelect $selector - the current select query
         * @return BaseSelect
         */
        private function addTimestampFieldsToGetQuery(BaseSelect $selector)
        {
            foreach ($this->tableFields->getFields() as $field => $fieldDescription) {
                if (in_array($fieldDescription['type'] ,array('timestamp', 'datetime'))) {
                    $selector->addField("UNIX_TIMESTAMP(`{$field}`)", "{$field}_timestamp");
                }
            }

            return $selector;
        }

        /**
         * Parses all the additional fields of the Base class and forms a single WHERE clause
         * Supports: hidden field and additional value
         *
         * @param string $additional - the addition to the WHERE clause (if any)
         * @return string
         */
        private function getSelectQueryWherePart(string $additional = null)
        {
            $where = array();
            if (array_key_exists($this->hiddenFieldName, $this->tableFields->getFields()) && !$this->showHiddenRows) {
                $where[] = "(`{$this->hiddenFieldName}` IS NULL OR `{$this->hiddenFieldName}` = 0)";
            }
            if (!empty($additional)) {
                $where[] = $additional;
            }

            return implode(" AND ", $where);
        }
        
        /**
         * Checks if a translation is available for the current model
         * @return bool
         */
        private function isTranslationAvailable()
        {
            global $Core;
            
            return $this->translateResult !== false && 
                    !empty($this->translationFields) && 
                    $Core->Language->currentLanguageIsDefaultLanguage();
        }

        /**
         * Parse the result from a SELECT query for any delimitor seprated fields (explode fields)
         * It will break them down to arrays and will return the parsed array
         * @param array $result
         * @return array
         */
        private function parseExplodeFields(array $result)
        {
            if (!empty($this->explodeFields)) {
                foreach ($this->explodeFields as $field) {
                    foreach ($result as $resultKey => $resultRow) {
                        if (empty($resultRow[$field])) {
                            $resultRow[$field] = array();
                        } else {
                            if (substr($resultRow[$field], 0, strlen($this->explodeDelimiter)) == $this->explodeDelimiter) {
                                $resultRow[$field] = substr($resultRow[$field], strlen($this->explodeDelimiter));
                            }

                            $explodedArray = array();

                            foreach(explode($this->explodeDelimiter, $resultRow[$field]) as $key => $value){
                                $explodedArray[$key] = trim($value);
                            }
                            $resultRow[$field] = $explodedArray;;
                        }
                        $result[$resultKey] = $resultRow;
                    }
                }
            }

            return $result;
        }

        /**
         * Basic method of the Base class
         * It will attempt to select all fields from the table of the model (SELECT *)
         * It will use the default parameters for limiting and ordering of the result
         * If limit or orderBy is provided, it will override the default parameters
         * The additional parameter is used to provide additional filtering (custom WHERE clause)
         * @param int $limit
         * @param string $additional
         * @param string $orderBy
         * @return array
         */
        public function getAll(int $limit = null, string $additional = null, string $orderBy = null)
        {
            global $Core;

            $this->checkTableFields();

            $selector = new BaseSelect;

            $selector->tableName($this->tableName);

            $selector->addField('*');

            if ($this->returnTimestamps === true) {
                $selector = $this->addTimestampFieldsToGetQuery($selector);
            }

            $selector->where($this->getSelectQueryWherePart($additional));

            if ($orderBy === null) {
                $selector->orderBy("{$this->orderByField} {$this->orderByType}");
            } else {
                $selector->orderBy($orderBy);
            }

            if ($limit === null) {
                $selector->limit((($Core->rewrite->currentPage - 1) * $Core->itemsPerPage).','.$Core->itemsPerPage);
            } else {
                $selector->limit($limit);
            }

            $Core->db->query($selector->buildQuery(), $this->queryCacheTime, 'simpleArray', $result);
                                                         
            $result = $this->getTranslation($result, $Core->Language->currentLanguageId);
            
            //to do: fix error count fields
            $result = $this->parseExplodeFields($result);

            return !empty($result) ? $result : array();
        }
        
        /**
         * It will attempt to translate the provided result from a seelct query, using a '<table_name>_lang' 
         * table from the database, where <table_name> is the name of the table, which is used to initialize the model.
         * The result MUST contain a column 'id', which will be used to determine the object id for the translation
         * The '<table_name>_lang' MUST contain a column 'object_id', which will be used to link the original value
         * and it's translation.
         * 
         * @param array $result - the result from a select query
         * @param int $languageId - the id of the language
         * @return array
         */
        public function getTranslation(array $result, int $languageId = null)
        {
            global $Core;

            if ($languageId === null || !$this->isTranslationAvailable()) {
                return $result;
            }
            
            $resultObjectIds = array_column($result, 'id');
            
            if (empty($resultObjectIds)) {
                throw new Exception ("Cannot translate query results, which do not contain the id column!");
            }
            
            $selector = new BaseSelect();
            $selector->tableName("{$this->tableName}_lang");
            $selector->where("`object_id` IN (".(implode(', ', $resultObjectIds)).") AND `lang_id`={$languageId}");
            
            $Core->db->query($selector->buildQuery(), $this->queryCacheTime, 'fillArray', $translations, 'object_id');
            
            if (!empty($translations)) {
                $fieldIds = array_flip($resultObjectIds);
                
                foreach ($translations as $objectId => $row) {
                    $resultKey = $fieldIds[$objectId];
                    if (isset($result[$resultKey])) {
                        foreach ($this->translationFields as $field) {
                            if (!empty($row[$field])) {
                                $result[$resultKey][$field] = $row[$field];
                            } else {
                                $result[$resultKey][$field] = $result[$resultKey][$field];
                            }
                        }
                    }
                }
            }
            
            return $result;
        }
    }
































