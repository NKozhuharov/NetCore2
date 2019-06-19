<?php
/**
 * This is a platform module
 * This module should be extended with something like 'SiteCategories extends Categories', which allows configuration
 * Create the tables from the 'categories.sql'
 * Drop `categories_lang` table if it is not used
 * Change the name of the created tables (if necessary), along with the tableName variable in the child class
 */
class Categories extends Base
{
    /**
     * Shows if translations are allowed for the categories
     * Requires the `categories_lang` table
     * @var bool
     */
    protected $allowMultilanguage = true;
    
    /**
     * Creates a new instance of Categories class
     */
    public function __construct()
    {
        $this->tableName    = 'categories';
        $this->orderByField = 'position';
        
        if ($this->allowMultilanguage) {
            $this->translationFields = array('name', 'link');
        } else {
            $this->translateResult = false;
        }
    }
    
    /**
     * Override the base function to add automatic generation of links
     * @param array $input - it must contain the field names => values
     * @param int $flag - used for insert ignore and insert on duplicate key update queries
     * @return int
     * @throws Exception
     */
    public function insert(array $input, int $flag = null)
    {
        global $Core;
        
        if (!isset($input['link']) || empty($input['link'])) {
            $input['link'] = $Core->GlobalFunctions->getHref($input['name'], $this->tableName, $this->linkField);
        }
        
        return parent::insert($input, $flag);
    }
    
    /**
     * Override the base function to add automatic generation of links
     * @param int $objectId - the id of the row where the translated object is
     * @param int $languageId - the language id to translate to
     * @param array $input - the translated object data
     * @return int
     * @throws Exception
     */
    public function translate(int $objectId, int $languageId, array $input)
    {
        global $Core;
        
        if (!isset($input['link']) || empty($input['link'])) {
            $input['link'] = $Core->GlobalFunctions->getHref($input['name'], "{$this->tableName}_lang", $this->linkField);
        }
        
        return parent::translate($objectId, $languageId, $input);
    }
    
    /**
     * Override the base function to add automatic generation of links
     * @param int $objectId - the id of the row to be updated
     * @param array $input - it must contain the field names => values
     * @param string $additional - the where clause override
     * @throws Exception
     * @return int
     */
    public function updateById(int $objectId, array $input, string $additional = null)
    {
        global $Core;
        
        if (!isset($input['link']) || empty($input['link'])) {
            $input['link'] = $Core->GlobalFunctions->getHref($input['name'], $this->tableName, $this->linkField);
        }
        
        return parent::updateById($objectId, $input, $additional);
    }
}
