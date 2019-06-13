<?php
/**
 * This is a platform module
 * It should be used directly, without extending it
 * Create the tables from the 'surveys.sql'
 * Drop `surveys_lang`/`surveys_questions_lang`/`surveys_answers_lang` table if it is not used
 */
class Surveys extends Base
{
    /**
     * Shows if translations are allowed
     * Requires the `surveys_lang` table
     * @var bool
     */
    protected $allowMultilanguage = true;
    
    /**
     * Creates a new instance of Surveys class
     */
    public function __construct()
    {
        $this->tableName       = 'surveys';
        $this->hiddenFieldName = 'expired';
        
        if ($this->allowMultilanguage) {
            $this->translationFields = array('title', 'link');
        }
    }
    
    /**
     * Are translations allowed for surveys
     * @return bool
     */
    public function isMultilanguageAllowed()
    {
        return $this->allowMultilanguage;
    }
    
    /**
     * Gets the questions to the provided survey from the db and assigns them to it under key 'questions'
     * @param array $survey - the survey to add the questions to
     * @return array
     */
    private function addQuestionsToSurvey(array $survey)
    {
        global $Core;
        
        if (!empty($survey)){
            $survey['questions'] = $Core->SurveysQuestions->getByParentId($survey['id']);
        }
        
        return $survey;
    }
    
    /**
     * Override the method to add the questions to the surveys
     * @param int $limit - the limit override
     * @param string $additional - the where clause override
     * @param string $orderBy - the order by override
     * @return array
     */
    public function getAll(int $limit = null, string $additional = null, string $orderBy = null)
    {
        $surveys = parent::getAll($limit, $additional, $orderBy);
        if (!empty($surveys)) {
            foreach ($surveys as $surveyKey => $survey) {
                $surveys[$surveyKey] = $this->addQuestionsToSurvey($survey);
            }
        }
        
        return $surveys;
    }
    /**
     * Override the method to add the questions to the survey
     * @param int $rowId - the id of the row
     * @return array
     * @throws Exception
     */
    public function getById(int $rowId)
    {
        return $this->addQuestionsToSurvey(parent::getById($rowId));
    }
    
    /**
     * Override the base function to add automatic generation of links;
     * It will exptect the input to contain key questions, which contains key answers, corresponding to the questions
     * and the answers of the survey.
     * Inserts the survey, next the questions, next the answers.
     * Should some validation fails, it will delete the already inserted data
     * Returns the id of the inserted survey
     * 
     * @param array $input - it must contain the field names => values
     * @param int $flag - used for insert ignore and insert on duplicate key update queries
     * @return int
     * @throws Exception
     */
    public function insert(array $input, int $flag = null)
    {
        global $Core;
        
        if (!isset($input['questions']) || empty($input['questions']) || !is_array($input['questions'])) {
            throw new Exception("Provide questions for the survey!");
        }
        
        $questions = $input['questions'];
        unset($input['questions']);
        
        foreach ($questions as $question) {
            if (!is_array($question)) {
                throw new Exception("Each question should be an array!");
            }
            
            if (!isset($question['answers']) || empty($question['answers']) || !is_array($question['answers'])) {
                throw new Exception("Provide answers for the questions!");
            }
            
            foreach ($question['answers'] as $answer) {
                if (!is_array($answer)) {
                    throw new Exception("Each answer should be an array!");
                }
            }
        }
        
        if (!isset($input['link']) || empty($input['link'])) {
            $input['link'] = $Core->GlobalFunctions->getHref($input['title'], $this->tableName, $this->linkField);
        }
        
        $surveyId = parent::insert($input, $flag);
        
        try {
            foreach ($questions as $question) {
                $question['object_id'] = $surveyId;
                
                $answers = $question['answers'];
                unset($question['answers']);
                
                $qeustionId = $Core->SurveysQuestions->insert($question);
                
                foreach ($answers as $answer) {
                    $answer['object_id'] = $qeustionId;
                    $Core->SurveysAnswers->insert($answer);
                }
                
                unset($answers, $qeustionId);
            }
        } catch (Exception $ex) {
            $this->deleteById($surveyId);
            throw new Exception($ex->getMessage());
        } catch (BaseException $ex) {
            $this->deleteById($surveyId);
            throw new BaseException($ex->getMessage(), $ex->getData(), get_class($this));
        } catch (Error $ex) {
            $this->deleteById($surveyId);
            throw new Error($ex->getMessage());
        }
    
        return $surveyId;
    }
    
