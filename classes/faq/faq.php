<?php
/**
 * This is a platform module
 * This module can be extended with something like 'SiteFAQ extends FAQ', which allows configuration
 * Create the tables from the 'faq.sql'
 * Drop `faq_lang` table if it's not used
 */
class FAQ extends Base
{
    /**
     * Shows if translations are allowed
     * Requires the `news_lang` table
     * @var bool
     */
    protected $allowMultilanguage = true;
    
    /**
     * Creates a new instance of FAQ class
     */
    public function __construct()
    {
        $this->tableName    = 'faq';
        $this->orderByField = 'order';
        $this->orderByType  = self::ORDER_ASC;
        
        if ($this->allowMultilanguage) {
            $this->translationFields = array('title', 'body');
        } else {
            $this->translateResult = false;
        }
    }
    
    //testing git
}
