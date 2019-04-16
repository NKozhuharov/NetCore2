<?php
trait InputAndTranslate
{
    private function checkInputForMissingLanguages(array $input)
    {
        global $Core;
        
        $activeLanguagesIds = array();
        foreach ($Core->Language->getActiveLanguages() as $languageKey => $language) {
            if (is_numeric($languageKey)) {
                $activeLanguagesIds[] = $languageKey;
            }
        }
        
        foreach ($activeLanguagesIds as $activeLanguagesId) {
            if (!isset($input[$activeLanguagesId])) {
                throw new Exception("The input must have all data for all active languages! Missing - {$activeLanguagesId}");
            }
        }
    }
    
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
    
    public function insertAndTranslate(array $input)
    {
        global $Core;
        
        $this->checkIfTranslationFieldsAreSet();
        
        $this->checkInputForMissingLanguages($input);
        
        $this->checkInputForMissingAndEmptyTranslationFields($input);
        
        $tagId = $this->insert($input[$Core->Language->getDefaultLanguageId()]);
        
        foreach ($input as $languageId => $inputData) {
            if ($languageId != $Core->Language->getDefaultLanguageId()) {
                $this->translate($tagId, $languageId, $inputData);
            }
        }
    }
    
    public function updateByIdAndTranslate(int $objectId, array $input)
    {
        global $Core;
        
        $this->checkIfTranslationFieldsAreSet();
        
        $this->validateObjectId($objectId);
        
        $this->checkInputForMissingLanguages($input);
        
        $this->checkInputForMissingAndEmptyTranslationFields($input);
        
        $this->updateById($objectId, $input[$Core->Language->getDefaultLanguageId()]);
        
        foreach ($input as $languageId => $inputData) {
            if ($languageId != $Core->Language->getDefaultLanguageId()) {
                $this->translate($objectId, $languageId, $inputData);
            }
        }
    }
}