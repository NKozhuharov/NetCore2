<?php
/**
 * Refer to surveys
 * DO NOT USE THIS MODEL WITHOUT SURVEYS
 */
class SurveysAnswers extends Base
{
    /**
     * Creates a new instance of SurveysAnswers class
     */
    public function __construct()
    {
        global $Core;        

        $this->tableName    = 'surveys_answers';
        $this->parentField  = 'object_id';
        $this->orderByField = '`order`';
        $this->orderByType  = self::ORDER_ASC;
        
        if ($Core->Surveys->isMultilanguageAllowed()) {
            $this->translationFields = array('answer');
        }
    }
}
