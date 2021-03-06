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
     * The name of the table. THIS IS REQUIRED!!!
     * @var string
     */
    protected $tableName = 'news';

    /**
     * Allows the user to set a default ordering for the select queries
     * This is the order type
     * @var string
     */
    protected $orderByType  = self::ORDER_DESC;

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
     * The table fields to search in if a phrase to search by is present
     * @var array
     */
    protected $searchFields = array('title', 'short_description', 'description');

     /**
     * Fields in the table, which has translations in the {table}_lang
     * @var array
     */
    protected $translationFields = array('title', 'description', 'short_description', 'link');

    /**
     * Fields in the child tables, which has news_id
     * @var string
     */
    protected $newsField = 'news_id';

    /**
     * Fields in the child tables, which has tag_id
     * @var string
     */
    protected $tagsField = 'tag_id';

    /**
     * Creates a new instance of News class
     */
    public function __construct()
    {
        if (!$this->allowMultilanguage) {
            $this->translateResult = false;
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
                "(SELECT GROUP_CONCAT(`{$this->tagsField}` ORDER BY `id`) FROM `{$this->tagsTableName}` WHERE `{$this->newsField}` = `{$this->tableName}`.`id`)",
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
            $selector->addField(
                "(SELECT GROUP_CONCAT(`{$this->tagsField}` ORDER BY `id`) FROM `{$this->tagsTableName}` WHERE `{$this->newsField}` = `{$this->tableName}`.`id`)",
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
     * @throws Error
     */
    public function insert(array $input, int $flag = null)
    {
        global $Core;

        if (
            $this->linkField
            && (!isset($input['link']) || empty($input['link']))
            && isset($input['title'])
            && !empty($input['title'])
        ) {
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
     * @throws Error
     */
    public function translate(int $objectId, int $languageId, array $input)
    {
        global $Core;

        if (
            $this->linkField
            && (!isset($input['link']) || empty($input['link']))
            && isset($input['title'])
            && !empty($input['title'])
        ) {
            $input['link'] = $Core->GlobalFunctions->getHref($input['title'], "{$this->tableName}_lang", $this->linkField);
        }

        return parent::translate($objectId, $languageId, $input);
    }

    /**
     * Override the base function to add automatic generation of links
     * @param int $objectId - the id of the row to be updated
     * @param array $input - it must contain the field names => values
     * @param string $additional - the where clause override
     * @throws Error
     * @return int
     */
    public function updateById(int $objectId, array $input, string $additional = null)
    {
        global $Core;

        if (
            $this->linkField
            && (!isset($input['link']) || empty($input['link']))
            && isset($input['title'])
            && !empty($input['title'])
        ) {
            $input['link'] = $Core->GlobalFunctions->getHref($input['title'], $this->tableName, $this->linkField, $objectId);
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
     * Throws Error if languages are not allowed, or the provided language id is empty
     *
     * @param int $languageId - the id of a language to search by
     * @return string
     * @throws Error
     */
    protected function getLanguageSearchPart(int $languageId = null)
    {
        if ($languageId !== null) {
            if ($this->allowMultilanguage === false) {
                throw new Error("Translations are not allowed");
            }

            $languageId = intval($languageId);

            if (empty($languageId)) {
                throw new Error("Language cannot be empty");
            }

            return "`{$this->tableName}_lang`.`lang_id` = {$languageId}";
        }

        return '';
    }

    /**
     * Gets the category part of a search query where clause
     * Throws Error if categories are not allowed, or the provided category id is empty
     *
     * @param int $categoryId - the id of a category to search by
     * @return string
     * @throws Error
     */
    protected function getCategorySearchPart(int $categoryId = null)
    {
        if ($categoryId !== null) {
            if ($this->allowCategories === false) {
                throw new Error("Categories are not allowed");
            }

            $categoryId = intval($categoryId);

            if (empty($categoryId)) {
                throw new Error("Category cannot be empty");
            }

            return "`{$this->tableName}`.`category_id` = {$categoryId}";
        }

        return '';
    }

    /**
     * Gets the tag part of a search query where clause
     * Throws Error if tags are not allowed, or the provided tag id is empty
     *
     * @param int $tagId - the id of a tag to search by
     * @return string
     * @throws Error
     */
    protected function getTagSearchPart(int $tagId = null)
    {
        if ($tagId != null) {
            if ($this->allowTags === false) {
                throw new Error("Tags are not allowed");
            }

            $tagId = intval($tagId);

            if (empty($tagId)) {
                throw new Error("Tag cannot be empty");
            }

            $newsIds = $this->getNewsIdsByTagId($tagId);

            if (!empty($newsIds)) {
                $newsIds = implode(', ', $newsIds);
                return "`{$this->tableName}`.`id` IN ({$newsIds})";
            } else {
                return "`{$this->tableName}`.`id` = 0";
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
    protected function getPhraseSearchPart(string $phrase = null)
    {
        global $Core;

        if ($phrase !== null && !empty($phrase)) {
            $phrase = $Core->db->real_escape_string($phrase);

            $wherePhrase = '';

            foreach ($this->searchFields as $searchField) {
                $wherePhrase .= " OR `{$this->tableName}`.`{$searchField}` LIKE '%$phrase%'";
            }

            if ($this->allowMultilanguage) {
                foreach ($this->searchFields as $searchField) {
                    $wherePhrase .= " OR `{$this->tableName}_lang`.`{$searchField}` LIKE '%$phrase%'";
                }
            }

            $wherePhrase = substr($wherePhrase, 4);

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
    protected function getSearchQueryWhere(string $phrase = null, int $languageId = null, int $categoryId = null, int $tagId = null)
    {
        $where = array();

        $where[] = $this->getCategorySearchPart($categoryId);

        $where[] = $this->getTagSearchPart($tagId);

        $where[] = $this->getLanguageSearchPart($languageId);

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
            return $this->getAll(null, " `id` IN ($newsIds)");
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
    protected function getNewsIdsByLangId(int $languageId)
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
    protected function getNewsIdsByTagId(int $tagId)
    {
        $selector = new BaseSelect($this->tagsTableName);
        $selector->addField($this->newsField);
        $selector->setWhere("`{$this->tagsField}` = {$tagId}");
        $newsIds = $selector->execute();

        if (!empty($newsIds)) {
            return array_column($newsIds, $this->newsField);
        }

        return array();
    }

    /**
     * Inserts tag for the specified news
     * Throws Error if any of the tag ids is empty or not numeric value
     * Throws Error if tags are not allowed
     * @param int $objectId - the id of the a news
     * @param array $tagIds - the ids of the tags to insert (must be ints)
     * @throws Error
     */
    public function insertTags(int $objectId, array $tagIds)
    {
        if ($this->allowTags === false) {
            throw new Error("Tags are not allowed");
        }

        foreach ($tagIds as $tagId) {
            if (empty($tagId) || !is_numeric($tagId)) {
                throw new Error("Tag id must be a non-empty integer");
            }

            $tagId = intval($tagId);
            if (empty($tagId)) {
                throw new Error("Tag id must be a non-empty integer");
            }

            $inserter = new BaseInsert($this->tagsTableName);
            $inserter->addField($this->newsField, $objectId);
            $inserter->addField($this->tagsField, $tagId);
            $inserter->execute();
            unset($inserter);
        }
    }

    /**
     * Deletes all tags of the specified news
     * Throws Error if tags are not allowed
     * @param int $objectId - the id of the a news
     * @throws Error
     */
    public function deleteTags(int $objectId)
    {
        if ($this->allowTags === false) {
            throw new Error("Tags are not allowed");
        }

        $deleter = new BaseDelete($this->tagsTableName);
        $deleter->setWhere("`{$this->newsField}` = {$objectId}");
        $deleter->execute();
    }
}
