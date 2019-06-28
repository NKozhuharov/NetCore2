<?php
trait LinkField
{
    /**
     * @var string
     * Use this field to specify in which column of the table for this model is the link
     */
    protected $linkField = 'link';

    /**
     * Ensures the linkField property is configured correctly for the model and the database table structure
     * is setup correctly
     * Throws Exception if something is wrong
     * @throws Exception
     */
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

    /**
     * Gets a link from an object, from the table of the model, if the field defined in linkField property
     * is available in the table.
     * It will attempt to translate the object if the provided language is different from the default one
     * and return the result from the `_lang` table
     * If the current model does not have a controller defined in the multilanguage links table, it will
     * return only the value of the linkField from the table
     * Throws Exception if there is another problem while getting the link
     * @param array $object - a row from the table of the model
     * @param int $languageId - get the link in this language
     * @return string
     * @throws Exception
     * @todo check commented rows
     */
    private function getLinkByIdIfLinkFieldIsPresentInObject(array $object, int $lanugageId)
    {
        global $Core;

        if ($lanugageId !== $Core->Language->getDefaultLanguageId()) {
            if (!in_array($this->linkField, $this->translationFields, true)) {
                $this->translationFields[$this->linkField] = $this->linkField;
                $removeLinkField = true;
            }

            $translatedObject = $this->getTranslation($object, $lanugageId);

            #if ($translatedObject[$this->linkField] === $object[$this->linkField]) {
            #    return $Core->Links->getLink(mb_strtolower(get_class($this)), false, $lanugageId);
            #}

            $object = $translatedObject;

            if (isset($removeLinkField)) {
                unset($this->translationFields[$this->linkField]);
            }
        }

        try {
            return $Core->Links->getLink(mb_strtolower(get_class($this)), '/'.mb_strtolower($object[$this->linkField]), $lanugageId);
        } catch (Exception $ex) {
            if (mb_strstr($ex->getMessage(), 'The following controller was not found')) {
                return '/'.mb_strtolower($object[$this->linkField]);
            }
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * Gets an object by it's link from the table of the model. Uses the getById method.
     * Requires a valid linkField set in the model and available in the table of the model.
     * If multilanguage links are available and current language is not the default language, it will
     * search for the link in the `_lang` table for the model.
     * @param string $link - the link of the object
     * @return array
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
            $selector->setWhere(
                "LOWER(`{$this->tableName}_lang`.`{$this->linkField}`) = '$link' AND `lang_id` = ".
                $Core->Language->getCurrentLanguageId()
            );
            $selector->setLimit(1);
            $selector->setGlobalTemplate('fetch_assoc');
            $objectId = $selector->execute();

            if (!empty($objectId)) {
                return $this->getById($objectId['object_id']);
            }
        } else {
            $object = $this->getAll(1, "LOWER(`{$this->tableName}`.`{$this->linkField}`) = '$link'");
            if (!empty($object)) {
                return current($object);
            }
        }

        return array();
    }

    /**
     * Gets the link for an object form the table of the model, using it's row id and the provided language id
     * Uses the getLinkByIdIfLinkFieldIsPresentInObject function.
     * If the current model does not have a controller defined in the multilanguage links table, it will
     * return only the id of object.
     * Throws Exception if there is another problem while getting the link
     * @param int $rowId - the id of the row
     * @param int $lanugageId - get the link in this language
     * @return string
     * @throws Exception
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
            return $Core->Links->getLink(mb_strtolower(get_class($this)), '/'.$object['id'], $lanugageId);
        } catch (Exception $ex) {
            if (mb_strstr($ex->getMessage(), 'The following controller was not found')) {
                return '/'.$object['id'];
            }
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * Creates unique link by given string compared to $this->linkField
     * @param string $string - string by which to create the link
     * @param int $objectId - current object id when updating (optional)
     * @returns string
     */
    public function getUniqueLink(string $string, int $objectId = null)
    {
        $link = $this->getLinkFromString($string);

        $additional = null;

        if ($objectId !== null) {
            $additional = " `id` != '{$objectId}' AND ";
        }

        $count = 0;

        while ($this->getAll(1, $additional."`{$this->linkField}` = '{$link}'")) {
            $count++;

            $postFix = mb_substr($link, mb_strripos($link, '-'));

            if ($count > 1) {
                $postFix = str_replace('-'.($count-1),'-'.$count, $postFix);
                $link = mb_substr($link, 0, mb_strripos($link, '-')).$postFix;
            } else {
                $link .= '-'.$count;
            }
        }

        return $link;
    }

    /**
     * Creates unique link by given string compared to $this->linkField in the translation table
     * Throws Exception if either languageId or objectId is provided and the other is not
     * @param string $string - string by which to create the link
     * @param int $languageId - current language id when updating (optional)
     * @param int $objectId - current object id when updating (optional)
     * @throws Exception
     * @returns string
     */
    public function getUniqueLinkTranslation(string $string, int $languageId = null, int $objectId = null)
    {
        if (($languageId === null && $objectId !== null) || ($languageId !== null && $objectId === null)) {
            throw new Exception("Provide both language id and object id");
        }

        try {
            $this->tableFields = new BaseTableFields("{$this->tableName}_lang");
        } catch (Exception $ex) {
            throw new Exception("Table {$this->tableName}_lang does not exist in model `".get_class($this)."`");
        }

        foreach ($this->translationFields as $translationField) {
            if (!array_key_exists($this->linkField, $this->tableFields->getFields())) {
                throw new Exception("The translation field `{$this->linkField}` does not exists in table `{$this->tableName}_lang`");
            }
        }

        $additional = '';

        if ($objectId !== null) {
            $additional = " `id` != (
                SELECT
                    `id`
                FROM
                    `{$this->tableName}_lang`
                WHERE
                    `object_id` = '{$objectId}'
                AND `lang_id` = $languageId
            )
            AND ";
        }

        $link = $this->getLinkFromString($string);

        $selector = new BaseSelect("{$this->tableName}_lang");
        $selector->setWhere($additional."`{$this->linkField}` = '{$link}'");
        $selector->setLimit(1);
        $selector->setGlobalTemplate('fetch_assoc');

        $count = 0;

        while (count($selector->execute()) > 0) {
            if ($count > 0) {
                if ($count == 1) {
                    $link .= '-1';
                } else {
                    $link = mb_substr($link, 0, (-mb_strlen($count) - 1));
                    $link .= '-'.$count;
                }
            }
            $selector->setWhere($additional."`{$this->linkField}` = '{$link}'");
            $count++;
        }

        return $link;
    }

    /**
     * Creates a link from the provided string. Replaces all special characters and spaces with dashes (-)
     *
     * @param string $string - string by which to create the link
     * @return string
     */
    private function getLinkFromString(string $string)
    {
        global $Core;

        $string = trim(preg_replace('~\P{Xan}++~u', ' ', $string));
        $string = preg_replace("~\s+~", '-', mb_strtolower($string));
        $string = mb_substr($string, 0, 200);
        $string = mb_strtolower($string);

        return $string;
    }
}
