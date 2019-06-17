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
     * How many seconds between votes for a single user
     * @var int
     */
    protected $voteInteraval = 3600;
    
    /**
     * Creates a new instance of Surveys class
     */
    public function __construct()
    {
        $this->tableName       = 'surveys';
        $this->hiddenFieldName = 'expired';
        
        if ($this->allowMultilanguage) {
            $this->translationFields = array('title', 'link');
        } else {
            $this->translateResult = false;
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
     * Gets how much time has to pass in order for a user to vote on a survey again
     * @return int
     */
    public function getVoteInterval()
    {
        return $this->voteInteraval;
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
            $survey['questions'] = $Core->SurveysQuestions->getByParentId($survey['id'], false);
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
     * Ensures the structure of the array for inserting or updating a survey is correct
     * @param array $input - a survey to insert/update
     * @throws Exception
     */
    private function ensureInputHasQuestionsAndAnswers(array $input)
    {
        if (!isset($input['questions']) || empty($input['questions']) || !is_array($input['questions'])) {
            throw new Exception("Provide questions for the survey!");
        }
        
        foreach ($input['questions'] as $question) {
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
    }
    
    /**
     * Checks for duplicated questions and duplicated answers of the same question.
     * Used for insert and update
     * @param array $questions - an array of questions (and their answers)
     * @throws BaseException
     */
    private function ensureSurveyQuestionsAndAnswersAreUnique(array $questions)
    {
        $questionTitles = array();
        foreach ($questions as $question) {
            if (!in_array($question['question'], $questionTitles)) {
                $questionTitles[] = $question['question'];
            } else {
                throw new BaseException(
                    "The following %%surveysquestion%% already exists %%{$question['question']}%%", 
                    array(),
                    get_class($this)
                );
            }
            
            $answers = array();
            foreach ($question['answers'] as $answer) {
                if (!in_array($answer['answer'], $answers)) {
                    $answers[] = $answer['answer'];
                } else {
                    throw new BaseException(
                        "The following %%surveysanswer%% already exists %%{$answer['answer']}%%",
                        array(),
                        get_class($this)
                    );
                }
            }
        }
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
        
        $this->ensureInputHasQuestionsAndAnswers($input);
        
        $this->ensureSurveyQuestionsAndAnswersAreUnique($input['questions']);
        
        $questions = $input['questions'];
        unset($input['questions']);
        
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
        
        $this->ensureInputHasQuestionsAndAnswers($input);
        
        $questions = $input['questions'];
        unset($input['questions']);
        
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
     * Removes all qeustions, which are removed from the user, when updating a survey
     * @param array $existingSurvey - the body of the survey, which is being updated
     * @param array $questions - all the questions from the update request
     */
    private function onUpdateDeleteRemovedQuestions(array $existingSurvey, array $questions)
    {
        global $Core;
        
        foreach ($existingSurvey['questions'] as $existingQuestion) {
            $found = false;
            foreach ($questions as $question) {
                if (isset($question['id']) && !empty($question['id']) && $existingQuestion['id'] == $question['id']) {
                    $found = true;
                    break;
                }
            }
            
            if ($found === false) {
                $Core->SurveysQuestions->deleteById($existingQuestion['id']);
            }
        }
    }
    
    /**
     * Removes all answers from a question, which are removed from the user, when updating a survey
     * @param array $existingSurvey - the body of the survey, which is being updated
     * @param array $question - the body of the question
     */
    private function onUpdateDeleteRemovedQuestionAnswers(array $existingSurvey, array $question)
    {
        global $Core;
        
        foreach ($existingSurvey['questions'] as $existingQuestion) {
            if ($existingQuestion['id'] == $question['id']) {
                $existingAnswers = $existingQuestion['answers'];
            }   
        }
        
        foreach ($existingAnswers as $existingAnswer) {
            $found = false;
            
            foreach ($question['answers'] as $answer) {
                if (isset($answer['id']) && !empty($answer['id']) && $existingAnswer['id'] == $answer['id']) {
                    $found = true;
                    break;
                }
            }
            
            if ($found === false) {
                $Core->SurveysAnswers->deleteById($existingAnswer['id']);
            }
        }
    }
    
    /**
     * Updates or inserts the answers of an existing qeustion
     * @param array $question - the body of the question
     */
    private function onUpdateInsertOrUpdateQuestionAnswers(array $question)
    {
        global $Core;
        
        foreach ($question['answers'] as $answer) {
            if (isset($answer['id']) && !empty($answer['id'])) {
                $Core->SurveysAnswers->updateById(
                    $answer['id'], 
                    array(
                        'answer' => $answer['answer'],
                        'order'  => $answer['order'],
                    )
                );
            } else {
                $answer['object_id'] = $question['id'];
                $Core->SurveysAnswers->insert($answer);
            }
        }   
    }
    
    /**
     * Updates existing qeustion and it's answers
     * @param array $existingSurvey - the body of the survey, which is being updated
     * @param array $question - the body of the question
     */
    private function onUpdateUpdateExistingQuestion(array $existingSurvey, array $question)
    {
        global $Core;
        
        $this->onUpdateDeleteRemovedQuestionAnswers($existingSurvey, $question);
        
        $this->onUpdateInsertOrUpdateQuestionAnswers($question);
        
        $Core->SurveysQuestions->updateById(
            $question['id'],
            array(
                'question' => $question['question'],
                'order'    => $question['order'],
            )
        );
    }
    
    /**
     * Inserts a new qeustion and it's answers
     * @param array $question - the body of the question
     * @param int $surveyId - the parent survey id for the question
     */
    private function onUpdateInsertNewQuestion(array $question, int $surveyId)
    {
        global $Core;
        
        $answers = $question['answers'];
        unset($question['answers']);
        
        $question['object_id'] = $surveyId;
        $questionId = $Core->SurveysQuestions->insert($question);
        foreach ($answers as $answer) {
            $answer['object_id'] = $questionId;
            $Core->SurveysAnswers->insert($answer);
        }
    }
    
    /**
     * Override the base function to add automatic generation of links.
     * Expects the input to contain an 'questions' key, which has to be array, containing an 'answers' key.
     * Validates the structure of the $input array, then checks for duplicated names of questions and 
     * answers of the same question.
     * Questions and answers, provided without the 'id' key will be considered new and will be inserted.
     * Existing questions and answers which are not provided with their corresponding 'id's will be deleted.
     * It will update the existing survey.
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
        
        $this->translateResult = false;
        $existingSurvey = $this->getById($objectId);
        
        if (!empty($existingSurvey['published_on'])) {
            throw new Exception("Cannot update published surveys!");
        }
        
        $this->ensureInputHasQuestionsAndAnswers($input);
        
        $this->ensureSurveyQuestionsAndAnswersAreUnique($input['questions']);
        
        $questions = $input['questions'];
        unset($input['questions']);
        
        if (!isset($input['link']) || empty($input['link'])) {
            if ($input['title'] === $existingSurvey['title']) {
                $input['link'] = $existingSurvey['link'];
            } else {
                $input['link'] = $Core->GlobalFunctions->getHref($input['title'], $this->tableName, $this->linkField);
            }
        }
        
        $this->onUpdateDeleteRemovedQuestions($existingSurvey, $questions);
        
        foreach ($questions as $question) {
            if (isset($question['id']) && !empty($question['id'])) {
                $this->onUpdateUpdateExistingQuestion($existingSurvey, $question);
            } else {
                $this->onUpdateInsertNewQuestion($question, $objectId);
            }
        }
        
        return parent::updateById($objectId, $input);
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
    
    /**
     * Records votes for a survey, with the provided answer ids
     * Every answer should have his unique question, and all the questions have to belong to a single survey
     * Throws Exception if the provided answer ids are invalid
     * Returns the survey, which was voted for
     * 
     * @param array $answerIds - the id's of the answers to vote for
     * @return array
     * @throws Exception
     */
    public function vote(array $answerIds)
    {
        global $Core;
        
        if (empty($answerIds)) {
            throw new Exception("Provide at least one answer");
        }
        
        if ($this->allowMultilanguage === true) {
            $Core->SurveysAnswers->turnOffTranslation();
            $Core->SurveysQuestions->turnOffTranslation();
        }
        
        $answers = $Core->SurveysAnswers->getAll(false, "`id` IN (".implode(',', $answerIds).")");
        
        if (empty($answers)) {
            throw new Exception("One or more answers does not exist");
        }
        
        $questionIds = array();
        foreach ($answers as $answer) {
            if (in_array($answer['object_id'], $questionIds)) {
                throw new Exception("One question can have only one answer");
            }
            $questionIds[] = $answer['object_id'];
        }
        
        $questions = $Core->SurveysQuestions->getAll(false, "`id` IN (".implode(',', $questionIds).")");
        
        $surveyId = 0;
        
        foreach ($questions as $question) {
            if ($surveyId === 0) {
                $surveyId = $question['object_id'];
            } elseif ($surveyId !== $question['object_id']) {
                throw new Exception("All questions must belong to a single survey");
            }
        }
        
        $Core->SurveysVotes->checkVotingRights($surveyId);
        
        foreach($answerIds as $answerId) {
            $Core->SurveysVotes->insert(
                array(
                    'survery_id' => $surveyId,
                    'answer_id'  => $answerId,
                )
            );
        }
        
        $Core->SurveysAnswers->recordVotes($answerIds);
        
        return $this->getById($surveyId);
    }
}
