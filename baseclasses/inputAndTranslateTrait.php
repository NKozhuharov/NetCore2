<?php
/**
 * The functions in this trait require the $input to be passed like this:
 * array (
 *  lang_id_1 => array('k1' => 'v1', 'k2' => 'v2'),
 *  lang_id_2 => array('k1' => 'v1', 'k2' => 'v2'),
 * )
 */
trait InputAndTranslate
{
    /**
     * Checks if the input contains all of the active languages
     * Throws Error if it doesn't
     * @param array $input - the input to check
     * @throws Error
     */
    private function checkInputForMissingLanguages(array $input)
    {
        global $Core;

        foreach ($Core->Language->getActiveLanguagesIds() as $activeLanguagesId) {
            if (!isset($input[$activeLanguagesId])) {
                throw new Error("The input must have all data for all active languages! Missing - {$activeLanguagesId}");
            }
        }
    }

    /**
     * Checks if the input contains values for all translation fields
     * Throws BaseException a field is not set or it's empty
     * @param array $input - the input to check
     * @throws BaseException
     */
    private function checkInputForMissingAndEmptyTranslationFields(array $input)
    {
        global $Core;

        $missingFields = array();
        $emptyFields = array();

        foreach ($input as $languageId => $inputData) {
            foreach ($this->translationFields as $translationField) {
                if (!isset($inputData[$translationField])) {
                    $missingFields[$translationField] = 'Is requried for language %%'.
                        $Core->Language->getById($languageId)['name'].'%%';
                } elseif (empty($inputData[$translationField])) {
                    $emptyFields[$translationField] = 'Is requried for language %%'.
                        $Core->Language->getById($languageId)['name'].'%%';
                }
            }
        }

        if (!empty($missingFields)) {
            throw new BaseException("The following fields are required", $missingFields, get_class($this));
        }

        if (!empty($emptyFields)) {
            throw new BaseException("The following fields must not be empty", $emptyFields, get_class($this));
        }
    }

    /**
     * Inserts and directly translates the provided input
     * Requries translationFields to work
     * Requries the input to be structured like array (lang_id_1 => array('k1' => 'v1', 'k2' => 'v2'))
     * All fields, which are set as translationFields are required
     * Returns the inserted object id
     * @param array $input - the input to insert and translate
     * @return int
     */
    public function insertAndTranslate(array $input)
    {
        global $Core;

        $this->checkIfTranslationFieldsAreSet();

        $this->checkInputForMissingLanguages($input);

        $this->checkInputForMissingAndEmptyTranslationFields($input);

        $objectId = $this->insert($input[$Core->Language->getDefaultLanguageId()]);

        foreach ($input as $languageId => $inputData) {
            if ($languageId != $Core->Language->getDefaultLanguageId()) {
                $this->translate($objectId, $languageId, $inputData);
            }
        }

        return $objectId;
    }

    /**
     * Updates and directly translates the provided input
     * Requries translationFields to work
     * Requries the input to be structured like array (lang_id_1 => array('k1' => 'v1', 'k2' => 'v2'))
     * All fields, which are set as translationFields are required
     * Returns the number of inserted rows
     * @param array $input - the input to insert and translate
     * @return int
     */
    public function updateByIdAndTranslate(int $objectId, array $input)
    {
        global $Core;

        $this->checkIfTranslationFieldsAreSet();

        $this->validateObjectId($objectId);

        $this->checkInputForMissingLanguages($input);

        $this->checkInputForMissingAndEmptyTranslationFields($input);

        $rows = $this->updateById($objectId, $input[$Core->Language->getDefaultLanguageId()]);

        foreach ($input as $languageId => $inputData) {
            if ($languageId != $Core->Language->getDefaultLanguageId()) {
                $this->translate($objectId, $languageId, $inputData);
            }
        }

        return $rows;
    }
}
