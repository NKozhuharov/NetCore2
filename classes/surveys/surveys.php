<?php
/**
 * 
 */
class Surveys extends Base
{
    /**
     * Shows if translations are allowed
     * Requires the `categories_lang` table
     * @var bool
     */
    protected $allowMultilanguage = true;
    
    /**
     * Creates a new instance of Categories class
     */
    public function __construct()
    {
        $this->tableName       = 'surveys';
        $this->hiddenFieldName = 'expired';
        
        if ($this->allowMultilanguage) {
            $this->translationFields = array('title', 'link');
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
     * Override the function to forbid it
     * @param int $objectId - the id of the row to be updated
     * @param array $input - it must contain the field names => values
     * @param string $additional - the where clause override
     * @throws Exception
     */
    public function update(array $input, string $additional)
    {
        throw new Exception("Basic update is forbidden");
    }
    
    /**
     * Override the function to forbid it
     * @param string $additional - the where clause override
     * @throws Exception
     * @return int
     */
    public function updateAll(array $input)
    {
        throw new Exception("Basic update is forbidden");
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
        
        $survey = $this->getById($objectId);
        if (!empty($survey['published_on'])) {
            throw new Exception("Cannot update published surveys!");
        }
        
        if (!isset($input['link']) || empty($input['link'])) {
            $input['link'] = $Core->GlobalFunctions->getHref($input['title'], $this->tableName, $this->linkField);
        }
        
        return parent::updateById($objectId, $input, $additional);
    }
    
    /**
     * Override the function to forbid deletion
     * Returns the number of deleted rows
     * @param string $additional - the where clause override
     * @throws Exception
     */
    public function delete(string $additional)
    {
        throw new Exception("Delete is forbidden");
    }

    /**
     * Override the function to forbid deletion
     * @throws Exception
     */
    public function deleteAll()
    {
        throw new Exception("Delete is forbidden");
    }

    /**
     * Override the function to forbid deletion
     * @param int $id - the id of the row
     * @throws Exception
     */
    public function deleteById(int $rowId)
    {
        throw new Exception("Delete is forbidden");
    }
    
    private function ensureSurveyExists(int $objectId)
    {
        $survey = $this->getById($objectId);
        
        if (empty($survey)) {
            throw new Exception("This survey does not exist");
        }
    }
    
    public function setToExpired(int $objectId)
    {
        $this->ensureSurveyExists($objectId);
        
        $this->updateById($objectId, array('expired' => 1));
    }
    
    public function publish(int $objectId)
    {
        global $Core;
        
        $this->ensureSurveyExists($objectId);
        
        $this->updateById($objectId, array('published_on' => $Core->GlobalFunctions->formatMysqlTime(time(), true)));
    }
    
    public function setAllExpiredSurveysToExpired()
    {
        $this->returnTimestamps = true;
        $surveys = $this->getAll(null, " `expired` = 0 AND `expires_on` IS NOT NULL");
        
        if (!empty($surveys)) {
            foreach ($surveys as $survey) {
                if ($survey['expires_on_timestamp'] <= time()) {
                    $this->setToExpired($survey['id']);
                }
            }
        }
    }
    
}
