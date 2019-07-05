<?php
class BaseTableFields
{
    /**
     * The name of the table
     * @var string
     */
    protected $tableName;
    
    /**
     * Contains all the table fields
     * @var array
     */
     protected $tableFields;
    
    /**
     * Contains all the required table fields
     * @var array
     */
    protected $requiredFields;
    
    /**
     * How long should the table information be cached
     * @var int
     */
    protected $cacheTime = 60;

    /**
     * Creates a new intance of BaseTableFields
     * @param string $tableName - the name of the bale
     * @param int $cacheTime - how much time should the cache of the table info persist
     */
    public function __construct(string $tableName, int $cacheTime = null)
    {
        global $Core;

        $this->tableName = $tableName;
        $this->getTableInfo($Core->clientIsDeveloper());
        
        if ($cacheTime !== null) {
            $this->cacheTime = $cacheTime;
        }
    }
    
    /**
     * Get a list of the table fields and their description
     * @return array
     */
    public function getFields()
    {
        if (empty($this->tableFields)) {
            $this->getTableInfo();
        }

        return $this->tableFields;
    }

    /**
     * Get a list of the required table fields and their description
     * @return array
     */
    public function getRequiredFields()
    {
        if (empty($this->tableFields)) {
            $this->getTableInfo();
        }

        return $this->requiredFields;
    }
    
    /**
     * Gets the type of the provided field
     * @param string $fieldName - the name of the field
     * @return string
     */
    public function getFieldType(string $fieldName)
    {
        if (!isset($this->tableFields[$fieldName])) {
            throw new Error("The field `$fieldName` does not exist in table `{$this->tableName}`");
        }

        return $this->tableFields[$fieldName]['type'];
    }
    
    /**
     * Gets the info array of the provided field
     * @param string $fieldName - the name of the field
     * @return array
     */
    public function getFieldInfo(string $fieldName)
    {
        if (!isset($this->tableFields[$fieldName])) {
            throw new Error("The field `$fieldName` does not exist in table `{$this->tableName}`");
        }

        return $this->tableFields[$fieldName]['field_info'];
    }
    
    /**
     * Gets information for the table fields of the instance.
     * Fills the tableFields and requiredFields arrays.
     * @param bool $noCache - ignore the cache of the instance or not
     */
    private function getTableInfo(bool $noCache = null)
    {
        global $Core;

        $Core->db->query(
            "SELECT
                COLUMN_NAME AS 'column_temp_id',
                DATA_TYPE AS 'type',
                TRIM(REPLACE(REPLACE(REPLACE(COLUMN_TYPE, DATA_TYPE,''),')',''),'(','')) AS 'field_info',
                IS_NULLABLE AS 'allow_null',
                COLUMN_DEFAULT AS 'default'
            FROM
                INFORMATION_SCHEMA.COLUMNS
            WHERE
                `table_schema` = '{$Core->dbName}'
            AND
                `table_name` = '{$this->tableName}'"
            ,$noCache ? 0 : $this->cacheTime, 'fillArray', $columnsInfo, 'column_temp_id'
        );

        if (empty($columnsInfo)) {
            throw new Error(get_class($this).": "."Table `{$this->tableName}` does not exist!");
        }

        foreach ($columnsInfo as $k => $v) {
            if ($v["allow_null"] == "NO" && $v['type'] != 'timestamp' && $v['default'] != 'CURRENT_TIMESTAMP') {
                $this->requiredFields[$v['column_temp_id']] = $v['column_temp_id'];
                $v["allow_null"] = false;
            } else {
                $v["allow_null"] = true;
            }

            unset($v['column_temp_id']);
            $this->tableFields[$k] = $v;
        }

        unset($columnsInfo, $this->requiredFields['id']);
    }
}
