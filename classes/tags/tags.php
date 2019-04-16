<?php
class Tags extends Base
{
    use InputAndTranslate;
    
    public function __construct()
    {
        $this->tableName = 'tags';
        $this->orderByField = 'id';
        $this->orderByType = self::ORDER_DESC;
        $this->translationFields = array('name');
    }
    
    
    
















    
}
