<?php
/**
 * This is a platform module
 * This module can be extended with something like 'SiteDynamicInfoPages extends DynamicInfoPages', which allows configuration
 * It can also be used directly, without extending it, using the default configuration.
 * Create the tables from the 'dynamic_info_pages.sql'
 * Drop `dynamic_info_pages_lang` table if it's not used
 * Some examples provided in table `dynamic_info_pages`. Delete the unnecessary ones
 */
class DynamicInfoPages extends Base
{
    /**
     * Shows if translations are allowed
     * Requires the `news_lang` table
     * @var bool
     */
    protected $allowMultilanguage = true;
    
    /**
     * Creates a new instance of DynamicInfoPages class
     */
    public function __construct()
    {
        $this->tableName    = 'dynamic_info_pages';
        $this->orderByField = 'order';
        $this->orderByType  = self::ORDER_ASC;
        
        if ($this->allowMultilanguage) {
            $this->translationFields = array('link', 'title', 'body');
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
            $input['link'] = $Core->GlobalFunctions->getHref($input['title'], $this->tableName, $this->linkField);
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
            $input['link'] = $Core->GlobalFunctions->getHref($input['title'], "{$this->tableName}_lang", $this->linkField);
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
            $input['link'] = $Core->GlobalFunctions->getHref($input['title'], $this->tableName, $this->linkField);
        }
        
        return parent::updateById($objectId, $input, $additional);
    }
    
    /**
     * Gets a page by it's name
     * @param string $name - the name of the page
     * @return array
     */
    public function getByName(string $name)
    {
        global $Core;
        
        $name = $Core->db->real_escape_string(trim($name));
        
        $page = $this->getAll(1, "`name` = '$name'");
        
        if (!empty($page)) {
            return current($page);
        }
        
        return array();
    }
}
