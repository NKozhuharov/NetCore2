<?php
/**
 * This is a platform module
 * This module should be extended with something like 'SiteNews extends News', which allows configuration
 * Create the tables from the 'news.sql'
 * Drop any unnecessary columns from the `news` table (like `category_id`), if they are not used
 * Drop any unnecessary foreign keys fro the `news` table
 * Drop `news_lang` or `news_tags` tables if they are not used
 * Change the name of the created tables (if necessary), along with the tableName variable in the child class
 */
class News extends Base
{
    /**
     * Shows if tags are allowed
     * Requires the `tags` table from the Tags module
     * @var bool
     */
    protected $allowTags = true;
    
    /**
     * The name of the table, where the tags are store
     * @var string
     */
    protected $tagsTableName = 'news_tags';
    
    /**
     * Shows if categories are allowed
     * @var bool
     */
    protected $allowCategories = true;
    
    /**
     * Shows if translations are allowed
     * Requires the `news_lang` table
     * @var bool
     */
    protected $allowMultilanguage = true;
    
    /**
     * Creates a new instance of News class
     */
    public function __construct()
    {
        $this->tableName    = 'news';
        $this->orderByField = 'added';
        $this->orderByType  = self::ORDER_DESC;
        
        if ($this->allowMultilanguage) {
            $this->translationFields = array('title', 'description', 'short_description', 'link');
        }
    }
    
    /**
     * Add the tags to the selector when the tags are allowed
     * @param BaseSelect $selector - the selector from getAll
     * @return BaseSelect
     */
    public function getAllSelectHook(BaseSelect $selector)
    {
        if ($this->allowTags) {
            $selector->addField(
                "(SELECT GROUP_CONCAT(`tag_id` ORDER BY `id`) FROM `{$this->tagsTableName}` WHERE `news_id` = `news`.`id`)", 
                'tags'
            );
        }
        return $selector;
    }
    
