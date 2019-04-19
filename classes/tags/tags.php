<?php
class Tags extends Base
{
    use InputAndTranslate;
    
    /**
     * Creates an instance of Tags class
     */
    public function __construct()
    {
        $this->tableName = 'tags';
        $this->orderByField = 'id';
        $this->orderByType = self::ORDER_DESC;
        $this->translationFields = array('name');
        $this->turnOffTranslation();
        $this->searchByField = 'name';
    }

    /**
     * Override the hook, to add the translations of all tags for all languages
     * @param BaseSelect $selector - the selector from getCount
     * @return BaseSelect
     */
    public function getCountSelectHook(BaseSelect $selector)
    {
        global $Core;

        $activeLanguageIds = $Core->Language->getActiveLanguagesIds();
        
        foreach ($activeLanguageIds as $languageId) {
            if ($languageId !== $Core->Language->getDefaultLanguageId()) {
                $selector->addLeftJoin(
                    "{$this->tableName}_lang",
                    'object_id',
                    'id',
                    null,
                    "lang_{$languageId}",
                    "AND `{$Core->dbName}`.`lang_{$languageId}`.`lang_id` = {$languageId}"
                );
            }
        }

        return $selector;
    }

    /**
     * Override the hook, to add the translations of all tags for all languages
     * @param BaseSelect $selector - the selector from getAll
     * @return BaseSelect
     */
    public function getAllSelectHook(BaseSelect $selector)
    {
        global $Core;

        $activeLanguageIds = $Core->Language->getActiveLanguagesIds();
        
        $selector->removeField('*', $this->tableName);
        $selector->addField('id', 'id', $this->tableName);
        $selector->addField('name', 'name', $this->tableName);
        foreach ($activeLanguageIds as $languageId) {
            if ($languageId !== $Core->Language->getDefaultLanguageId()) {
                $selector->addField('name', "name_{$languageId}", "lang_{$languageId}");
                $selector->addLeftJoin(
                    "{$this->tableName}_lang",
                    'object_id',
                    'id',
                    null,
                    "lang_{$languageId}",
                    "AND `{$Core->dbName}`.`lang_{$languageId}`.`lang_id` = {$languageId}"
                );
            }
        }

        return $selector;
    }

    /**
     * Override the hook, to add the translations of the tag for all languages
     * @param BaseSelect $selector - the selector from getById
     * @return BaseSelect
     */
    public function getByIdSelectHook(BaseSelect $selector)
    {
        global $Core;

        $activeLanguageIds = $Core->Language->getActiveLanguagesIds();
        
        $selector->removeField('*', $this->tableName);
        $selector->addField('id', 'id', $this->tableName);
        $selector->addField('name', 'name', $this->tableName);
        foreach ($activeLanguageIds as $languageId) {
            if ($languageId !== $Core->Language->getDefaultLanguageId()) {
                $selector->addField('name', "name_{$languageId}", "lang_{$languageId}");
                $selector->addLeftJoin(
                    "{$this->tableName}_lang",
                    'object_id',
                    'id',
                    null,
                    "lang_{$languageId}",
                    "AND `{$Core->dbName}`.`lang_{$languageId}`.`lang_id` = {$languageId}"
                );
            }
        }

        return $selector;
    }
    
    /**
     * Gets the tag rows, as objects for an autocomplete.
     * Searches using RIGHT match in all languages
     * @param string $phrase - the phrase to search for
     * @param array $existingTagIds - it will skip theese tag ids (optional)
     * @return array
     */
    public function getAutocomplete(string $phrase, array $existingTagIds = null)
    {
        global $Core;
        
        $phrase = urldecode($phrase);
    
        $additional = "`tags`.`name` LIKE '{$phrase}%'";
    
        foreach ($Core->Language->getActiveLanguages() as $languageKey => $language) {
            if (is_numeric($languageKey) && $languageKey != $Core->Language->getDefaultLanguageId()) {
                $additional .= " OR `{$Core->dbName}`.`lang_{$languageKey}`.`name` LIKE '{$phrase}%'";
            }
        }
        
        if (!empty($existingTagIds)) {
            $additional = "`tags`.`id` NOT IN (".implode(',', $existingTagIds).") AND ($additional)";
        }
        
        return $Core->Tags->getAll(null, $additional);
    }
    
    /**
     * Overrides the method, to add search for the tags in all active languages.
     * This is applied, if $additional parameter is empty
     * @param string $phrase - the phrase to search for
     * @param int $limit - the limit override
     * @param string $additional - the addition to the WHERE clause (if any)
     * @param string $orderBy - the order by override
     * @return array
     */
    public function searchFull(string $phrase, int $limit = null, string $additional = null, string $orderBy = null)
    {
        global $Core;
        
        $phrase = urldecode($phrase);
        
        if (empty($additional)) {
            $additional = '';
            foreach ($Core->Language->getActiveLanguages() as $languageKey => $language) {
                if (is_numeric($languageKey) && $languageKey != $Core->Language->getDefaultLanguageId()) {
                    $additional .= " OR `{$Core->dbName}`.`lang_{$languageKey}`.`name` LIKE '%{$_REQUEST['phrase']}%'";
                }
            }
        }
        
        return parent::searchFull($_REQUEST['phrase'], $limit, $additional, $orderBy);
    }
}
