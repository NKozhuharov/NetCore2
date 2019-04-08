<?php
class Links extends Base
{
    const MULTI_LANGUAGE_LINKS_SEPARATOR = '^';

    /**
     * @var array
     * Contains a map, between the multilanguage links and their according controllers and languages
     */
    private $multiLanguageLinksInfo;

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
     * @throws Exception
     * @return string
     */
    public function getLink(string $controllerName, string $additional = null)
    {
        global $Core;

        if ($Core->multiLanguageLinks === true) {
            $controllerName = $controllerName.self::MULTI_LANGUAGE_LINKS_SEPARATOR.$Core->Language->getCurrentLanguageId();
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
                if ($pathTranslation === substr($path, 0, strlen($pathTranslation))) {
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
}
