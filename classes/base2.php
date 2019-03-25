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
         * Allows the user to set a parent field for the table for this model
         * This is usually set to a foreign key field for the table
         */
        protected $parentField;

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
        private $queryCacheTime = 0;

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
        public function turnOffQueryCache()
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
         * Initializes a selector for all of the select queries, using BaseSelect class
         * Sets the table name, the field (*), the additional timestamp fields
         *
         * @return BaseSelect
         */
        private function initializeBaseSelector()
        {
            $this->checkTableFields();

            $selector = new BaseSelect($this->tableName);

            $selector->addField('*');

            if ($this->returnTimestamps === true) {
                $selector = $this->addTimestampFieldsToGetQuery($selector);
            }

            return $selector;
        }

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
         * Supports hidden field and additional value
         *
         * @param string $additional - the addition to the WHERE clause (if any)
         * @return string
         */
        private function getSelectQueryWhereClause(string $additional = null)
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
         * Adds LIMIT and ORDER BY overrides to the selector of a select query
         * If they are null, it will use the default values from Core (limit) and the model (order by)
         * @param BaseSelect $selector - the current selector
         * @param int $limit - the limit override
         * @param string $orderBy - the order by override
         * @return BaseSelect
         */
        private function addSelectQueryOverrides(BaseSelect $selector, int $limit = null, string $orderBy = null)
        {
            global $Core;

            if ($orderBy === null) {
                $selector->setOrderBy("{$this->orderByField} {$this->orderByType}");
            } else {
                $selector->setOrderBy($orderBy);
            }

            if ($limit === null) {
                $selector->setLimit((($Core->rewrite->currentPage - 1) * $Core->itemsPerPage), $Core->itemsPerPage);
            } else {
                $selector->setLimit($limit);
            }

            return $selector;
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
         * Parses the result from a select query
         * Ensures that it is translated and the delimitor separated fields are parsed
         * Ensures that the result will allways be an array
         * @param $result - the result from a select query
         * @return array
         */
        private function parseSelectQueryResult($result)
        {
            global $Core;

            if (!empty($result)) {
                //to do: fix error count fields
                return $this->parseExplodeFields(
                    $this->getTranslation($result, $Core->Language->currentLanguageId)
                );
            }
            return array();
        }

        /**
         * Parse the result from a SELECT query for any delimitor seprated fields (explode fields)
         * It will break them down to arrays and will return the parsed array
         * @param array $result
         * @return array
         */
        private function parseExplodeFields(array $result)
        {
            if (!empty($this->explodeFields) && !empty($result)) {
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

        //SELECT QUERIES

        /**
         * Basic method of the Base class
         * It will attempt to select all fields from the table of the model (SELECT *)
         * It will use the default parameters for limiting and ordering of the result
         * If limit or orderBy is provided, it will override the default parameters
         * The additional parameter is used to provide additional filtering (custom WHERE clause)
         * @param int $limit - the limit override
         * @param string $additional - the where clause override
         * @param string $orderBy - the order by override
         * @return array
         */
        public function getAll(int $limit = null, string $additional = null, string $orderBy = null)
        {
            global $Core;

            $selector = $this->initializeBaseSelector();

            $selector->setWhere($this->getSelectQueryWhereClause($additional));

            $selector = $this->addSelectQueryOverrides($selector, $limit, $orderBy);
            
            $selector->setCacheTime($this->queryCacheTime);
            
            return $this->parseSelectQueryResult($selector->execute());
        }

        /**
         * Basic method of the Base class
         * It will select all rows from the table of the model, which contain the provided parentId
         * It requires the parentField property to be set first!
         * It will attempt to select all fields from the table of the model (SELECT *)
         * It will use the default parameters for limiting and ordering of the result
         * If limit or orderBy is provided, it will override the default parameters
         *
         * @param int $parentId - the id for the parent field
         * @param int $limit - the limit override
         * @param string $orderBy - the order by override
         * @return array
         */
        public function getByParentId(int $parentId, int $limit = null, string $orderBy = null)
        {
            if (empty($this->parentField)) {
                throw new Exception("Set a parent field first!");
            }

            if ($parentId <= 0) {
                throw new Exception("Parent id should be bigger than 0!");
            }

            $this->checkTableFields();

            if (!array_key_exists($this->parentField, $this->tableFields->getFields())) {
                throw new Exception("The field '{$this->parentField}' does not exist in table '{$this->tableName}'!");
            }

            $selector = $this->initializeBaseSelector();

            $selector->setWhere($this->getSelectQueryWhereClause("`{$this->parentField}` = {$parentId}"));

            $selector = $this->addSelectQueryOverrides($selector, $limit, $orderBy);
            
            $selector->setCacheTime($this->queryCacheTime);

            return $this->parseSelectQueryResult($selector->execute());
        }

        /**
         * Basic method of the Base class
         * It will select a single rows from the table of the model, which contains the provided id
         *
         * @param int $rowId - the id of the row
         * @return array
         */
        public function getById(int $rowId)
        {
            global $Core;

            if ($rowId <= 0) {
                throw new Exception("Id should be bigger than 0!");
            }

            $this->checkTableFields();

            if (!array_key_exists($this->parentField, $this->tableFields->getFields())) {
                throw new Exception("The field 'id' does not exist in table '{$this->tableName}'!");
            }

            $selector = $this->initializeBaseSelector();
            $selector->setWhere($this->getSelectQueryWhereClause("`id` = $rowId"));

            $Core->db->query($selector->build(), $this->queryCacheTime, 'simpleArray', $result);
            
            $result = $this->parseSelectQueryResult($result);
            
            return !empty($result) ? current($result) : array();
        }

        /**
         * Basic method of the Base class
         * It will return the count of all rows in the table
         * The additional parameter can be used to put a where clause on the query
         * @param string $additional - the where clause override
         * @return array
         */
        public function getCount(string $additional = null)
        {
            $selector = new BaseSelect($this->tableName);

            $selector->addField("COUNT(*)", 'ct');

            if (!empty($additional)) {
                $selector->setWhere($additional);
            }
            
            $selector->setCacheTime($this->queryCacheTime);
            $selector->setGlobalTemplate('fetch_assoc');
            
            return $selector->execute()['ct'];
        }

        /**
         * Basic method of the Base class
         * It will return the count of all rows in the table, which contain the provided parentId
         * It requires the parentField property to be set first!
         * The additional parameter can be used to put an additional where clause on the query
         * @param int $parentId - the id for the parent field
         * @param string $additional - the where clause override
         * @return array
         */
        public function getCountByParentId(int $parentId, string $additional = null)
        {
            if (empty($this->parentField)) {
                throw new Exception("Set a parent field first!");
            }

            if ($parentId <= 0) {
                throw new Exception("Parent id should be bigger than 0!");
            }

            $this->checkTableFields();

            if (!array_key_exists($this->parentField, $this->tableFields->getFields())) {
                throw new Exception("The field '{$this->parentField}' does not exist in table '{$this->tableName}'!");
            }

            if (!empty($additional)) {
                $additional = " AND {$additional}";
            }

            return $this->getCount("`{$this->parentField}` = {$parentId}{$additional}");
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

            if ($languageId === null || !$this->isTranslationAvailable() || empty($result)) {
                return $result;
            }

            $resultObjectIds = array_column($result, 'id');

            if (empty($resultObjectIds)) {
                throw new Exception ("Cannot translate query results, which do not contain the id column!");
            }

            $selector = new BaseSelect();
            $selector->tableName("{$this->tableName}_lang");
            $selector->setWhere("`object_id` IN (".(implode(', ', $resultObjectIds)).") AND `lang_id`={$languageId}");

            $Core->db->query($selector->build(), $this->queryCacheTime, 'fillArray', $translations, 'object_id');

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
        
        /**
         * Deletes all rows in the table of the model that match the provided where override
         * Returns the number of deleted rows
         * @param string $additional - the where clause override
         * @return int
         */
        public function delete(string $additional)
        {
            if (empty($additional)) {
                throw new Exception("A delete query without where parameter is not allowed! Use deleteAll instead!");
            }

            $deleter = new BaseDelete($this->tableName);
            $deleter->setWhere($additional);
            
            return $deleter->execute();
        }
        
        /**
         * Deletes all rows in the table of the model
         * Returns the number of deleted rows
         * @return int
         */
        public function deleteAll()
        {
            $deleter = new BaseDelete($this->tableName);
            
            return $deleter->execute();
        }
        
        /**
         * Deletes all rows in the table of the model that match the provided id
         * Returns the number of deleted rows
         * @param int $id - the id of the row
         * @return int
         */
        public function deleteById(int $rowId)
        {
            if ($rowId <= 0) {
                throw new Error("Object id must be bigger than 0!");
            }
            
            $deleter = new BaseDelete($this->tableName);
            $deleter->setWhere("`id` = {$rowId}");
            
            return $deleter->execute();
        }
        
        /**
         * Deletes all rows in the table of the model that match the provided parent id
         * Returns the number of deleted rows
         * @param int $id - the parent id of the rows
         * @return int
         */
        public function deleteByParentId(int $parentId)
        {
            global $Core;

            if (empty($this->parentField)) {
                throw new Exception("Set a parent field first!");
            }

            if ($parentId <= 0) {
                throw new Exception("Parent id should be bigger than 0!");
            }

            $this->checkTableFields();

            if (!array_key_exists($this->parentField, $this->tableFields->getFields())) {
                throw new Exception("The field '{$this->parentField}' does not exist in table '{$this->tableName}'!");
            }

            $deleter = new BaseDelete($this->tableName);
            $deleter->setWhere("`{$this->parentField}` = {$parentId}");

            return $deleter->execute();
        }
        
        /**
         * Checks insert or update query input the field 'id'
         * Throws BaseException if the field is present
         * @param array $input - the input of query
         * @throws BaseException
         */
        private function checkInputForFieldId(array $input)
        {
            if (isset($input['id'])) {
                throw new BaseException("Field `id` is not allowed!", null, get_class($this));
            }
        }
        
        /**
         * Checks insert or update query input for missing fields
         * Throws BaseException if a missing field is found
         * @param array $input - the input of query
         * @throws BaseException
         */
        private function checkForMissingFields(array $input) 
        {
            $missingFields = array();
            foreach ($this->tableFields->getRequiredFields() as $requiredField) {
                if (!isset($input[$requiredField])) {
                    $missingFields[] = $requiredField;
                }
            }
            
            if (!empty($missingFields)) {
                throw new BaseException("Required fields missing", $missingFields, get_class($this));
            }
        }
        
        /**
         * Checks insert or update query input for fields, which do not exist in the table
         * Throws BaseException if such a field is found
         * @param array $input - the input of query
         * @throws BaseException
         */
        private function checkForFieldsThatDoNotExistInTheTable(array $input)
        {
            $unexistingFields = array();
            
            foreach ($input as $field => $value) {
                if (!array_key_exists($field, $this->tableFields->getFields())) {
                    $unexistingFields[] = $field;
                }
            }
            
            if (!empty($unexistingFields)) {
                throw new BaseException("The field/s does not exist in the table", $unexistingFields, get_class($this));
            }
        }
        
        /**
         * Validates an integer field size for an update or insert query
         * Returns the text of the error (or empty string)
         * @param string $fieldName - the id of the field
         * @param int $value - the value of the field
         * @return string
         * 
         * @todo DEXTER
         */
        private function validateIntegerField(string $fieldName, int $value)
        {
            //todo -dexeter
            //https://dev.mysql.com/doc/refman/8.0/en/integer-types.html
            return 'exceeds the maximum number allowed';
        }
        
        /**
         * Validates an varchar field length for an update or insert query
         * Returns the text of the error (or empty string)
         * @param string $fieldName - the id of the field
         * @param int $value - the value of the field
         * @return string
         * 
         * @todo DEXTER
         */
        private function validateVarcharField(string $fieldName, string $value)
        {
            
            return 'exceeds the maximum field length';
        }
        
        private function validateInputValues(array $input)
        {
            $inputErrors = array();
            
            foreach ($input as $fieldName => $value) {
                $fieldType = $this->tableFields->getFieldType($fieldName);
                if ((stristr($fieldType, 'int') || $fieldType === 'double' || $fieldType === 'float')) {
                    if (!is_numeric($value)) {
                        $inputErrors[$fieldName] = "must be a numeric value";
                    } else if ($error = $this->validateIntegerField($fieldName, $value)) {
                        $inputErrors[$fieldName] = $error;
                    }
                } else if ($fieldType === 'date') {
                    $t = explode('-', $value);
                    if (count($t) < 3 || !checkdate($t[1], $t[2], $t[0])) {
                        $inputErrors[$fieldName] = "must be a date with MySQL format";
                    }
                    unset($t);
                } else if ($fieldType === 'varchar' && $error = $this->validateVarcharField($fieldName, $value)) {
                    $inputErrors[$fieldName] = $error;
                }
                unset($fieldType);
            }
            
            if (!empty($inputErrors)) {
                throw new BaseException("The following fields are not valid", $inputErrors, get_class($this));
            }
        }
        
        public function insert(array $input, $autocomplete = null)
        {
            $this->checkTableFields();
            
            $this->checkInputForFieldId($input);
            
            $this->checkForMissingFields($input);
            
            $this->checkForFieldsThatDoNotExistInTheTable($input);
            
            $this->validateInputValues($input);
        }
        
        public function insertTranslation()
        {
            
        }

        public function update(int $ojbectId, array $input)
        {

        }
        
        private function prepareQueryArray($input){
            global $Core;
            if(empty($input) || !is_array($input)){
                throw new Exception($Core->language->error_input_must_be_a_non_empty_array);
            }
            $allowedFields = $this->getTableFields();
            $temp = array();

            $parentFunction = debug_backtrace()[1]['function'];
            if((stristr($parentFunction,'add') || $parentFunction == 'insert')){
                $requiredBuffer = $this->requiredFields;
            }
            else{
                $requiredBuffer = array();
                if(stristr($parentFunction,'translate')){
                    $allowedFields = $this->translationFields;
                }
            }

            foreach ($input as $k => $v){
                if(
                       ($k === 'added' && !$this->allowFieldAdded)
                    || ($k === 'id')
                    || (!isset($allowedFields[$k]) && !in_array($k,$allowedFields))

                ){
                    throw new Exception($Core->language->the_field." `{$k}` ".$Core->language->is_not_allowed);
                }

                if(is_string($v)){
                    $v = trim($v);
                }

                if(!empty($v) || ((is_numeric($v) && intval($v) === 0))){
                    if(!empty($requiredBuffer) && ($key = array_search($k, $requiredBuffer)) !== false) {
                        unset($requiredBuffer[$key]);
                    }
                    $fieldType = $this->tableFields[$k]['type'];
                    if(stristr($fieldType,'int') || stristr($fieldType,'double')){
                        if(!is_numeric($v)){
                            $k = str_ireplace('_id','',$k);
                            throw new Exception ($Core->language->error_field.' `'.$Core->language->$k.'` '.$Core->language->error_must_be_a_numeric_value);
                        }
                        $temp[$k] = $Core->db->escape($v);
                    }
                    elseif($fieldType == 'date'){
                        $t = explode('-',$v);
                        if(count($t) < 3 || !checkdate($t[1],$t[2],$t[0])){
                            $k = str_ireplace('_id','',$k);
                            throw new Exception ($Core->language->error_field.' `'.$Core->language->$k.'` '.$Core->language->error_must_be_a_date_with_format);
                        }
                        $temp[$k] = $Core->db->escape($v);
                        unset($t);
                    }
                    elseif($this->autoFormatTime && in_array($fieldType ,array('timestamp', 'datetime'))){
                        $temp[$k] = $this->toMysqlTimestamp($v);
                    }
                    else{
                        if(in_array($k,$this->explodeFields)){
                            if(is_object($v)){
                                $v = (array)$v;
                            }
                            else if(!is_array($v)){
                                $v = array($v);
                            }

                            if($k == 'languages'){
                                $tt = array();
                                foreach($v as $lang){
                                    if(empty($lang)){
                                        throw new Exception ($Core->language->error_language_cannot_be_empty);
                                    }
                                    $langMap = $Core->language->getLanguageMap(false);

                                    if(!isset($langMap[$lang])){
                                        throw new Exception ($Core->language->error_undefined_or_inactive_language);
                                    }
                                    if(!is_numeric($lang)){
                                        $lang = $langMap[$lang]['id'];
                                    }
                                    $tt[] = $lang;
                                }
                                $v = '|'.implode('|',$tt).'|';
                            }
                            else{
                                $tt = '';
                                foreach($v as $t){
                                    if(!empty($t)){
                                        $tt .= str_replace($this->explodeDelimiter,'_',$t).$this->explodeDelimiter;
                                    }
                                }
                                $v = substr($tt, 0, -strlen($this->explodeDelimiter));
                                unset($tt,$t);
                            }
                        }
                        else if(is_array($v)){
                            $k = str_ireplace('_id','',$k);
                            throw new Exception($Core->language->error_field.' `'.$Core->language->$k.'` '.$Core->language->error_must_be_alphanumeric_string);
                        }
                        $temp[$k] = $Core->db->escape($v);
                    }
                }
                else{
                    if(stristr($parentFunction,'update') && isset($this->requiredFields[$k])){
                        $k = str_ireplace('_id','',$k);
                        throw new Exception($Core->language->error_enter.' `'.$Core->language->$k.'`');
                    }
                    else $temp[$k] = '';
                }
            }

            if(!empty($requiredBuffer)){
                $temp = array();
                foreach($requiredBuffer as $r){
                    $r = str_ireplace('_id','',$r);
                    $temp[] = mb_strtolower($Core->language->$r);
                }

                throw new Exception($Core->language->error_enter." ".implode(', ',$temp));
            }

            return $temp;
        }

    }
































