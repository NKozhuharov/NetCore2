<?php
/**
 * Refer to surveys
 * DO NOT USE THIS MODEL WITHOUT SURVEYS
 */
final class SurveysAnswers extends Base
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
    
    /**
     * Increments the votes of the answers with the provided answer ids
     * @param array $answerIds - the ids of the answers to increment
     */
    public function recordVotes(array $answerIds)
    {
        global $Core;
        
        if (!empty($answerIds)) {
            $Core->db->query("UPDATE `{$this->tableName}` SET `votes` = `votes` + 1 WHERE `id` IN (".implode(',', $answerIds).")");
        }
    }
}
