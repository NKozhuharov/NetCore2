<?php
/**
 * This is a platform module
 * This module can be extended with something like 'SiteContactUs extends ContactUs', which allows configuration
 * Create the tables from the 'contactus.sql'
 * Use subjectIsString to choose wether to use string subjects or to use those from the `contact_us_subjects` table
 * If the `contact_us_subjects` is used, create it from 'contact_us_subjects.sql' and drop the column `subject` from the
 * `contactus` table;
 * If not, drop the column `subject_id` and it's corresponding foreign key; after that, drop the `contact_us_subjects` and
 * `contact_us_subjects_lang` tables.
 */
class ContactUs extends Base
{
    /**
     * @var int 
     * The minimum length of the name of the user
     */
    protected $minimumNameLength = 5;
    
    /**
     * @var int 
     * The maximum length of the name of the user
     */
    protected $maximumNameLength = 50;
    
    /**
     * @var int 
     * The minimum length of the message of the user
     */
    protected $minimumMessageLength = 20;
    
    /**
     * @var bool
     * Set to false to use the ContactUsSubjects model insetead of strings.
     * Requires the contact_us_subjects / contact_us_subjects_lang tables.
     */
    protected $subjectIsString = false;
    
    /**
     * @var array
     * A map, describing the statuses of messages
     */
    protected $statusMap = array(
        0 => 'unseen',
        1 => 'seen',
        2 => 'responded',
    );
    
    /**
     * Creates a new instance of ContactUs class
     */
    public function __construct()
    {
        $this->tableName    = 'contact_us';
        $this->orderByField = 'added';
        $this->orderByType  = self::ORDER_DESC;
    }
    
    /**
     * Override the functions to provide additional validations of the name, email, message and subject.
     * Throws BaseException if something is wrong with the provided input
     * @param array $input - the input for a insert/update query
     * @param bool $isUpdate - the input query is an update query
     * @return array
     * @throws BaseException
     */
    protected function validateAndPrepareInputArray(array $input, bool $isUpdate = null)
    {
        $input = parent::validateAndPrepareInputArray($input, $isUpdate);
        
        $errorsInFields = array();
        
        if (
            mb_strlen($input['name']) < $this->minimumNameLength || 
            mb_strlen($input['name']) > $this->maximumNameLength
        ) {
            $errorsInFields['name'] = "Must be between %{$this->minimumNameLength}%".
            " and %$this->maximumNameLength% symbols";
        }
        
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errorsInFields['email'] = "Email is not valid";
        }
        
        if (mb_strlen($input['message']) < $this->minimumMessageLength) {
            $errorsInFields['message'] = "Must be more than %{$this->minimumMessageLength}% symbols";
        }
        
        if ($this->subjectIsString === true) {
            if (
                mb_strlen($input['subject']) < $this->minimumSubjectLength || 
                mb_strlen($input['subject']) > $this->maximumSubjectLength
            ) {
                $errorsInFields['subject'] = "Must be between %{$this->minimumSubjectLength}%". 
                " and %$this->maximumSubjectLength% symbols";
            }
        }
        
        if (!empty($errorsInFields)) {
            throw new BaseException("The following fields are not valid", $errorsInFields, get_class($this));
        }
        
        $input['name']    = trim($input['name']);
        $input['email']   = trim($input['email']);
        $input['message'] = trim($input['message']);
        
        if ($this->subjectIsString === true) {
            $input['subject'] = trim($input['subject']);
        }
        
        return $input;
    }
    
    /**
     * Override the function add the user's IP address to the input.
     * @param array $input - it must contain the field names => values
     * @param int $flag - used for insert ignore and insert on duplicate key update queries
     * @return int
     */
    public function insert(array $input, int $flag = null)
    {
        $input['ip'] = $_SERVER['REMOTE_ADDR'];

        return parnent::insert($input, $flag);
    }
    
    /**
     * Updates a message's flag to 'seen' (1)
     * Returns the number of affected rows
     * @param int $messageId - the id of the message to be marked
     * @return int
     */
    public function markAsSeen(int $messageId)
    {
        return $this->updateById($messageId, array('status' => 1));
    }
    
    /**
     * Updates a message's flag to 'responded' (2)
     * Returns the number of affected rows
     * @param int $messageId - the id of the message to be marked
     * @return int
     */
    public function markAsResponded(int $messageId)
    {
        return $this->updateById($messageId, array('status' => 2));
    }
}
