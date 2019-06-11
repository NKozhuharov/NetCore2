<?php
/**
 * 
 */
class SurveysVotes extends Base
{
    /**
     * How many seconds between votes for a single user
     * @var int
     */
    protected $voteInteraval = 3600;
    
    /**
     * Creates a new instance of Categories class
     */
    public function __construct()
    {
        $this->tableName = 'surveys_votes';
        $this->parentField = 'object_id';
    }
    
    private function getUserIp()
    {
        if (empty($_SERVER['REMOTE_ADDR'])) {
            throw new Exception("The user has no IP address");
        }
        
        if (!filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            throw new Exception("Invalid IP address");
        }
        
        return $_SERVER['REMOTE_ADDR'];
    }
}