    /**
     * Add the tags to the selector when the tags are allowed
     * @param BaseSelect $selector - the selector from getById
     * @return BaseSelect
     */
    public function getByIdSelectHook(BaseSelect $selector)
    {
        if ($this->allowTags) {
            $selector->addField("
                (SELECT GROUP_CONCAT(`tag_id` ORDER BY `id`) FROM `{$this->tagsTableName}` WHERE `news_id` = `news`.`id`)", 
                'tags'
            );
        }
        
        return $selector;
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
     * Gets the latest news. By default 6
     * @return array
     */
    public function getLatestNews(int $count = null)
    {
        if ($count === null) {
            $count = 6;
        }
        
        return $this->getAll($count);
    }
    
    //SEARCH
    
    /**
     * Gets the language part of a search query where clause
     * Throws Exception if languages are not allowed, or the provided language id is empty
     * 
     * @param int $languageId - the id of a language to search by
     * @return string
     * @throws Exception
     */
    private function getLanguageSearchPart(int $languageId = null)
    {
        if ($languageId !== null) {
            if ($this->allowMultilanguage === false) {
                throw new Exception("Translations are not allowed");
            }
            
            $languageId = intval($languageId);
            
            if (empty($languageId)) {
                throw new Exception("Language cannot be empty");
            }
            
            return "`{$this->tableName}_lang`.`lang_id` = $languageId";
        }
        
        return '';
    }
    
    /**
     * Gets the category part of a search query where clause
     * Throws Exception if categories are not allowed, or the provided category id is empty
     * 
     * @param int $categoryId - the id of a category to search by
     * @return string
     * @throws Exception
     */
    private function getCategorySearchPart(int $categoryId = null)
    {
        if ($categoryId !== null) {
            if ($this->allowCategories === false) {
                throw new Exception("Categories are not allowed");
            }
            
            $categoryId = intval($categoryId);
            
            if (empty($categoryId)) {
                throw new Exception("Category cannot be empty");
            }
            
            return "`{$this->tableName}`.`category_id` = $categoryId";
        }
        
        return '';
    }
    
    /**
     * Gets the tag part of a search query where clause
     * Throws Exception if tags are not allowed, or the provided tag id is empty
     * 
     * @param int $tagId - the id of a tag to search by
     * @return string
     * @throws Exception
     */
    private function getTagSearchPart(int $tagId = null)
    {
        if ($tagId != null) {
            if ($this->allowTags === false) {
                throw new Exception("Tags are not allowed");
            }
            
            $tagId = intval($tagId);
            
            if (empty($tagId)) {
                throw new Exception("Tag cannot be empty");
            }
            
            $newsIds = $this->getNewsIdsByTagId($tagId);
            
            if (!empty($newsIds)) {
                $newsIds = implode(', ', $newsIds);
                return "`{$this->tableName}`.`id` IN ($newsIds)";
            }
            
        }
        
        return '';
    }
    
    /**
     * Gets the phrase part of a search query where clause
     * 
     * @param string $phrase - a phrase to search by
     * @return string
     */
    private function getPhraseSearchPart(string $phrase = null)
    {
        global $Core;
        
        if ($phrase !== null && !empty($phrase)) {
            $phrase = $Core->db->real_escape_string($phrase);
            $wherePhrase  = "`{$this->tableName}`.`title` LIKE '%$phrase%'";
		    $wherePhrase .= " OR `{$this->tableName}`.`short_description` LIKE '%$phrase%'";
            $wherePhrase .= " OR `{$this->tableName}`.`description` LIKE '%$phrase%'";
            
            if ($this->allowMultilanguage) {
                $wherePhrase .= " OR `{$this->tableName}_lang`.`title` LIKE '%$phrase%'";
        		$wherePhrase .= " OR `{$this->tableName}_lang`.`short_description` LIKE '%$phrase%'";
        		$wherePhrase .= " OR `{$this->tableName}_lang`.`description` LIKE '%$phrase%'";
            }
            
            return $wherePhrase;
        }
    }
    
    /**
     * Gets the search query where clause, when searching in the news, 
     * using phrase, language id, category id and tag id
     * 
     * @param string $phrase - a phrase to search by
     * @param int $languageId - the id of a language to search by
     * @param int $categoryId - the id of a category to search by
     * @param int $tagId - the id of a tag to search by
     * @return string
     */
    private function getSearchQueryWhere(string $phrase = null, int $languageId = null, int $categoryId = null, int $tagId = null)
    {
        $where = array();
        
        $where[] = $this->getLanguageSearchPart($languageId);
        
        $where[] = $this->getCategorySearchPart($categoryId);
        
        $tagsWhere = $this->getTagSearchPart($tagId);
        if (empty($tagsWhere) && !empty($tagId)) {
            return array();
        }
        $where[] = $tagsWhere;
        unset($tagsWhere);
        
        
        $where[] = $this->getPhraseSearchPart($phrase);
        
        return implode(" AND ", array_filter($where));
    }
    
    /**
     * Allows to search in the news, using phrase, language id, category id and tag id
     * Returns an array of 'News' ids
     * 
     * @param string $phrase - a phrase to search by
     * @param int $languageId - the id of a language to search by
     * @param int $categoryId - the id of a category to search by
     * @param int $tagId - the id of a tag to search by
     * @return array
     */
    public function searchForNewsIds(string $phrase = null, int $languageId = null, int $categoryId = null, int $tagId = null)
    {
        $selector = new BaseSelect($this->tableName);
        $selector->addField('id');
        
        if ($this->allowMultilanguage) {
            $selector->addLeftJoin("{$this->tableName}_lang", 'object_id', 'id', $this->tableName);
        }
        
        $selector->setWhere($this->getSearchQueryWhere($phrase, $languageId, $categoryId, $tagId));
        
        $selector->dumpString(false);
        
        return $selector->execute();
    }
    
    /**
     * Allows to search in the news, using phrase, language id, category id and tag id
     * Returns whole 'News' objects
     * 
     * @param string $phrase - a phrase to search by
     * @param int $languageId - the id of a language to search by
     * @param int $categoryId - the id of a category to search by
     * @param int $tagId - the id of a tag to search by
     * @return array
     */
    public function search(string $phrase = null, int $languageId = null, int $categoryId = null, int $tagId = null)
    {
        $newsIds = $this->searchForNewsIds($phrase, $languageId, $categoryId, $tagId);
        
        if (!empty($newsIds)) {
            $newsIds = implode(',', array_column($newsIds, 'id'));
            return $this->getAll(false, " `id` IN ($newsIds)");
        }
        
        return array();
    }
    
    /**
     * Allows to get how many news match a search in the news, using phrase, language id, category id and tag id
     * 
     * @param string $phrase - a phrase to search by
     * @param int $languageId - the id of a language to search by
     * @param int $categoryId - the id of a category to search by
     * @param int $tagId - the id of a tag to search by
     * @return int
     */
    public function searchCount(string $phrase = null, int $languageId = null, int $categoryId = null, int $tagId = null)
    {
        return count($this->searchForNewsIds($phrase, $languageId, $categoryId, $tagId));
    }
    
    //LANGUAGES
    
    /**
     * Gets a collection of news ids with the provided language id
     * @param int $languageId - the id of the language
     * @return array
     */
    private function getNewsIdsByLangId(int $languageId)
    {
        $selector = new BaseSelect("{$this->tableName}_lang");
        $selector->addField("object_id");
        $selector->setWhere("`lang_id` = $languageId");
        $newsIds = $selector->execute();
        if (!empty($newsIds)) {
            return array_column($newsIds, 'object_id');
        }
        
        return array();
    }
    
    //TAGS
    
    /**
     * Gets a collection of news ids with the provided tag
     * @param int $tagId - the id of a tag
     * @return array
     */
    private function getNewsIdsByTagId(int $tagId)
    {
        $selector = new BaseSelect($this->tagsTableName);
        $selector->addField("news_id");
        $selector->setWhere("`tag_id` = $tagId");
        $newsIds = $selector->execute();
        if (!empty($newsIds)) {
            return array_column($newsIds, 'news_id');
        }
        
        return array();
    }
    
    /**
     * Inserts tag for the specified news
     * Throws Exception if any of the tag ids is empty or not numeric value
     * Throws Exception if tags are not allowed
     * @param int $objectId - the id of the a news
     * @param array $tagIds - the ids of the tags to insert (must be ints)
     * @throws Exception
     */
    public function insertTags(int $objectId, array $tagIds)
    {
        if ($this->allowTags === false) {
            throw new Exception("Tags are not allowed");
        }
        
        foreach ($tagIds as $tagId) {
            if (empty($tagId) || !is_numeric($tagId)) {
                throw new Exception("Tag id must be a non-empty integer");
            }
            
            $tagId = intval($tagId);
            if (empty($tagId)) {
                throw new Exception("Tag id must be a non-empty integer");
            }
            
            $inserter = new BaseInsert($this->tagsTableName);
            $inserter->addField('news_id', $objectId);
            $inserter->addField('tag_id', $tagId);
            $inserter->execute();
            unset($inserter);
        }
    }
    
    /**
     * Deletes all tags of the specified news
     * Throws Exception if tags are not allowed
     * @param int $objectId - the id of the a news
     * @throws Exception
     */
    public function deleteTags(int $objectId)
    {
        if ($this->allowTags === false) {
            throw new Exception("Tags are not allowed");
        }
        
        $deleter = new BaseDelete($this->tagsTableName);
        $deleter->setWhere("`news_id` = $objectId");
        $deleter->execute();
    }
}
