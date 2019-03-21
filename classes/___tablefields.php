<?php
    class TableFields
    {
        const CACHE_TIME = 60;
        
        protected $tableName;
        protected $tableFields;
        protected $requiredFields;
        
        public function __construct(string $tableName)
        {
            global $Core;
            
            $this->tableName = $tableName;
            $this->getTableInfo($Core->clientIsDeveoper());
        }
        
        //get a list of the table fields
        public function getFields(){
            if(empty($this->tableFields))
            {
                $this->getTableInfo();
            }

            return $this->tableFields;
        }

        //get a list of the required fields
        public function getRequiredFields()
        {
            if(empty($this->tableFields)){
                $this->getTableInfo();
            }

            return $this->requiredFields;
        }
        
        private function getTableInfo(bool $noCache = null)
        {
            global $Core;

            $Core->db->query(
                "SELECT 
                    COLUMN_NAME AS 'column_temp_id', 
                    DATA_TYPE AS 'type', 
                    IS_NULLABLE AS 'allow_null', 
                    COLUMN_DEFAULT AS 'default'
                FROM 
                    INFORMATION_SCHEMA.COLUMNS
                WHERE 
                    `table_schema` = '{$Core->dbName}' 
                AND 
                    `table_name` = '{$this->tableName}'"
                ,$noCache ? 0 : self::CACHE_TIME, 'fillArray', $columnsInfo, 'column_temp_id'
            );

            if (empty($columnsInfo)) {
                throw new Exception(get_class($this).": "."Table `{$this->tableName}` does not exist!");
            }

            foreach ($columnsInfo as $k => $v) {
                #if (in_array($k, $this->removedFields)) {
                #    continue;
                #}
                if($v["allow_null"] == "NO" && $v['type'] != 'timestamp' && $v['default'] != 'CURRENT_TIMESTAMP'){
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