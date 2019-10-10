<?php
/**
 * Refer to ContactUs
 * DO NOT USE THIS MODEL WITHOUT CONTACTUS
 * Drop the table `contact_us_subjects_lang` if the subjects will not be translated
 */
class ContactUsSubjects extends Base
{
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
     * Shows if translations are allowed
     * Requires the `surveys_lang` table
     * @var bool
     */
    protected $allowMultilanguage = true;

    /**
     * Creates a new instance of ContactUsSubjects class
     */
    public function __construct()
    {
        global $Core;

        $this->tableName = 'contact_us_subjects';

        if ($this->allowMultilanguage) {
            $this->translationFields = array('name');
        } else {
            $this->translateResult = false;
        }
    }

    /**
     * Override the functions to provide additional validations of the name of the  subject.
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
            mb_strlen($input['name']) < $this->minimumSubjectLength ||
            mb_strlen($input['name']) > $this->maximumSubjectLength
        ) {
            $errorsInFields['name'] = "Must be between %{$this->minimumSubjectLength}%".
            " and %$this->maximumSubjectLength% symbols";
        }

        if (!empty($errorsInFields)) {
            throw new BaseException("The following fields are not valid", $errorsInFields, get_class($this));
        }

        return $input;
    }
}
