<?php
    class BaseJoinData
    {
        /**
         * @var string
         * Join type
         */
        public $type;

        /**
         * The name of the table to join
         */
        public $tableToJoin;

        /**
         * The name of the field from the table to join
         */
        public $tableField;

        /**
         * The name of the field from the parent table to join to
         */
        public $parentField;

        /**
         * The name of the parent table to join to
         */
        public $parentTable;

        /**
         * Creates a new instance of the BaseJoinData class
         * @param string $type - the join type
         * @param string $tableToJoin - the name of the table to join
         * @param string $tableField - the name of the field from the table to join
         * @param string $parentField - the name of the field from the parent table to join to
         * @param string $parentTable - the name of the parent table to join to
         */
        public function __construct(string $type, string $tableToJoin, string $tableField, string $parentField, string $parentTable)
        {
            if (empty($tableToJoin)) {
                throw new Exception("Provide the name of the table to join");
            }

            if (empty($tableField)) {
                throw new Expceiotn("Provide the names of the field from the joining table");
            }

            if (empty($parentField)) {
                throw new Expceiotn("Provide the names of the field from the parent table");
            }

            if (empty($parentTable)) {
                throw new Exception("Provide the name of the parent table to join");
            }

            $this->type = $type;
            $this->tableToJoin = $tableToJoin;
            $this->tableField  = $tableField;
            $this->parentField = $parentField;
            $this->parentTable = $parentTable;
        }
    }

    trait BaseJoin
    {
        /**
         * @var array
         * The joins to the table
         */
        private $joins;

        /**
         * Adds a INNER JOIN to the query collection of joins
         * @param string $tableToJoin - the name of the table to join
         * @param string $tableField  - the name of the field from the table to join
         * @param string $parentField - the name of the field from the parent table to join to
         * @param string $parentTable - the name of the parent table to join to (optional)
         */
        public function addInnerJoin(string $tableToJoin, string $tableField, string $parentField, string $parentTable = null)
        {
            if (empty($parentTable)) {
                $parentTable = $this->tableName;
            }

            $this->joins[] = new BaseJoinData("INNER JOIN", $tableToJoin, $tableField, $parentField, $parentTable);
        }

        /**
         * Adds a OUTER JOIN to the query collection of joins
         * @param string $tableToJoin - the name of the table to join
         * @param string $tableField  - the name of the field from the table to join
         * @param string $parentField - the name of the field from the parent table to join to
         * @param string $parentTable - the name of the parent table to join to (optional)
         */
        public function addOuterJoin(string $tableToJoin, string $tableField, string $parentField, string $parentTable = null)
        {
            if (empty($parentTable)) {
                $parentTable = $this->tableName;
            }

            $this->joins[] = new BaseJoinData("OUTER JOIN", $tableToJoin, $tableField, $parentField, $parentTable);
        }

        /**
         * Adds a LEFT JOIN to the query collection of joins
         * @param string $tableToJoin - the name of the table to join
         * @param string $tableField  - the name of the field from the table to join
         * @param string $parentField - the name of the field from the parent table to join to
         * @param string $parentTable - the name of the parent table to join to (optional)
         */
        public function addLeftJoin(string $tableToJoin, string $tableField, string $parentField, string $parentTable = null)
        {
            if (empty($parentTable)) {
                $parentTable = $this->tableName;
            }

            $this->joins[] = new BaseJoinData("LEFT JOIN", $tableToJoin, $tableField, $parentField, $parentTable);
        }

        /**
         * Adds a RIGHT JOIN to the query collection of joins
         * @param string $tableToJoin - the name of the table to join
         * @param string $tableField  - the name of the field from the table to join
         * @param string $parentField - the name of the field from the parent table to join to
         * @param string $parentTable - the name of the parent table to join to (optional)
         */
        public function addRightJoin(string $tableToJoin, string $tableField, string $parentField, string $parentTable = null)
        {
            if (empty($parentTable)) {
                $parentTable = $this->tableName;
            }

            $this->joins[] = new BaseJoinData("RIGHT JOIN", $tableToJoin, $tableField, $parentField, $parentTable);
        }

        /**
         * Adds all joins to the query
         */
        private function addJoinsToQuery()
        {
            if (!empty($this->joins)) {
                foreach ($this->joins as $join) {
                    $this->query .= " {$join->type} `{$this->dbName}`.`{$join->tableToJoin}`";
                    $this->query .= " ON `{$this->dbName}`.`{$join->tableToJoin}`.`{$join->tableField}` =";
                    $this->query .= " `{$this->dbName}`.`{$join->parentTable}`.`{$join->parentField}`";
                }
            }
        }
    }
