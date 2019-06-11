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
        }
    }
}
