<?php
/**
 * Refer to Surveys module
 * DO NOT USE THIS MODEL WITHOUT SURVEYS
 */
final class SurveysVotes extends Base
{
    /**
     * Creates a new instance of SurveysVotes class
     */
    public function __construct()
    {
        $this->tableName = 'surveys_votes';
        $this->parentField = 'survey_id';
        $this->returnTimestamps = true;
    }
    
    /**
     * Returns the user IP from the request
     * Throws Error if the IP is invalid
     * @return string
     * @throws Error
     */
    private function getUserIp()
    {
        if (empty($_SERVER['REMOTE_ADDR'])) {
            throw new Error("The user has no IP address");
        }
        
        if (!filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            throw new Error("Invalid IP address");
        }
        
        return $_SERVER['REMOTE_ADDR'];
    }
    
    /**
     * Returns the user agent from the request
     * @return string
     */
    private function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }
    
    /**
     * Override the function to add user ip and user agent to input
     * @param array $input - it must contain the field names => values
     * @param int $flag - used for insert ignore and insert on duplicate key update queries
     * @return int
     */
    public function insert(array $input, int $flag = null)
    {
        global $Core;
        
        $input['ip'] = $this->getUserIp();
        $input['user_agent'] = $this->getUserAgent();
        
        return parent::insert($input, $flag);
    }
    
    /**
     * Checks if the current user can vote to survey, by the provided survey id
     * Throws Exception if he isn't
     * @param int $surveryId - the id of the survey to check
     * @throws Exception
     */
    public function checkVotingRights(int $surveyId)
    {
        global $Core;
        
        $lastVote = $this->getAll(
            1, 
            "`survery_id` = {$surveyId} AND `ip` = '".$this->getUserIp()."' AND `user_agent` = '".$this->getUserAgent()."'"
        );
        
        if (!empty($lastVote)) {
            $lastVote = current($lastVote);
            if ($lastVote['added_timestamp'] >= time() - $Core->{$Core->SurveysModuleName}->getVoteInterval()) {
                throw new Exception("You cannot vote again yet");
            }
        }
    }
}
