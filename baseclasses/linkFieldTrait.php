<?php
trait LinkField
{
    /**
     * @var string
     * Use this field to specify in which column of the table for this model is the link
     */
    protected $linkField = 'link';
    
    private function validateLinkField()
    {
        global $Core;
        
        if (empty($this->linkField)) {
            throw new Exception("You cannot use links functions, wihout specifing the linkField");
        }

        $this->checkTableFields();

        if ($this->tableFields->getFieldType($this->linkField) !== 'varchar') {
            throw new Exception(
                "The specified linkField `{$this->linkField}` in table `{$this->tableName}`".
                " must be type 'varchar'"
            );
        }
        
        if ($Core->multiLanguageLinks === true) {
            $langTableFields = new BaseTableFields("{$this->tableName}_lang");
    
            if ($langTableFields->getFieldType($this->linkField) !== 'varchar') {
                throw new Exception(
                    "The specified linkField `{$this->linkField}` in table `{$this->tableName}_lang`".
                    " must be type 'varchar'"
                );
            }
        }
    }
    
    private function getLinkByIdIfLinkFieldIsPresentInObject(array $object, int $lanugageId)
    {
        global $Core;
        
        if ($lanugageId !== $Core->Language->getDefaultLanguageId()) {
            if (!in_array($this->linkField, $this->translationFields, true)) {
                $this->translationFields[$this->linkField] = $this->linkField;
                $removeLinkField = true;
            }

            $translatedObject = $this->getTranslation($object, $lanugageId);

            if ($translatedObject[$this->linkField] === $object[$this->linkField]) {
                return $Core->Links->getLink(strtolower(get_class($this)), false, $lanugageId);
            }

            $object = $translatedObject;

            if (isset($removeLinkField)) {
                unset($this->translationFields[$this->linkField]);
            }
        }

        try {
            return $Core->Links->getLink(strtolower(get_class($this)), '/'.mb_strtolower($object[$this->linkField]), $lanugageId);
        } catch (Exception $ex) {
            if (strstr($ex->getMessage(), 'The following controller was not found')) {
                return '/'.mb_strtolower($object[$this->linkField]);
            }
            throw new Exception ($ex->getMessage());
        }
    }
    
    /**
     * WORK IN PROGRESS
     * @todo
     */
    public function getObjectByLink(string $link)
    {
        global $Core;
        
        $this->validateLinkField();

        $link = mb_strtolower($Core->db->real_escape_string($link));

        if (
            $Core->multiLanguageLinks === true &&
            $Core->Language->getCurrentLanguageId() !== $Core->Language->getDefaultLanguageId()
        ) {
            $selector = new BaseSelect("{$this->tableName}_lang");
            $selector->addField('object_id');
            $selector->setWhere("LOWER(`{$this->linkField}`) = '$link' AND `lang_id` = ".$Core->Language->getCurrentLanguageId());
            $selector->setLimit(1);
            $selector->setGlobalTemplate('fetch_assoc');
            $objectId = $selector->execute();

            if (!empty($objectId)) {
                return $this->getById($objectId['object_id']);
            }
        } else {
            $object = $this->getAll(1, "LOWER(`{$this->linkField}`) = '$link'");
            if (!empty($object)) {
                return current($object);
            }
        }

        return array();
    }

    /**
     * WORK IN PROGRESS
     * @todo
     */
    public function getLinkById(int $rowId, int $lanugageId)
    {
        global $Core;

        $this->validateLinkField();

        $object = $this->getByIdWithoutTranslation($rowId);

        if (empty($object)) {
            throw new Exception("Cannot get link for non existing object - ".get_class($this).", $rowId");
        }

        if (isset($object[$this->linkField])) {
            return $this->getLinkByIdIfLinkFieldIsPresentInObject($object, $lanugageId);
        }

        try {
            return $Core->Links->getLink(strtolower(get_class($this)), '/'.$object['id'], $lanugageId);
        } catch (Exception $ex) {
            if (strstr($ex->getMessage(), 'The following controller was not found')) {
                return '/'.$object['id'];
            }
            throw new Exception ($ex->getMessage());
        }
    }
}
