<?php
/**
 * Refer to surveys
 * DO NOT USE THIS MODEL WITHOUT SURVEYS
 */
final class SurveysQuestions extends Base
{
    /**
     * Creates a new instance of SurveysQuestions class
     */
    public function __construct()
    {
        global $Core;
        
        $this->tableName    = 'surveys_questions';
        $this->parentField  = 'object_id';
        $this->orderByField = '`order`';
        $this->orderByType  = self::ORDER_ASC;
        
        if ($Core->Surveys->isMultilanguageAllowed()) {
            $this->translationFields = array('question');
        }
    }
    
    /**
     * Gets the answers to the provided question from the db and assigns them to it under key 'answers'
     * @param array $question - the question to add the answers to
     * @return array
     */
    private function addAnswersToQuetion(array $question)
    {
        global $Core;
        
        if (!empty($question)) {
            $question['answers'] = $Core->SurveysAnswers->getByParentId($question['id'], false);
        }
        
        return $question;
    }
    
    /**
     * Override the method to add the answers to the questions
     * @param int $limit - the limit override
     * @param string $additional - the where clause override
     * @param string $orderBy - the order by override
     * @return array
     */
    public function getAll(int $limit = null, string $additional = null, string $orderBy = null)
    {
        $questions = parent::getAll($limit, $additional, $orderBy);
        if (!empty($questions)) {
            foreach ($questions as $questionKey => $question) {
                $questions[$questionKey] = $this->addAnswersToQuetion($question);
            }
        }
        
        return $questions;
    }
    /**
     * Override the method to add the answers to the question
     * @param int $rowId - the id of the row
     * @return array
     * @throws Exception
     */
    public function getById(int $rowId)
    {
        return $this->addQuestionsToSurvey(parent::getById($rowId));
    }
    
    /**
     * Override the method to add the answers to the questions
     * @param int $parentId - the id for the parent field
     * @param int $limit - the limit override
     * @param string $additional - the where clause override
     * @param string $orderBy - the order by override
     * @return array
     */
    public function getByParentId(int $parentId, int $limit = null, string $additional = null, string $orderBy = null)
    {
        $questions = parent::getByParentId($parentId, $limit, $additional, $orderBy);
        
        if (!empty($questions)) {
            foreach ($questions as $questionKey => $question) {
                $questions[$questionKey] = $this->addAnswersToQuetion($question);
            }
        }
        
        return $questions;
    }
}
