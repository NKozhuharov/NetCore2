<?php
    class Base{
        public    $orderByField          = 'id';        //default order by field is 'id'
        public    $orderType             = 'ASC';       //default order type is asc
        public    $additionalOrdering    = false;       //more ordering
        public    $returnTimestamps      = false;       //use this if you need your GetWhatever function to return you a UNIX_TIMESTAMP of the timestamp fields as well
        public    $allowFieldAdded       = false;       //allow table field `added` to be changed
        public    $autoFormatTime        = false;       //'%d-%m-%Y %H:%i' format to return datetime and timestap fields; when this is set, also the inputs are auto convertet to mysql format for insert and update

        protected $tableName             = false;       //the name of the table of the parent class
        protected $parentField           = false;       //if the parent class has a table referencing it, this field is used for getByParentId()
        protected $autocompleteField     = false;       //if you use autocomplete table, this will be the field to put in it
        protected $autocompleteObjectId  = false;       //if you use autocomplete table, enter the object id here
        protected $autocompleteSeparator = ',';         //use this to build your autocomplete index fields if more than one fields is used in autocomplete
        protected $searchByField         = false;       //if there is no need for autocomplete table, the search function will search according to this field
        protected $returnPhraseOnly      = true;        //if this is set to true, search function will return only the id and the phrase of the result; otherwise it will return the whole row
        protected $showHiddenRows        = false;       //if set to true, search and getAll functios will not consider the 'hidden' columns in the db

        protected $translationFields     = array();     //fields in the table, which has translations in the {table}_lang
        protected $explodeFields         = array();     //fields in the table which are separated
        protected $explodeDelimiter      = '|';         //the separator for the separated fields in the table

        protected $tableFields           = array();     //this is filled from get getTableInfo(); contains info for all the fields in the table
        protected $requiredFields        = array();     //this is filled from get getTableInfo(); contains the required fields in the table
        protected $removedFields         = array();     //remove unwanted fields from the queries

        public function __construct(){}

        public function getSearchQuery(){
            global $Core;

            if(isset($_REQUEST) && $_REQUEST){
                if(empty($this->tableFields)){
                    $this->getTableInfo();
                }

                $fields = array();

                foreach($this->tableFields as $f){
                    $fields[] = $f;
                }

                $query = array();
                foreach($_REQUEST as $k => $v){
                    if(array_key_exists(strtolower($k), $this->tableFields) && (is_string($v) || is_numeric($v)) && trim($v) !== ''){
                        $query[] = " `$k` = '{$Core->db->escape($v)}' ";
                    }
                }
                unset($k, $v);

                if($query){
                    natcasesort($query);
                    $query = implode(' AND ', $query);
                }
                return $query;
            }
            return false;
        }

        //this function fills the $tableFields and $requiredFields
        public function getTableInfo($noCache = false){
            global $Core;

            $Core->db->query(
                "SELECT COLUMN_NAME AS 'column_temp_id', DATA_TYPE AS 'type', IS_NULLABLE AS 'allow_null', COLUMN_DEFAULT AS 'default'
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE `table_schema` = '{$Core->dbName}' AND `table_name` = '{$this->tableName}'"
                ,$noCache ? 0 : $Core->cacheTime,'fillArray',$columnsInfo, 'column_temp_id'
            );

            if(empty($columnsInfo)){
                throw new Exception(get_class($this).": "."Table `{$this->tableName}` does not exist!");
            }

            foreach($columnsInfo as $k => $v){
                if(in_array($k, $this->removedFields)){
                    continue;
                }
                if($v["allow_null"] == "NO" && $v['type'] != 'timestamp' && $v['default'] != 'CURRENT_TIMESTAMP'){
                    $this->requiredFields[$v['column_temp_id']] = $v['column_temp_id'];
                    $v["allow_null"] = false;
                }else{
                    $v["allow_null"] = true;
                }
                unset($v['column_temp_id']);
                $this->tableFields[$k] = $v;
            }
            unset($columnsInfo, $this->requiredFields['id']);

            return true;
        }

        //get a list of the table fields
        public function getTableFields(){
            if(empty($this->tableFields)){
                $this->getTableInfo();
            }

            return $this->tableFields;
        }

        //get a list of the required fields
        public function getRequiredFields(){
            if(empty($this->tableFields)){
                $this->getTableInfo();
            }

            return $this->requiredFields;
        }

        //get a list of the translations fileds
        public function getTranslationFields(){
            return $this->translationFields;
        }

        //retuns the current TableName
        public function getTableName(){
            return $this->tableName;
        }

        //changes the current TableName. Use with caution!!!
        public function changeTableName($name){
            if(empty($name)){
                throw new Exception(get_class($this).": Empty table name!");
            }
            $this->tableName = $name;
            $this->tableFields = array();
            $this->requiredFields = array();
            $this->getTableInfo(true);

            return true;
        }

        public function changeParentField($name){
            if(empty($name)){
                throw new Exception(get_class($this).": Empty parent field name!");
            }

            if(!isset($this->tableFields[$name])){
                throw new Exception(get_class($this).": The field `{$name}` does not exist in table `{$this->tableName}`!");
            }
            $this->parentField = $name;

            return true;
        }

        //check if a numeric or string language is valid; returns the numeric value of the language
        public function checkLanguage($language){
            global $Core;

            if($language === false){
                return false;
            }

            if(empty($language)){
                throw new Exception($Core->language->error_please_define_a_language);
            }

            if(!is_string($language) && !is_numeric($language)){
                throw new Exception($Core->language->error_language_must_be_a_string_or_numeric);
            }

            if(!isset($Core->language->langMap[$language])){
                throw new Exception($Core->language->error_undefined_or_inactive_language);
            }

            if(!is_numeric($language)){
                $language = $Core->language->langMap[$language]['id'];
            }

            return $language;
        }

        //converts string or timestamp into mysql timestamp
        public function toMysqlTimestamp($time){
            global $Core;

            if(!$time){
                return false;
            }

            if(!is_string($time)){
                throw new Exception($Core->language->error_please_provide_valid_time);
            }

            if(is_numeric($time)){
                $returnTime = date("Y-m-d H:i:s", $time);
            }else{
                $returnTime = date("Y-m-d H:i:s", strtotime($time));
            }

            if($returnTime === '1970-01-01 01:00:00' && $time !== '1970-01-01 01:00:00'){
                throw new Exception($Core->language->error_please_provide_valid_time);
            }

            return $returnTime;
        }

        //OUTPUT FUNCTIONS
        //gets the parent id of the row in the table
        public function getParentId($objectId){
            global $Core;

            if(!is_numeric($objectId)){
                throw new Exception($Core->language->error_object_id_must_be_numeric);
            }
            $objectId = intval($objectId);
            if(empty($objectId)){
                throw new Exception($Core->language->error_object_id_cannot_be_empty);
            }

            if(empty($this->parentField)){
                throw new Exception(get_class($this).": ".$Core->language->error_this_class_does_not_have_a_parent_id);
            }

            return $Core->db->result("SELECT `{$this->parentField}` FROM `{$Core->dbName}`.`{$this->tableName}` WHERE `id` = $objectId");
        }

        //returns the count of the results in the search function
        public function getCount($phrase = false, $additional = false){
            global $Core;

            if(!empty($phrase)){
                $res = $this->search($phrase, false, $additional);
                return is_array($res) ? count($res) : 0;
            }else{
                $Core->db->query("SELECT COUNT(*) AS 'ct' FROM `{$Core->dbName}`.`{$this->tableName}`".($additional ? " WHERE $additional" : ''), $Core->cacheTime, 'fetch_assoc', $ct);
                return $ct['ct'];
            }
        }

        //if autocompletefields is defined - gets the search results from the autocomplete table if phrase is provided; if not gets the results from the getAll() function
        //limit parameter is accepted, default is from Core class; if you want specific limit, put it as second parameter
        //this function returns ONLY the ids of the rows
        //also checks for hidden rows, which should not be shown in the site. make sure to set the $noUser parameter to false!
        //additional parameter is added to the query usign AND
        public function search($phrase='', $limit = true, $additional = false){
            global $Core;

            if(empty($this->tableFields)){
                $this->getTableFields();
            }

            $phrase = trim($Core->db->escape($phrase));
            if(!empty($phrase)){
                if($this->autocompleteField && $this->autocompleteObjectId){
                    $q = "SELECT `autocomplete`.`object_id`, TRIM(`autocomplete`.`phrase`) AS 'phrase'";
                    $q .= " FROM `{$Core->dbName}`.`autocomplete`";

                    if(isset($this->tableFields['hidden']) && !$this->showHiddenRows){
                        $q .= " INNER JOIN `{$Core->dbName}`.`{$this->tableName}` ON `{$Core->dbName}`.`autocomplete`.`object_id` = `{$Core->dbName}`.`{$this->tableName}`.`id`";
                    }

                    $q .= " WHERE `autocomplete`.`type`={$this->autocompleteObjectId} AND `autocomplete`.`phrase` LIKE '%$phrase%'";

                    if(isset($this->tableFields['hidden']) && !$this->showHiddenRows){
                        $q .= " AND (`{$this->tableName}`.`hidden` IS NULL OR `{$this->tableName}`.`hidden` = 0)";
                    }

                    if($additional){
                        $q .= " AND ".$additional;
                    }

                    $q .= " GROUP BY `autocomplete`.`object_id` ORDER BY `autocomplete`.`phrase` ASC, {$this->orderByField} {$this->orderType}";

                    if($this->additionalOrdering){
                        $q .= ', '.$this->additionalOrdering;
                    }
                }
                elseif($this->searchByField){
                    if(!isset($this->tableFields[$this->searchByField])){
                        throw new Exception(get_class($this).": The field `{$this->searchByField}` does not exist in table {$this->tableName}!");
                    }

                    if($this->returnPhraseOnly){
                        $q  = "SELECT `id` AS 'object_id',`{$this->searchByField}` AS 'phrase'";
                    }
                    else{
                        $q = "SELECT *";
                    }

                    $q .= " FROM `{$Core->dbName}`.`{$this->tableName}`";
                    $q .= " WHERE `{$this->searchByField}` LIKE '%$phrase%'";

                    if(isset($this->tableFields['hidden']) && !$this->showHiddenRows){
                        $q .= " AND (`{$this->tableName}`.`hidden` IS NULL OR `{$this->tableName}`.`hidden` = 0)";
                    }

                    if($additional){
                        $q .= " AND ".$additional;
                    }

                    $q .= " ORDER BY ".$this->orderByField." {$this->orderType}";

                    if($this->additionalOrdering){
                        $q .= ', '.$this->additionalOrdering;
                    }
                }
                else{
                    throw new Exception(get_class($this).": In order to use the search function, plesae define autocompleteField and autocompleteObjectId or searchByField!");
                }

                if($limit){
                    if(is_numeric($limit)){
                        $q.= " LIMIT ".(($Core->rewrite->currentPage - 1) * $limit).','.$limit;
                    }
                    else{
                        $q.= " LIMIT ".(($Core->rewrite->currentPage - 1) * $Core->itemsPerPage).','.$Core->itemsPerPage;
                    }
                }

                if($this->returnPhraseOnly){
                    if($Core->db->query($q,$Core->cacheTime, 'simpleArray', $result, 'object_id', 'phrase')){
                        return $result;
                    }
                }
                else{
                    if($Core->db->query($q, $Core->cacheTime, 'simpleArray', $result, 'id')){
                        return $result;
                    }
                }

                return array();
            }
            else{
                $all = $this->getAll($limit, $additional);
                if(empty($all)){
                    return array();
                }

                $result = array();
                foreach($all as $k => $v){
                    if($this->autocompleteField && $this->autocompleteObjectId){
                        if($this->returnPhraseOnly){
                            if(is_array($this->autocompleteField)){
                                $result[$k] = array();
                                foreach($this->autocompleteField as $f){
                                    if(!empty($v[$f])){
                                        $result[$k][] = $v[$f];
                                    }
                                }
                                $result[$k] = implode($this->autocompleteSeparator,$result[$k]);
                            }
                            else{
                                $result[$k] = $v[$this->autocompleteField];
                            }
                        }
                        else return $all;
                    }
                    else if($this->searchByField){
                        if($this->returnPhraseOnly){
                            $result[$k] = $v[$this->searchByField];
                        }
                        else return $all;
                    }
                    else throw new Exception(get_class($this).": In order to use the search function, plesae define autocompleteField and autocompleteObjectId or searchByField!");
                }
                unset($all);
                return $result;
            }
        }

        //this function returns all the elements from the table
        //TRANSLATION   - the results come out translated automatically in the language, which is chosen now;
        //TRANSLATION   - if you want specific language, put it in the $language parameter;
        //PAGINATION    - third parameter supports pagination, similar to the search function
        //HIDDEN FIELD  - also supports hidden field, like the search function
        //PARENT ID     - supports $parentId parameter; if this is set, it will return the elements with this parent id
        //ID            - supports $id parameter; if this is set, it will return the element from the table with the specific id
        //ADDITIONAL    - additional parameter is added to the query usign AND
        //public function getAll($language = false, $noTranslation = false, $limit = false, $parentId = false, $id = false, $additional = false){
        public function getAll($limit = false, $additional = false, $language = false, $parentId = false, $id = false){
            global $Core;

            if(empty($this->tableFields)){
                $this->getTableFields();
            }
            $fields = $this->tableFields;

            $q = "SELECT *";
            
            $additionalFields = array();                        

            if($this->returnTimestamps){
                foreach($fields as $k => $v){
                    if(in_array($v['type'] ,array('timestamp', 'datetime'))){
                        $additionalFields[] = "UNIX_TIMESTAMP(`$k`) AS '{$k}_timestamp'";
                    }
                }
                unset($k,$v);
            }

            if($this->autoFormatTime){
                foreach($fields as $k => $v){
                    if(in_array($v['type'] ,array('timestamp', 'datetime'))){
                        $additionalFields[] = "DATE_FORMAT(`$k`, '{$this->autoFormatTime}') AS '{$k}'";
                    }
                }
            }
            
            if (!empty($additionalFields)) {
                $q .= ", ".implode(', ',$additionalFields);
             }
                                        
            unset($additionalFields);
            #$q .= (implode(", ", array_keys($fields)));

            $q.= " FROM `{$Core->dbName}`.`{$this->tableName}`";

            if(isset($fields['hidden']) && !$this->showHiddenRows){
                $q .= " WHERE (`hidden` IS NULL OR `hidden` = 0)";
            }
            unset($fields);

            if($parentId){
                if(empty($this->parentField)){
                    throw new Exception(get_class($this).": ".$Core->language->error_this_object_does_not_have_a_parent);
                }

                if(!is_numeric($parentId)){
                    throw new Exception(get_class($this).": ".$Core->language->error_parent_ID_cannot_be_empty);
                }

                $parentId = intval($parentId);
                $q .= " ".(stristr($q,'WHERE') ? "AND" : "WHERE")." `{$this->parentField}` = $parentId";
            }
            elseif($id){
                if(is_array($id)){
                    $in = '';
                    foreach($id as $k => $v){
                        if(!is_numeric($v)){
                            throw new Exception(get_class($this).": ".$Core->language->error_id_array_must_be_only_numerics);
                        }

                        $v = intval($v);
                        $in .= "$v,";
                    }
                    $in = substr($in,0,-1);
                    $q .= " ".(stristr($q,'WHERE') ? "AND" : "WHERE")." `id` IN ($in)";
                    unset($k,$v,$in);
                }
                else{
                    $id = intval($id);
                    if(empty($id)){
                        throw new Exception(get_class($this).": ".$Core->language->error_id_cannot_be_empty);
                    }
                    $q .= " ".(stristr($q,'WHERE') ? "AND" : "WHERE")." `id` = $id";
                }
            }

            if($additional){
                $q .= " ".(stristr($q,'WHERE') ? "AND" : "WHERE").' '.$additional;
            }

            if(empty($id)){
                $q .= " ORDER BY {$this->orderByField} ".(strtolower($this->orderType) == 'desc' ? 'DESC' : 'ASC');
                if($this->additionalOrdering){
                    $q .= ', '.$this->additionalOrdering;
                }
            }

            if($limit){
                if(is_numeric($limit)){
                    $q.= " LIMIT ".(($Core->rewrite->currentPage - 1) * $limit).','.$limit;
                }
                else{
                    $q.= " LIMIT ".(($Core->rewrite->currentPage - 1) * $Core->itemsPerPage).','.$Core->itemsPerPage;
                }
            }

            $Core->db->query($q, $Core->cacheTime, 'fillArray', $result);
            if(empty($result)){
                return array();
            }
            unset($q);

            $language = $this->checkLanguage($language);

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
            $result = array_values($result);

            if(!empty($this->explodeFields)){
                $temp = $result;
                foreach($temp as $k => $v){
                    $result[$k] = $this->fixExplodeFields($v);
                }
                unset($temp);
            }

            return !empty($result) ? $result : array();
        }

        //gets a specific row from the table;
        //supports translation like the getAll() function
        //supports an array of ints
        //supports limit and additional
        //public function getById($id, $language = false, $noTranslation = false){
        public function getById($id, $additional = false, $language = false){
            $result = $this->getAll(false, $additional, $language, false, $id);
            if(!is_array($id) && is_array($result)){
                $result = current($result);
            }
            return $result;
        }

        //returns count by parent id
        //additional parameter is added to the query usign AND
        public function getCountByParentId($parentId, $additional = false){
            global $Core;

            if(empty($this->parentField)){
                throw new Exception(get_class($this).": ".$Core->language->error_this_object_does_not_have_a_parent);
            }

            $parentId = intval($parentId);
            if(empty($parentId)){
                throw new Exception(get_class($this).": ".$Core->language->error_parent_ID_cannot_be_empty);
            }

            $q = "SELECT COUNT(*) AS 'ct' FROM `{$Core->dbName}`.`$this->tableName` WHERE `{$this->parentField}` = $parentId";

            if($additional){
                $q .= " AND ".$additional;
            }

            $Core->db->query($q, 0, 'fetch_assoc', $res);

            if(empty($res)){
                return 0;
            }
            return $res['ct'];
        }

        public function getByField($input, $value=false){
            global $Core;

            if(!is_array($input) && $value === false){
                throw new Exception(get_class($this).": Invalid params. Input param1 must be a string(field name) if param2 is set or an array(field => value, field2 => value2) if param2=false");
            }
            if(is_array($input) && $value !== false){
                throw new Exception(get_class($this).": Invalid params. Input param1 must be a string(field name) if param2 is set or an array(field => value, field2 => value2) if param2=false");
            }

            if(!is_array($input) && $value!==false){
                $input=array($input => $value);
                $value=false;
            }

            $fields = $this->getTableFields();

            foreach($input as $field => $value){
                if(!isset($fields[$field])){
                    throw new Exception(get_class($this).": Unknown column `".$field."` passed.");
                }
                if(is_null($value)){
                    $v = ' IS NULL';
                }else{
                    $v = "='".$Core->db->escape($value)."'";
                }
                $where[]="`".$Core->db->escape($field)."`".$v;
            }

            $Core->db->query("SELECT * FROM `{$Core->dbName}`.`{$this->tableName}` WHERE ".implode(' AND ', $where), 0, 'simpleArray', $return);

            if(!$return){
                return array();
            }

            return $return;
        }

        //gets all rows with the provided parent id
        //supports translation like the getAll() function
        //supports an array of ints
        //supports limit and additional
        //public function getByParentId($parentId, $language = false, $noTranslation = false, $limit = false){
        public function getByParentId($parentId, $limit = false, $additional = false, $language = false){
            if(empty($parentId)){
                return array();
            }

            return $this->getAll($limit, $additional, $language, $parentId);
        }

        public function sum($column, $additional = false){
            global $Core;

            $fields = $this->getTableFields();
            if(!isset($fields[$column])){
                throw new Exception(get_class($this).": Unknown column `".$column."` passed.");
            }

            if($additional){
                $additional = ' WHERE '.$additional;
            }

            $Core->db->query("SELECT SUM(`{$column}`) AS 'sum' FROM `{$Core->dbName}`.`{$this->tableName}` $additional", 0, 'fetch_assoc', $sum);
            return $sum['sum'];
        }
        //END OUTPUT FUNCTIONS

        //INPUT FUNCTIONS
        //this validates the input array for the insert,update and translate functions
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

        //general insert funciton, input is array key => value eq column_name => value
        //if $autocomplete is set to false, it will not insert anything into the autocomplete table
        public function add($input = false, $autocomplete = true){
            global $Core;

            $input = $this->prepareQueryArray($input);

            $q = "INSERT INTO `{$Core->dbName}`.`{$this->tableName}` (";
            foreach($input as $k => $v){
                $q .= "`$k`,";
            }

            $q = substr($q,0,-1).') VALUES (';
            foreach($input as $k => $v){
                $q .= ((empty($v) && !((is_numeric($v) && $v == '0'))) ? 'NULL' : (is_numeric($v) ? "'".(str_replace(',', '.', $v))."'" : "'$v'")).",";
            }
            $q = substr($q,0,-1).')';

            if($this->autocompleteObjectId && $autocomplete){
                $acField = $this->formAutocompleteField($input);
                if(empty($acField)){
                    throw new Exception($Core->language->error_generating_index_text);
                }
            }

            try{
                $Core->db->query($q);
                $objectId = $Core->db->insert_id;
                if(isset($acField)){
                    $Core->db->query("INSERT INTO `{$Core->dbName}`.`autocomplete` (`type`, `object_id`, `phrase`) VALUES ({$this->autocompleteObjectId}, $objectId, '{$acField}')");
                    unset($acField);
                }
            }
            catch(Exception $ex){
                $this->handleBuilderException($ex);
            }

            return $objectId;
        }

        //alias of the add function
        public function insert($input, $autocomplete = true){
            return $this->add($input, $autocomplete);
        }

        //deletes rows from the table and the rows in the autocomplete table
        public function delete($id, $additional = false){
            global $Core;

            if(empty($id)){
                throw new Exception ($Core->language->error_id_cannot_be_empty);
            }

            if(!is_array($id)){
                $where = ' = '.intval($id);
            }else{
                $in = '';
                foreach($id as $k => $v){
                    if(!is_numeric($v)){
                        throw new Exception($Core->language->error_id_array_must_be_only_numerics);
                    }

                    $v = intval($v);
                    $in .= "$v,";
                }
                $in = substr($in,0,-1);
                $where = ' IN('.$in.')';
                unset($in);
            }

            if($additional){
                $where .= " AND ".$additional;
            }

            try{
                $Core->db->query("DELETE FROM `{$Core->dbName}`.`{$this->tableName}` WHERE `id` $where");
                if($this->autocompleteObjectId > 0){
                    $Core->db->query("DELETE FROM `{$Core->dbName}`.`autocomplete` WHERE `type` = {$this->autocompleteObjectId} AND `object_id` $where");
                }
            }
            catch(Exception $ex){
                if(stristr($ex->getMessage(),'Mysql Error: Cannot delete or update a parent row: a foreign key constraint fails')){
                    $clName = get_class($this);
                    $clName = (substr($clName,-1) == 's' ? substr($clName,0,-1) : $clName);
                    $clName = strtolower($clName);

                    throw new Exception($Core->language->error_cannot_delete_this.' '.$Core->language->$clName.'! '.$Core->language->error_there_are_children_attached_to_id);
                }
                else{
                    throw new Exception($ex->getMessage());
                }
            }
            unset($where);
            return mysqli_affected_rows($Core->db->insert);
        }

        public function deleteByParentId($id, $additional = false){
            global $Core;

            $id = intval($id);
            if(empty($id)){
                throw new Exception ($Core->language->error_id_cannot_be_empty);
            }

            if(empty($this->parentField)){
                throw new Exception($Core->language->error_this_class_does_not_have_a_parent_id.' - '.get_class($this));
            }

            try{
                if($this->autocompleteObjectId > 0){
                    $ids = $this->getByParentId($id);

                    if($ids){
                        foreach($ids as $i){
                            $in .= $i['id'].',';
                        }
                        unset($i);
                        $in = substr($in,0,-1);

                        $Core->db->query("DELETE FROM `{$Core->dbName}`.`autocomplete` WHERE `type` = {$this->autocompleteObjectId} AND `object_id` IN ({$in})");
                    }
                    unset($ids, $in);
                }

                if($additional){
                    $additional = " AND ".$additional;
                }

                $Core->db->query("DELETE FROM `{$Core->dbName}`.`{$this->tableName}` WHERE `{$this->parentField}` = $id $additional");
            }
            catch(Exception $ex){
                if(stristr($ex->getMessage(), 'Mysql Error: Cannot delete or update a parent row: a foreign key constraint fails')){
                    $clName = get_class($this);
                    $clName = (substr($clName,-1) == 's' ? substr($clName,0,-1) : $clName);
                    $clName = strtolower($clName);

                    throw new Exception($Core->language->error_cannot_delete_this.' '.$Core->language->$clName.'! '.$Core->language->error_there_are_children_attached_to_id);
                }
                else{
                    throw new Exception($ex->getMessage());
                }
            }
            return true;
        }

        //translate the object
        public function translate($objectId, $language, $input){
            global $Core;
            $language = $this->checkLanguage($language);

            if(!is_numeric($objectId)){
                throw new Exception ($Core->language->error_object_id_must_be_numeric);
            }
            $objectId = intval($objectId);
            if(empty($objectId)){
                throw new Exception($Core->language->error_object_id_cannot_be_empty);
            }

            $object = $this->getAll(false, false, false, false, $objectId);

            if(empty($object)){
                $tName = get_class($this);
                $tName = (substr($tName,-1) == 's' ? substr($tName,0,-1) : $tName);
                $tName = strtolower($tName);
                throw new Exception($Core->language->update_failed.': '.$Core->language->unexisting.' '.$Core->language->{$tName}.'!');
            }
            unset($object);

            $input = $this->prepareQueryArray($input);

            $q = "INSERT INTO `{$Core->dbName}`.`{$this->tableName}_lang` (`object_id`,`lang_id`,";
            foreach ($input as $k => $v){
                $q .= "`$k`,";
            }

            $q = substr($q,0,-1);
            $q .= ") VALUES ($objectId,$language,";
            foreach ($input as $k => $v){
                $v = trim($Core->db->escape($v));
                $q .= (empty($v) ? 'NULL' : "'$v'").",";
            }
            $q = substr($q,0,-1);
            $q .= ') ON DUPLICATE KEY UPDATE ';
            foreach ($input as $k => $v){
                $v = trim($Core->db->escape($v));
                $q .= "`$k` = ".(empty($v) ? 'NULL' : "'$v'").",";
            }
            $q = substr($q,0,-1);

            if($this->autocompleteObjectId > 0){
                $acField = $this->formAutocompleteField($input);
                if(empty($acField)){
                    throw new Exception($Core->language->error_generating_index_text);
                }
            }

            try{
                $Core->db->query($q);

                if(isset($acField)){
                    $Core->db->query("INSERT INTO `{$Core->dbName}`.`autocomplete` (`type`,`object_id`,`phrase`,`language_id`)
                    VALUES ({$this->autocompleteObjectId},$objectId,'{$acField}',$language)
                    ON DUPLICATE KEY UPDATE `phrase` = '{$acField}'");
                    unset($acField);
                }
            }
            catch(Exception $ex){
                $this->handleBuilderException($ex);
            }
            return true;
        }

        //this functions updates the database row $objectId with the values from $input
        public function update($objectId, $input, $additional = false){
            global $Core;

            if(!is_numeric($objectId)){
                throw new Exception ($Core->language->error_object_id_must_be_numeric);
            }

            if(empty($input) || !is_array($input)){
                throw new Exception ($Core->language->error_input_must_be_a_non_empty_array);
            }

            if(isset($input['id'])){
                throw new Exception ($Core->language->error_field_id_is_not_allowed);
            }

            $objectId = intval($objectId);
            if(empty($objectId)){
                throw new Exception($Core->language->error_object_id_cannot_be_empty);
            }

            $object = $this->getAll(false, false, false, false, $objectId);
            if(empty($object)){
                $tName = get_class($this);
                $tName = (substr($tName,-1) == 's' ? substr($tName,0,-1) : $tName);
                $tName = strtolower($tName);
                throw new Exception($Core->language->update_failed.': '.$Core->language->unexisting.' '.$Core->language->{$tName}.'!');
            }

            $input = $this->prepareQueryArray($input);
            $q = '';

            foreach ($input as $k => $v){
                $q .= "`$k` = ".((empty($v) && $v !== 0 && $v !== '0') ? 'NULL' : "'$v'").",";
            }

            $q = "UPDATE `{$Core->dbName}`.`{$this->tableName}` SET ".substr($q,0,-1)." WHERE `id` = $objectId";

            if($additional){
                $q .= " AND ".$additional;
            }

            if($this->autocompleteObjectId > 0){
                $acField = $this->formAutocompleteField($input);
                if(empty($acField)){
                    throw new Exception($Core->language->error_generating_index_text);
                }
            }

            try{
                $Core->db->query($q);

                if(isset($acField)){
                    $Core->db->query("UPDATE `{$Core->dbName}`.`autocomplete` SET `phrase` = '{$acField}'
                    WHERE `type` = {$this->autocompleteObjectId} AND `object_id` = $objectId AND `language_id` = ".$Core->language->getDefaultLanguage('id'));
                    unset($acField);
                }
            }
            catch(Exception $ex){
                $this->handleBuilderException($ex);
            }

            return true;
        }

        //this functions updates the database by parent field with the values from $input
        public function updateByParentId($objectId, $input, $additional = false){
            global $Core;
            $return = array();

            if(empty($this->parentField)){
                throw new Exception($Core->language->error_you_must_set_a_parent_field_to_update_by_parent_id);
            }

            if(!$ids = $this->getByParentId($objectId)){
                return $return;
            }

            foreach($ids as $id){
                $return[] = $this->update($id['id'], $input, $additional = false);
            }

            return $return;
        }
        //END INPUT FUNCTIONS

        //TEMPLATE FUNCTIONS
        public function drawTemplate($templateName, $params = false){
            if(empty($templateName)){
                throw new exception("Template name must not be empty!");
            }
            $temp = $this->getTemplate($params);
            if(in_array($templateName,get_class_methods(get_class($temp)))){
                $html = $temp->$templateName();
                unset($temp);

                return $html;
            }
            throw new exception("Template '$templateName' does not exist! It must be a public method of the ".get_class($this)."Templates class!");
        }

        public function getTemplate($params = false){
            global $Core;
            $clName = get_class($this);
            if(is_file($Core->siteDir.'templates/'.strtolower($clName).'.php')){
                require_once($Core->siteDir.'templates/'.strtolower($clName).'.php');
                $className = $clName.'Templates';
                if(!class_exists($className)){
                    throw new exception("Wrong class name in $clName.php! It must be '{$clName}Templates'!");
                }
                $temp = $params ? (new $className($params)) : new $className();
                unset($clName,$className);
                return $temp;
            }
            throw new exception("Template file $clName.php does not exist! Please create it in the 'templates' folder!");
        }

        //alias of getTemplate function
        public function template($params = false){
            return $this->getTemplate($params);
        }
        //END TEMPLATE FUNCUTIONS

        //SOME PRIVATE FUNCTIONS
        //this forms whats is going to be inserted into the autocomplete index table
        private function formAutocompleteField($input){
            if(is_array($this->autocompleteField)){
                $result = '';
                foreach($this->autocompleteField as $acf){
                    if(isset($input[$acf]) && !empty($input[$acf])){
                        $result.= $input[$acf].$this->autocompleteSeparator;
                    }
                }
                return substr($result, 0, -strlen($this->autocompleteSeparator));
            }
            else{
                if(isset($input[$this->autocompleteField]) && !empty($input[$this->autocompleteField])){
                    return $input[$this->autocompleteField];
                }
            }

            return false;
        }

        //this is used to get the arrays from the explode fields in a result
        private function fixExplodeFields($input){
            foreach($this->explodeFields as $field){
                if(empty($input[$field]))
                    $input[$field] = array();
                else{
                    if(substr($input[$field], 0, strlen($this->explodeDelimiter)) == $this->explodeDelimiter){
                        $input[$field] = substr($input[$field], strlen($this->explodeDelimiter));
                    }
                    $fld = explode($this->explodeDelimiter, $input[$field]);
                    $f = array();
                    foreach($fld as $k => $v){
                        $f[$k] = trim($v);
                    }
                    $input[$field] = $f;
                    unset($f,$fld,$k,$v);
                }
            }
            return $input;
        }

        private function handleBuilderException($ex){
            global $Core;
            $m = $ex->getMessage();
            if(stristr($m,'duplicate')){
                $clName = get_class($this);
                $clName = (substr($clName,-1) == 's' ? substr($clName,0,-1) : $clName);
                $clName = strtolower($clName);
                if($clName == 'base'){
                    throw new Exception($Core->language->this_row_in_table.' '.$Core->language->{$this->tableName}.' '.$Core->language->error_already_exists);
                }

                throw new Exception($Core->language->duplication_of.' '.$Core->language->$clName);
            }
            else if(stristr($m,'FOREIGN KEY')){
                $m = substr($m,strpos($m,'CONSTRAINT `') + 12);
                if(stristr($m,'_')){
                    $m = substr($m,strpos($m,'_') + 1);
                }
                $m = substr($m,0,strpos($m,'`'));
                $m = str_replace('_fk','',$m);
                throw new Exception ($Core->language->error_invalid_or_undefined.' '.$Core->language->$m.'');
            }
            else if(stristr($m,'Unknown column')){
                $m = str_replace('Mysql Error: ','',$m);
                $m = str_replace(" in 'field list'",'',$m);
                throw new Exception("$m!");
            }
            else{
                throw new Exception($m);
            }
        }
    }
?>