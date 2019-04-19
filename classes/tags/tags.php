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
}