    /**
     * Override the base function to add automatic generation of links
     * @param int $objectId - the id of the row where the translated object is
     * @param int $languageId - the language id to translate to
     * @param array $input - the translated object data
     * @return int
     * @throws Exception
     */
    public function translate(int $objectId, int $languageId, array $input)
    {
        global $Core;
        
        if (!isset($input['link']) || empty($input['link'])) {
            $input['link'] = $Core->GlobalFunctions->getHref($input['title'], "{$this->tableName}_lang", $this->linkField);
        }
        
        return parent::translate($objectId, $languageId, $input);
    }
    
    /**
     * Override the function to forbid it
     * @param int $objectId - the id of the row to be updated
     * @param array $input - it must contain the field names => values
     * @param string $additional - the where clause override
     * @throws Exception
     */
    public function update(array $input, string $additional)
    {
        throw new Exception("Basic update is forbidden");
    }
    
    /**
     * Override the function to forbid it
     * @param string $additional - the where clause override
     * @throws Exception
     * @return int
     */
    public function updateAll(array $input)
    {
        throw new Exception("Basic update is forbidden");
    }
    
    /**
     * Override the base function to add automatic generation of links;
     * It will insert a new object, then delete the existing one, then update the id of the new object, to id of the old
     * Ignores the additional parameter.
     * Returns 1 if the survey is updated
     * 
     * @param int $objectId - the id of the row to be updated
     * @param array $input - it must contain the field names => values
     * @param string $additional - ignored
     * @throws Exception
     * @return int
     */
    public function updateById(int $objectId, array $input, string $additional = null)
    {
        global $Core;
        
        $existingSurvey = $this->getById($objectId);
        if (!empty($existingSurvey['published_on'])) {
            throw new Exception("Cannot update published surveys!");
        }
        
        if (!isset($input['link']) || empty($input['link'])) {
            if ($input['title'] === $existingSurvey['title']) {
                $input['link'] = $existingSurvey['link'];
            } else {
                $input['link'] = $Core->GlobalFunctions->getHref($input['title'], $this->tableName, $this->linkField);
            }
        }
        
        $newSurveyId = $this->insert($input);
        $this->deleteById($existingSurvey['id']);
        
        $updater = new BaseUpdate($this->tableName);
        $updater->setFieldsAndValues(array('id' => $existingSurvey['id']));
        $updater->setWhere("`id` = {$newSurveyId}");
        
        return $this->executeUpdateQuery($updater);
        
    }
    
    /**
     * Override the function to forbid deletion
     * Returns the number of deleted rows
     * @param string $additional - the where clause override
     * @throws Exception
     */
    public function delete(string $additional)
    {
        throw new Exception("Delete is forbidden");
    }

    /**
     * Override the function to forbid deletion
     * @throws Exception
     */
    public function deleteAll()
    {
        throw new Exception("Delete is forbidden");
    }

    /**
     * Override the function to forbid deletion
     * @param int $id - the id of the row
     * @throws Exception
     */
    public function deleteById(int $rowId)
    {
        $survey = $this->getById($rowId);
        if (!empty($survey['published_on'])) {
            throw new Exception("Cannot delete published surveys!");
        }
        return parent::deleteById($rowId);
    }
    
    /**
     * Checks if the survey with the provided id exists
     * Throws Exception if it doesn't
     * @param int $objectId - the id of the survey
     * @throws Exception
     */
    private function ensureSurveyExists(int $objectId)
    {
        $survey = $this->getById($objectId);
        
        if (empty($survey)) {
            throw new Exception("This survey does not exist");
        }
    }
    
    /**
     * Sets the survey with the provided id to expired
     * @param int $objectId - the id of the survey
     */
    public function setToExpired(int $objectId)
    {
        $this->ensureSurveyExists($objectId);
        
        $this->updateById($objectId, array('expired' => 1));
    }
    
    /**
     * Publishes the survey with the provided id
     * @param int $objectId - the id of the survey
     */
    public function publish(int $objectId)
    {
        global $Core;
        
        $this->ensureSurveyExists($objectId);
        
        $this->updateById($objectId, array('published_on' => $Core->GlobalFunctions->formatMysqlTime(time(), true)));
    }
    
    /**
     * Searches for surveys, which have the `expire_on` column set to a certain value;
     * If any of them surpases the current time, it will mark that survey as expired.
     */
    public function setAllExpiredSurveysToExpired()
    {
        $this->returnTimestamps = true;
        $surveys = $this->getAll(null, " `expired` = 0 AND `expires_on` IS NOT NULL");
        
        if (!empty($surveys)) {
            foreach ($surveys as $survey) {
                if ($survey['expires_on_timestamp'] <= time()) {
                    $this->setToExpired($survey['id']);
                }
            }
        }
    }
}
