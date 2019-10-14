<?php
final class BaseSelect extends BaseQuery
{
    use BaseWhere;
    use BaseOrderBy;
    use BaseLimit;
    use BaseJoin;

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
     * It will throw Error if the provided parameter is <= 0
     * @param int $cacheTime - allows to set the specific cache time (in minutes)
     * @throws Error
     */
    public function turnOnCache(int $cacheTime)
    {
        if ($cacheTime <= 0) {
            throw new Error("Query cache time should be bigger than 0");
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
            throw new Error("'{$globalTemplate}' is not a name of a global template");
        }
        $this->globalTemplate = $globalTemplate;
    }

    /**
     * Removes a field from the current field list
     * If a function result is provided to the field (COUNT(*)), table name must be put manually in the function
     * @param string $field - the name of the field
     * @param string $tableName - the name of the table, from which to select the field
     */
    public function removeField(string $field, string $tableName = null)
    {
        global $Core;

        $field = $Core->db->escape($field);

        if (empty($tableName)) {
            $tableName = $this->tableName;
        }

        if (strstr($field, '(') && strstr($field, ')')) {
            $tableName = '';
        } else {
            $tableName = "`$tableName`.";
        }

        unset($this->fields["{$tableName}{$field}"]);
    }

    /**
     * Adds a field to the query
     * If a function result is provided to the field (COUNT(*)), table name must be put manually in the function
     * @param string $field - the name of the field
     * @param string $alias - the "AS" alias for the field
     * @param string $tableName - the name of the table, from which to select the field
     */
    public function addField(string $field, string $alias = null, string $tableName = null)
    {
        global $Core;

        if (empty($tableName)) {
            $tableName = $this->tableName;
        }

        if (strstr($field, '(') && strstr($field, ')')) {
            $tableName = '';
        } else {
            $tableName = "`$tableName`.";
        }

        if (!empty($alias)) {
            $this->fields["{$tableName}{$field}"] = "{$tableName}{$field} AS '{$alias}'";
        } else {
            $this->fields["{$tableName}{$field}"] = "{$tableName}{$field}";
        }
    }

    /**
     * Allows to add multiple fields, without aliases
     * The array keys of the fields parameter are ignored
     * @param array $fields - the names of the fields to add
     * @param string $tableName - the name of the table, from which to select the fields
     */
    public function addFields(array $fields, string $tableName = null)
    {
        if (!empty($fields)) {
            foreach ($fields as $field) {
                $this->addField($field, null, $tableName);
            }
        }
    }

    /**
     * Allows to add multiple fields, with aliases
     * The array keys of the fields parameter are the fields, the values are the aliases
     * @param array $fields - the names and aliases of the fields to add
     * @param string $tableName - the name of the table, from which to select the fields
     */
    public function addFieldsWithAliasess(array $fields, string $tableName = null)
    {
        if (!empty($fields)) {
            foreach ($fields as $field => $alias) {
                $this->addField($field, $alias, $tableName);
            }
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
        $this->query .= empty($this->fields) ? self::DEFAULT_SELECTED_FIELDS : implode(', ', $this->fields);
        $this->query .= ' FROM ';

        $this->query .= "`".(empty($this->dbName) ? $Core->dbName : $this->dbName)."`.`{$this->tableName}`";

        $this->addJoinsToQuery();

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
