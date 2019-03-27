<?php
//TO DO
//templates
//auto format date time
//to do: fix error count fields

    class Base2
    {
        use InputValidations;

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
        protected $queryCacheTime = 0;

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
        protected $explodeDelimiter = '~';

        /**
         * @var array
         * Fields in the table, which has translations in the {table}_lang
         */
        protected $translationFields = array();

        /**
         * @var bool
         * If is set to true the results from the outupt functions will be translated if they have translation table
         */
        protected $translateResult = true;

        /**
         * Ensures that tableFields variable is initialized; if not it will initialize it
         * Throws Exception if the table name is not provided
         * @throws Exception
         */
        private function checkTableFields()
        {
            if (empty($this->tableName)) {
                throw new Exception("Provide a table name for model `".get_class($this)."`");
            }

            if (empty($this->tableFields)) {
                $this->tableFields = new BaseTableFields($this->tableName);
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
                throw new Exception("Empty table name in model `".get_class($this)."`");
            }

            $this->tableName = $name;
            $this->tableFields = new BaseTableFields($this->tableName);
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
                throw new Exception("The field `$field` does not exist in table `{$this->tableName}`");
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
                throw new Exception("Query cache time should be bigger than 0 in model `".get_class($this)."`");
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
            return $this->translateResult !== false && !empty($this->translationFields) ;
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
                    $this->getTranslation($result, $Core->Language->getCurrentLanguageId())
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

                            foreach (explode($this->explodeDelimiter, $resultRow[$field]) as $key => $value) {
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
            $this->validateParentField($parentId);

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
                throw new Exception("Id should be bigger than 0 in model `".get_class($this)."`");
            }

            $this->checkTableFields();

            if (!array_key_exists('id', $this->tableFields->getFields())) {
                throw new Exception("The field `id` does not exist in table `{$this->tableName}`");
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
            $this->validateParentField($parentId);

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

            if (isset($result['id'])) {
                $result = array($result);
            }

            $resultObjectIds = array_column($result, 'id');

            if (empty($resultObjectIds)) {
                throw new Exception ("Cannot translate query results, which do not contain the id column");
            }

            $selector = new BaseSelect("{$this->tableName}_lang");
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
        public function delete(string $additional = null)
        {
            if (empty($additional)) {
                throw new Exception("A delete query without where parameter is not allowed. Use deleteAll instead. Model: `".get_class($this)."`");
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
                throw new Exception("Object id must be bigger than 0 in model `".get_class($this)."`");
            }

            $deleter = new BaseDelete($this->tableName);
            $deleter->setWhere("`id` = {$rowId}");

            return $deleter->execute();
        }

        /**
         * Deletes all rows in the table of the model that match the provided parent id
         * Returns the number of deleted rows
         * It will throw Exception if a parentField is not set for the model, the provided parent id is empty or invalid
         * @param int $id - the parent id of the rows
         * @throws Exception
         * @return int
         */
        public function deleteByParentId(int $parentId)
        {
            $this->validateParentField($parentId);

            $deleter = new BaseDelete($this->tableName);
            $deleter->setWhere("`{$this->parentField}` = {$parentId}");

            return $deleter->execute();
        }

        /**
         * Inserts a rows into the model table
         * Returns the id of the inserted row
         * @param array $input - it must contain the field names => values
         * @return int
         */
        public function insert(array $input)
        {
            $inserter = new BaseInsert($this->tableName);
            $inserter->setFieldsAndValues($this->validateAndPrepareInputArray($input));
            $inserter->setUpdateOnDuplicate(true);

            return $inserter->execute();
        }

        /**
         * Updates a rows into the model table by the provided where override
         * Returns the number of affected rows
         * It will throw Exception if a where override is not provided
         * It will throw Exception if there is a problem with the provided input
         * @param int $objectId - the id of the row to be updated
         * @param array $input - it must contain the field names => values
         * @param string $additional - the where clause override
         * @throws Exception
         * @return int
         */
        public function update(array $input, string $additional = null)
        {
            if (empty($additional)) {
                throw new Exception("An update query without where parameter is not allowed. Use updateAll instead. Model: `".get_class($this)."`");
            }

            $updater = new BaseUpdate($this->tableName);
            $updater->setFieldsAndValues($this->validateAndPrepareInputArray($input, true));
            $updater->setWhere($additional);

            return $updater->execute();
        }

        /**
         * Updates a rows into the model table by it's id
         * Returns the number of affected rows
         * It will throw Exception if the provided objectId is empty
         * It will throw Exception if there is a problem with the provided input
         * @param int $objectId - the id of the row to be updated
         * @param array $input - it must contain the field names => values
         * @param string $additional - the where clause override
         * @throws Exception
         * @return int
         */
        public function updateById(int $ojbectId, array $input, string $additional = null)
        {
            if ($ojbectId <= 0) {
                throw new Exception("Object id must be bigger than 0 in model `".get_class($this)."`");
            }

            $updater = new BaseUpdate($this->tableName);
            $updater->setFieldsAndValues($this->validateAndPrepareInputArray($input, true));
            $updater->setWhere(" `id` = {$ojbectId} ".(!empty($additional) ? " AND {$additional}" : ""));

            return $updater->execute();
        }

        /**
         * Updates a rows into the model table by it's id
         * Returns the number of affected rows
         * It will throw Exception if a parentField is not set for the model, the provided parent id is empty or invalid
         * It will throw Exception if there is a problem with the provided input
         * @param int $parentId - the id of the parent field
         * @param array $input - it must contain the field names => values
         * @param string $additional - the where clause override
         * @throws Exception
         * @return int
         */
        public function updateByParentId(int $parentId, array $input, string $additional = null)
        {
            $this->validateParentField($parentId);

            $updater = new BaseUpdate($this->tableName);
            $updater->setFieldsAndValues($this->validateAndPrepareInputArray($input, true));
            $updater->setWhere(" `{$this->parentField}` = {$parentId} ".(!empty($additional) ? " AND {$additional}" : ""));
            
            return $updater->execute();
        }

        /**
         * Updates all fields in the table with the provided input
         * It will throw Exception if there is a problem with the provided input
         * @param string $additional - the where clause override
         * @throws Exception
         * @return int
         */
        public function updateAll(array $input)
        {
            $updater = new BaseUpdate($this->tableName);
            $updater->setFieldsAndValues($this->validateAndPrepareInputArray($input, true));

            return $updater->execute();
        }

        /**
         * Inserts or updates a translation for the provided object
         * Returns the number of affected rows or the id of the new row
         * @param int $objectId - the id of the row where the translated object is
         * @param int $languageId - the language id to translate to
         * @param array $input - the translated object data
         * @return int
         */
        public function translate(int $objectId, int $languageId, array $input)
        {
            global $Core;

            if (empty($this->translationFields)) {
                throw new Exception("You cannot translate an object, without any translation fields!");
            }

            if ($objectId <= 0) {
                throw new Exception("Object id should be bigger than 0 in model `".get_class($this)."`");
            }

            if ($languageId <= 0) {
                throw new Exception("Language id should be bigger than 0 in model `".get_class($this)."`");
            }

            if (!array_key_exists($languageId, $Core->Language->getActiveLanguages())) {
                throw new Exception("Language id should be the id of an active language in model `".get_class($this)."`");
            }

            $currentTranslation = $this->translateResult;
            $this->translateResult = false;

            $objectInfo = $this->getById($objectId);

            $this->translateResult = $currentTranslation;

            if (empty($objectInfo)) {
                throw new Exception("The object you are trying to translate does not exist in model `".get_class($this)."`");
            }
            
            $this->validateTranslateExplodeFields($input, $objectInfo);

            //temp variable to store the current info for the validations
            $currentTableFields = $this->tableFields;

            try {
                $this->tableFields = new BaseTableFields("{$this->tableName}_lang");
            } catch (Exception $ex) {
                throw new Exception ("Table {$this->tableName}_lang does not exist in model `".get_class($this)."`");
            }

            $input['object_id'] = $objectId;
            $input['lang_id'] = $languageId;

            $translator = new BaseInsert("{$this->tableName}_lang");
            $translator->setFieldsAndValues($this->validateAndPrepareInputArray($input));
            $translator->setUpdateOnDuplicate(true);

            $this->tableFields = $currentTableFields;
            unset($currentTableFields);

            return $translator->execute();
        }
    }
