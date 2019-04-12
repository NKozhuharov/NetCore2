<?php
class Links extends Base
{
    const MULTI_LANGUAGE_LINKS_SEPARATOR = '^';

    /**
     * @var array
     * Contains a map, between the multilanguage links and their according controllers and languages
     */
    private $multiLanguageLinksInfo;
    
    private $languageChangeLinks = array();
    
    /**
     * Creates a new instacne of the Links class
     * If the multiLanguageLinks Core variable is set to true, it will initialize the Base variables
     * WARNING: it requires the `multilanguage_links` table to work
     *
     *    CREATE TABLE `multilanguage_links` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `controller_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          `language_id` int(10) unsigned NOT NULL,
          `path` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `language_id` (`language_id`,`controller_name`) USING BTREE,
          UNIQUE KEY `link` (`path`),
          CONSTRAINT `multilanguage_links_ibfk_1` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
     */
    public function __construct()
    {
        global $Core;

        if ($Core->multiLanguageLinks === true) {
            $this->tableName = 'multilanguage_links';
            $this->parentField = 'language_id';
            $this->getMultiLanguageLinks();
        }
    }

    /**
     * Gets the map, between the multilanguage links and their according controllers and languages
     * from the `multilanguage_links` table and initializes the multiLanguageLinksInfo property
     */
    private function getMultiLanguageLinks()
    {
        global $Core;

        $Core->db->query("
            SELECT
                CONCAT(`controller_name`,'".self::MULTI_LANGUAGE_LINKS_SEPARATOR."',`language_id`) AS 'controller',
                `path`
            FROM
                `{$Core->dbName}`.`{$this->tableName}`",
            $this->queryCacheTime,
            'fillArraySingleField',
            $this->multiLanguageLinksInfo,
            'path',
            'controller'
        );
    }

    /**
     * Another option to get a link, according to the controller
     * @param string $controllerName - the name of the controller for the link
     */
    public function __get(string $controllerName)
    {
        return $this->getLink($controllerName);
    }

    /**
     * Gets a link, according to the provided controller.
     * Throws Exception if multi-language links are available and the controller does not exist
     * in the `multilanguage_links` table
     * @param string $controllerName - the name of the controller for the link
     * @param string $additional - an additonal string to concatenate to the link (optional)
     * @param int $lanugageId - if a language id is provided here and multi-language links are available,
     * it will return the link in the provided language
     * @throws Exception
     * @return string
     */
    public function getLink(string $controllerName, string $additional = null, int $lanugageId = null)
    {
        global $Core;

        if ($Core->multiLanguageLinks === true) {
            if (empty($lanugageId)) {
                $lanugageId = $Core->Language->getCurrentLanguageId();
            }

            $controllerName = strtolower($controllerName).self::MULTI_LANGUAGE_LINKS_SEPARATOR.$lanugageId;
            $link = array_search($controllerName, $this->multiLanguageLinksInfo);

            if (empty($link)) {
                $controllerName = explode(self::MULTI_LANGUAGE_LINKS_SEPARATOR, $controllerName);
                throw new Exception (
                    "The following controller was not found in the `{$this->tableName}` table - ".
                    "{$controllerName[0]} for language id {$controllerName[1]}"
                );
            }

            return "/{$link}{$additional}";
        }

        return "/{$controllerName}{$additional}";
    }

    /**
     * Gets the link controller (and language from the `multilanguage_links` table, if multi-language links are available)
     * It will redirect to '/not-found' if a controller is not found in the `multilanguage_links` table
     * It will change the language, according to the link (if necessary)
     * @param string $path - the path from the URL, must be without the first '/'
     */
    public function parseLink(string $path)
    {
        global $Core;

        if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === '/') {
            return $path;
        }

        if ($Core->multiLanguageLinks === true) {
            foreach ($this->multiLanguageLinksInfo as $pathTranslation => $controller) {
                if (
                    $pathTranslation === $path ||
                    (
                        $pathTranslation === mb_substr($path, 0, mb_strlen($pathTranslation)) &&
                        mb_substr($path, mb_strlen($pathTranslation), 1) === '/'
                    )
                ) {
                    $controller = explode(self::MULTI_LANGUAGE_LINKS_SEPARATOR, $controller);

                    if ($controller[1] != $Core->Language->getCurrentLanguageId()) {
                        $Core->Language->changeLanguageById($controller[1]);
                    }

                    return $controller[0];
                }
            }

            return $Core->pageNotFoundLocation;
        }
        
        return $path;
    }
    
    public function setLanguageChangeLinks(array $links) 
    {
        $this->languageChangeLinks = $links;
    }
    
    public function getLanguageChangeLinks()
    {
        return $this->languageChangeLinks;
    }
    
    /**
     * 
     * 
     * @param string $modelName
     * @param int $objectId
     * @return array
     */
    public function getLanguageChangeLinksOfModel(string $modelName, int $objectId)
    {
        global $Core;
        
        if (empty($modelName)) {
            throw new Exception("Provide a model name");
        }
        
        if ($objectId <= 0) {
            throw new Exception("Provide an object id");
        }
        
        if ($Core->$modelName === false) {
            throw new Exception("Model {$modelName} does not exist!");
        }
        
        $links = array();
        
        if ($Core->multiLanguageLinks === true) {
            foreach ($Core->Language->getAllowedLanguages() as $langId => $langShort) {
                if ($langId !== $Core->Language->getCurrentLanguageId()) {
                    try {
                        $links[$langId] = $Core->$modelName->getLinkById($objectId, $langId);
                    } catch (Exception $ex) {
                        if (!strstr($ex->getMessage(), 'The following controller was not found in the `multilanguage_links` table')) {
                            throw new Exception ($ex->getMessage());
                        }
                    }
                }
            }
        }
        
        return $links;
    }
    
    public function getLanguageChangeLinksOfController(string $controllerName = null)
    {
        global $Core;
        
        $links = array();
        
        if ($Core->multiLanguageLinks === true) {
            if ($controllerName === null) {
                $controllerName = $Core->Rewrite->controller;
            }
            
            foreach ($Core->Language->getAllowedLanguages() as $langId => $langShort) {
                if ($langId !== $Core->Language->getCurrentLanguageId()) {
                    try {
                        $links[$langId] = $Core->Links->getLink($controllerName, null, $langId);
                    } catch (Exception $ex) {
                        if (!strstr($ex->getMessage(), 'The following controller was not found in the `multilanguage_links` table')) {
                            throw new Exception ($ex->getMessage());
                        }
                    }
                }
            }
        }
        
        return $links;
    }
}
