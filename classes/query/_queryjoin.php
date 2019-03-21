<?php
    trait QueryJoin
    {
        public function addLeftJoin(string $tableName, string $firstTableField, string $joinedTableField)
        {
            $this->queryBody[] = "LEFT JOIN {$tableName} ON `{$this->tableName}`.`{$firstTableField}` = `{$tableName}`.`{$joinedTableField}`";
        }
        
        public function addInnerJoin(string $tableName, string $firstTableField, string $joinedTableField)
        {
            $this->queryBody[] = "INNER JOIN {$tableName} ON `{$this->tableName}`.`{$firstTableField}` = `{$tableName}`.`{$joinedTableField}`";
        }
        
        public function addRightJoin(string $tableName, string $firstTableField, string $joinedTableField)
        {
            $this->queryBody[] = "RIGHT JOIN {$tableName} ON `{$this->tableName}`.`{$firstTableField}` = `{$tableName}`.`{$joinedTableField}`";
        }
    }
