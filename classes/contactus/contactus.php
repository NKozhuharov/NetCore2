<?php
/**
 * This is a platform module
 * This module can be extended with something like 'SiteContactUs extends ContactUs', which allows configuration.
 * It can also be used directly, without extending it, using the default configuration.
 * Create the tables from the 'contactus.sql';
 * The module will dynamically validate the fields, which are present as columns in the table `contact_us`.
 * If the `contact_us_subjects` is used, create it from 'contact_us_subjects.sql' and drop the column `subject` from the
 * `contactus` table;
 * If not, drop the column `subject_id` and it's corresponding foreign key; after that, drop the `contact_us_subjects` and
 * `contact_us_subjects_lang` tables.
 * Drop any of the other columns which are not required!
 * Allowed fields are 'name', 'email', 'phone', 'subject'/'subject_id', 'message' and 'accept_terms'
 */
class ContactUs extends Base
{
    //TODO - dynamicly define the required fields and their validations
    //todo - use Validations!
    //accept terms
    
    const NAME_FIELD_NAME       = 'name';
    const EMAIL_FIELD_NAME      = 'email';
    const PHONE_FIELD_NAME      = 'phone';
    const MESSAGE_FIELD_NAME    = 'message';
    const SUBJECT_FIELD_NAME    = 'subject';
    const SUBJECT_ID_FIELD_NAME = 'subject_id';
    const TERMS_FIELD_NAME      = 'accept_terms';
    
    /**
     * @var bool
     * Shows if the subject fields eixsts in the model's table.
     * If it exists, the subject is considered string
     */
    private $subjectIsString;
    
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
     * @var int
     * The minimum length of the name allowed for subjects
     */
    protected $minimumSubjectLength = 5;
    
    /**
     * @var int
     * The maximum length of the name allowed for subjects
     */
    protected $maximumSubjectLength = 50;
    
    /**
     * @var bool
     * Is the accept_terms field required
     */
    protected $requireAcceptTerms = false;
    
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
        
        $this->checkTableFields();
        
        $this->subjectIsString();
    }
    
    /**
     * Checks if subject field exists in the model's table; if it exists the the subject will be considered string
     * Throws Exception if subject_id and subject are both set
     * @throws Exception
     */
    private function subjectIsString()
    {
        $fields = $this->tableFields->getFields();
        
        if (isset($fields[self::SUBJECT_FIELD_NAME]) && isset($fields[self::SUBJECT_ID_FIELD_NAME])) {
            throw new Exception("Only one of the following fields is allowed ".
            self::SUBJECT_FIELD_NAME." or ".self::SUBJECT_ID_FIELD_NAME);
        }
        
        if (isset($fields[self::SUBJECT_FIELD_NAME])) {
            $this->subjectIsString = true;
        }
        
        $this->subjectIsString = false;
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
        global $Core;
        
        $input = parent::validateAndPrepareInputArray($input, $isUpdate);
        
        $fields = $this->tableFields->getFields();
        
        $errorsInFields = array();
        
        if (isset($fields[self::NAME_FIELD_NAME])) {
            if (
                mb_strlen($input[self::NAME_FIELD_NAME]) < $this->minimumNameLength || 
                mb_strlen($input[self::NAME_FIELD_NAME]) > $this->maximumNameLength
            ) {
                $errorsInFields[self::NAME_FIELD_NAME] = "Must be between %{$this->minimumNameLength}%".
                " and %$this->maximumNameLength% symbols";
            } elseif ($Core->Validations->validateName($input[self::NAME_FIELD_NAME]) === false) {
                $errorsInFields[self::NAME_FIELD_NAME] = "Is not a valid name";
            }
            
            $input[self::NAME_FIELD_NAME] = trim($input[self::NAME_FIELD_NAME]);
        }
        
        if (isset($fields[self::EMAIL_FIELD_NAME])) {
            if ($Core->Validations->validateEmail($input[self::EMAIL_FIELD_NAME]) === false) {
                $errorsInFields[self::EMAIL_FIELD_NAME] = "Email is not valid";
            }
            $input[self::EMAIL_FIELD_NAME] = trim($input[self::EMAIL_FIELD_NAME]);
        }
        
        if (isset($fields[self::PHONE_FIELD_NAME])) {
            if ($Core->Validations->validatePhone($input[self::PHONE_FIELD_NAME]) === false) {
                $errorsInFields[self::PHONE_FIELD_NAME] = "Phone is not valid";
            }
            $input[self::PHONE_FIELD_NAME] = trim($input[self::PHONE_FIELD_NAME]);
        }
        
        if (isset($fields[self::MESSAGE_FIELD_NAME])) {
            if (mb_strlen($input[self::MESSAGE_FIELD_NAME]) < $this->minimumMessageLength) {
                $errorsInFields[self::MESSAGE_FIELD_NAME] = "Must be more than %{$this->minimumMessageLength}% symbols";
            }
            $input[self::MESSAGE_FIELD_NAME] = trim($input[self::MESSAGE_FIELD_NAME]);
        }
        
        if ($this->subjectIsString === true) {
            if (
                mb_strlen($input[self::SUBJECT_FIELD_NAME]) < $this->minimumSubjectLength || 
                mb_strlen($input[self::SUBJECT_FIELD_NAME]) > $this->maximumSubjectLength
            ) {
                $errorsInFields[self::SUBJECT_FIELD_NAME] = "Must be between %{$this->minimumSubjectLength}%". 
                " and %$this->maximumSubjectLength% symbols";
            }
            
            $input[self::SUBJECT_FIELD_NAME] = trim($input[self::SUBJECT_FIELD_NAME]);
        }
        
        if ($this->requireAcceptTerms === true) {
            if (!isset($input[self::TERMS_FIELD_NAME]) || empty($input[self::TERMS_FIELD_NAME])) {
                $errorsInFields[self::TERMS_FIELD_NAME] = "You must accept the terms and conditions";
            } else {
                unset($input[self::TERMS_FIELD_NAME]);
            }
        }
        
        if (!empty($errorsInFields)) {
            throw new BaseException("The following fields are not valid", $errorsInFields, get_class($this));
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
