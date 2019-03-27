<?php
    final class BaseSelect extends BaseQuery
    {
        use BaseWhere;
        use BaseOrderBy;
        use BaseLimit;

        const DEFAULT_SELECTED_FIELDS = '*';

        /**
         * @var array
         * A collection of fields for the query
         */
        private $fields;

        /**
         * @var string
         * The name for the GlobalTemplates function, used to get the data from the query
         */
        private $globalTemplate = 'simpleArray';

        /**
         * @var int
         * Allows the user to set a cache time for all the select queries
         */
        private $cacheTime = 0;

        /**
         * Creates a new instance of the BaseSelect class
         * @param string $tableName - the name for the table
         */
        public function __construct(string $tableName)
        {
            parent::__construct($tableName);
        }

        /**
         * It will reset the cache for all select queries from now on
         */
        public function resetCache()
        {
            $this->cacheTime = -1;
        }

        /**
         * It will turn off the cache for all select queries from now on
         */
        public function turnOffCache()
        {
            $this->cacheTime = 0;
        }

        /**
         * It will turn on the cache for all select queries from now on
         * It will throw Exception if the provided parameter is <= 0
         * @param int $cacheTime - allows to set the specific cache time (in minutes)
         * @throws Exception
         */
        public function turnOnCache(int $cacheTime)
        {
            if ($cacheTime <= 0) {
                throw new Exception("Query cache time should be bigger than 0");
            }

            $this->cacheTime = $cacheTime;
        }

        /**
         * Sets the cache time to the provided value
         *  @param int $cacheTime - allows to set the specific cache time (in minutes)
         */
        public function setCacheTime(int $cacheTime)
        {
            $this->cacheTime = $cacheTime;
        }

        /**
         * Set the name for the GlobalTemplates function, used to get the data from the query
         * @param string $globalTemplate - the name of the template
         */
        public function setGlobalTemplate(string $globalTemplate)
        {
            if (!method_exists('GlobalTemplates', $globalTemplate)) {
                throw new Exception("{$globalTemplate} is not a name of a global template!");
            }
            $this->globalTemplate = $globalTemplate;
        }

        /**
         * Adds a field to the query
         * @param string $field - the name of the field
         * @param string $name - the "AS" name for the field
         */
        public function addField(string $field, string $name = null)
        {
            global $Core;

            $field = $Core->db->escape($field);
            $name = $Core->db->escape($name);

            if (!empty($name)) {
                $this->fields[] = "{$field} AS '{$name}'";
            } else {
                $this->fields[] = $field;
            }
        }

        /**
         * Builds the query from all of it's parts
         * @return string
         */
        public function build()
        {
            global $Core;

            $this->query = 'SELECT ';
            $this->query .= empty($this->fields) ? self::DEFAULT_SELECTED_FIELDS : implode(',', $this->fields);
            $this->query .= ' FROM ';

            $this->query .= "`".(empty($this->dbName) ? $Core->dbName : $this->dbName)."`.`{$this->tableName}`";

            $this->addWhereToQuery();
            $this->addOrderByToQuery();
            $this->addLimitToQuery();

            return $this->query;
        }

        /**
         * Executes a select query, using the query body, the cacheTime property and globalTemplate property
         * @return array
         */
        public function execute()
        {
            global $Core;

            $Core->db->query($this->build(), $this->cacheTime, $this->globalTemplate, $result);

            return !empty($result) ? $result : array();
        }
    }
