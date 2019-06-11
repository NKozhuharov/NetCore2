<?php
/**
 * 
 */
class SurveysQuestions extends Base
{
    /**
     * Shows if translations are allowed for the categories
     * Requires the `categories_lang` table
     * @var bool
     */
    protected $allowMultilanguage = true;
    
    /**
     * Creates a new instance of Categories class
     */
    public function __construct()
    {
        $this->tableName   = 'surveys_questions';
        $this->parentField = 'object_id';
        
        if ($this->allowMultilanguage) {
            $this->translationFields = array('title');
        }
    }
}
