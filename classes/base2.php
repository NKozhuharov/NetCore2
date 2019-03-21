<?php
    class Base2
    {
        const ORDER_ASC = 'ASC';
        const ORDER_DESC = 'DESC';
        
        protected $tableName;
        protected $tableFields;
        
        protected $orderByField = 'id';
        protected $orderByType = 'ASC';
        
        protected $returnTimestamps = false;
        
        protected $hiddenFieldName = 'hidden';
        protected $showHiddenRows  = false;
        
        protected $translationFields     = array();     //fields in the table, which has translations in the {table}_lang
        protected $explodeFields         = array();     //fields in the table which are separated
        protected $explodeDelimiter      = '|';         //the separator for the separated fields in the table

        
        private function checkTableFields()
        {
            if (empty($this->tableName)) {
                throw new Exception("Provide a table name for the model!");
            }
            
            if (empty($this->tableFields)) {
                $this->tableFields = new TableFields($this->tableName);
            }
        }
        
        //retuns the current TableName
        public function getTableName()
        {
            return $this->tableName;
        }
        
        //changes the current TableName. Use with caution!!!
        public function changeTableName(string $name)
        {
            if (empty($name)) {
                throw new Exception("Error in ".get_class($this).": Empty table name!");
            }
            $this->tableName = $name;
            $this->tableFields = new TableFields($this->tableName);

            return true;
        }
        
        public function getOrderByField()
        {
            return $this->orderByField;
        }
        
        public function getOrderByType()
        {
            return $this->orderByType;
        }
        
        public function changeOrderByField(string $field)
        {
            if (!array_key_exists($field, $this->tableFields->getFields())) {
                throw new Exception("This field does not exist!");
            }
            $this->orderByField = $field; 
        }
        
        public function changeOrderByType(string $type)
        {
            if ($type != self::ORDER_ASC && $type != self::ORDER_DESC) {
                throw new Exception("Order by must be ASC or DESC");
            }
            
            $this->orderByType = $type;
        }
        
        public function getTranslation(array $result, $language = null)
        {
            if(!empty($this->translationFields) && ($Core->language->useTranslation() || (!empty($language) && $language != $Core->language->getDefaultLanguage('id')))){
                if(empty($language)){
                    $language = $Core->language->currentLanguageId;
                }

                $Core->db->query("SELECT * FROM `{$Core->dbName}`.`{$this->tableName}_lang` WHERE `object_id` IN (".(implode(', ', array_keys($result))).") AND `lang_id`={$language}", $Core->cacheTime, 'fillArray', $translations, 'object_id');
                if(!empty($translations)){
                    foreach($translations as $k => $v){
                        if(isset($result[$k])){
                            foreach($this->translationFields as $field){
                                $result[$k][$field] = !empty($v[$field]) ? $v[$field] : $result[$k][$field];
                            }
                        }
                    }
                }
            }
            
            return $result;
        }
        
        private function addTimestampFieldsToGetQuery(BaseSelect $selector)
        {
            foreach ($this->tableFields->getFields() as $field => $fieldDescription) {
                if (in_array($fieldDescription['type'] ,array('timestamp', 'datetime'))) {
                    $selector->addField("UNIX_TIMESTAMP(`{$field}`)", "{$field}_timestamp");
                }
            }
            
            return $selector;
        }
        
        private function getGetQueryWherePart(string $additional = null)
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
        
        
        
        public function getAll(int $limit = null, string $orderBy = null, string $additional = null)
        {
            global $Core;
            
            $this->checkTableFields();
            
            $selector = new BaseSelect;
            
            $selector->tableName($this->tableName);
            
            $selector->addField('*');
            
            if ($this->returnTimestamps === true) {
                $selector = $this->addTimestampFieldsToGetQuery($selector);
            }
            
            $selector->where($this->getGetQueryWherePart($additional));

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
            
            $Core->db->query($selector->buildQuery(), 0, 'simpleArray', $result);
            
            $result = $this->parseExplodeFields($result);
            
            var_dump($selector->buildQuery());
            $Core->dump($result);
        }
        
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    