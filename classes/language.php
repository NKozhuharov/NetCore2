<?php
class Language extends Base
{
    /**
     * @var string
     * The short name of the current language
     */
    private $currentLanguage = null;

    /**
     * @var int
     * The id of the current language
     */
    private $currentLanguageId = null;

    /**
     * @var array
     * A collection of translation phrases from the `phrases` table
     */
    private $phrases = null;

    /**
     * @var array
     * A collection of long translation phrases from the `phrases_text` table
     */
    private $phrasesText = null;

    /**
     * @var array
     * A collection of platfrom translation phrases from the `phrases_platform` table
     * DO NOT EDIT THE CONTENTS OF THE TABLE!!!
     */
    private $phrasesPlatform = null;

    /**
     * @var array
     * Maps the active languages, id => name and name => id
     */
    private $activeLangMap = null;

    /**
     * @var array
     * Maps the allowed languaes id => name
     */
    private $allowedLanguages = null;

    /**
     * Creates an instance of the Language class
     * Makes a query to the databse to get a list of the allowed languages
     * Sets the PHP locale to the current language
     * Requires languages, languages_lang, phrases and phrases_text tables to work!
     * Throws Exception if no languages are allowed
     * @throws Exception
     */
    public function __construct()
    {
        global $Core;

        $this->tableName = 'languages';
        $this->translationFields = array('name');

        $Core->db->query(
            "SELECT
                `id`,
                LOWER(`short`) AS 'short'
            FROM
                `{$Core->dbName}`.`languages`
            WHERE `active` = 1",
            $this->queryCacheTime,
            'fillArraySingleField',
            $this->allowedLanguages,
            'id',
            'short'
        );

        if (!$this->allowedLanguages) {
            throw new Exception("No allowed languages");
        }

        $this->getCurrentLanguageFromRequestOrCookie();

        setlocale(LC_ALL, mb_strtolower($this->currentLanguage).'_'.mb_strtoupper($this->currentLanguage).'.utf8');
    }

    /**
     * Magic method to get a phrase from the translation tables
     * It will return the given phrase value if no translation is available
     */
    public function __get(string $phrase)
    {
        if ($this->phrases === null) {
            $this->getPhrases();
        }

        if (isset($this->phrases[$phrase])) {
            return $this->phrases[$phrase];
        } else if (isset($this->phrasesPlatform[$phrase])) {
            return $this->phrasesPlatform[$phrase];
        } else if (isset($this->phrasesText[$phrase])) {
            return $this->phrasesText[$phrase];
        }
        return $phrase;
    }

    /**
     * Gets the current language, using the request or cookie
     * If the language is in the request, it will set a cookie with it
     * After that it will redirect to the same page, removing language parameter from the url
     */
    private function getCurrentLanguageFromRequestOrCookie()
    {
        global $Core;

        if (isset($_REQUEST['language']) && in_array($_REQUEST['language'], $this->allowedLanguages)) {
            $this->currentLanguage = $_REQUEST['language'];
            $this->currentLanguageId = array_search($this->currentLanguage, $this->allowedLanguages);
            setcookie('language', $_REQUEST['language'], time()+86400, '/');

            if (isset($_SERVER['HTTP_REFERER'])) {
                $location = $_SERVER['HTTP_REFERER'];
            } elseif (isset($_SERVER['REQUEST_URI'])) {
                $location = $_SERVER['REQUEST_URI'];
            } else {
                $location = '/';
            }

            $location = str_replace('&language='.$_REQUEST['language'], '', $location);
            $location = str_replace('?language='.$_REQUEST['language'], '', $location);

            $Core->redirect($location);
        } else if (isset($_COOKIE['language']) && in_array($_COOKIE['language'], $this->allowedLanguages)) {
            $this->currentLanguage = $_COOKIE['language'];
            $this->currentLanguageId = array_search($this->currentLanguage, $this->allowedLanguages);
        } else {
            $this->currentLanguage   = $Core->defaultLanguage;
            $this->currentLanguageId = $Core->defaultLanguageId;
        }
    }

    /**
     * Gets the collection of phrases from the `phrases` and `phrases_text` tables in the databse
     */
    private function getPhrases()
    {
        global $Core;

        $Core->db->query(
            "SELECT
                `phrase`,
                IF(`".$this->currentLanguage."` IS NULL OR `".$this->currentLanguage."` = '', `".$Core->defaultLanguage."`, `".$this->currentLanguage."`) AS `translation`
            FROM
                `{$Core->dbName}`.`phrases`",
            $this->queryCacheTime,
            'fillArraySingleField',
            $this->phrases,
            'phrase',
            'translation'
        );

        $Core->db->query(
            "SELECT
                `phrase`,
                IF(`".$this->currentLanguage."` IS NULL OR `".$this->currentLanguage."` = '', `".$Core->defaultLanguage."`, `".$this->currentLanguage."`) AS `translation`
            FROM
                `{$Core->dbName}`.`phrases_text`",
            $this->queryCacheTime,
            'fillArraySingleField',
            $this->phrasesText,
            'phrase',
            'translation'
        );

        $Core->db->query(
            "SELECT
                `phrase`,
                IF(`".$this->currentLanguage."` IS NULL OR `".$this->currentLanguage."` = '', `".$Core->defaultLanguage."`, `".$this->currentLanguage."`) AS `translation`
            FROM
                `{$Core->dbName}`.`phrases_platform`",
            $this->queryCacheTime,
            'fillArraySingleField',
            $this->phrasesPlatform,
            'phrase',
            'translation'
        );
    }

    /**
     * Checks if the current language is the default language
     * @return bool
     */
    public function isCurrentLanguageDefaultLanguage()
    {
        global $Core;

        return $this->currentLanguageId == $Core->defaultLanguageId;
    }

    /**
     * Allows the user to manually change the current language by it's short
     * @param string $language - the short name of the language
     */
    public function changeLanguage(string $language)
    {
        if (in_array(strtolower($language), $this->allowedLanguages)) {
            $this->currentLanguage = $language;
            $this->currentLanguageId = $this->getLanguageMap()[$this->currentLanguage]['id'];
            setcookie('language', $language, time()+86400, '/');
            $this->getPhrases();
        } else {
            throw new Exception("This language does not exist or not allowed");
        }
    }

    /**
     * Allows the user to manually change the current language by its id
     * @param int $languageId - the id name of the language
     */
    public function changeLanguageById(int $languageId)
    {
        if (array_key_exists($languageId, $this->allowedLanguages)) {
            $language = $this->getLanguageMap()[$languageId]['short'];
            $this->currentLanguage = $language;
            $this->currentLanguageId = $languageId;
            setcookie('language', $language, time()+86400, '/');
            $this->getPhrases();
        } else {
            throw new Exception("This language does not exist or not allowed");
        }
    }

    /**
     * Gets a map of all languages
     * @param bool $onlyActive - if set to false, it will return all languages; otherwise it will return only the active
     * @return array
     */
    public function getLanguageMap(bool $onlyActive = null)
    {
        global $Core;

        if (!empty($this->activeLangMap) && ($onlyActive === true || $onlyActive === null)) {
            return $this->activeLangMap;
        }

        $Core->db->query(
            "SELECT
                `id`,
                `name`,
                `native_name`,
                `short`,
                LOWER(`short`) AS 'lower'
            FROM
                `{$Core->dbName}`.`languages`"
            .(($onlyActive) ? "WHERE `active` = 1" : ''),
            $this->queryCacheTime,
            'fillArray',
            $langMap
        );

        if (empty($langMap)) {
            if ($onlyActive) {
                throw new Exception("No active languages");
            } else {
                throw new Exception("No languages");
            }
        }

        $result = array();
        foreach ($langMap as $lang) {
            $result[$lang['id']] = $lang;
            $result[$lang['short']] = $lang;
            $result[$lang['lower']] = $lang;
            if(empty($this->currentLanguageId) && $lang['lower'] == $this->currentLanguage){
                $this->currentLanguageId = $lang['id'];
            }
        }
        unset($langMap, $lang);
        if ($onlyActive) {
            $this->activeLangMap = $result;
        }
        return $result;
    }

    /**
     * Gets a map of all active languages
     * @return array
     */
    public function getActiveLanguages()
    {
        if (empty($this->activeLangMap)) {
            return $this->getLanguageMap(true);
        }

        return $this->activeLangMap;
    }

    /**
     * Gets an array with the ids of all active languages
     * @return array
     */
    public function getActiveLanguagesIds()
    {
        $activeLanguagesIds = array();

        foreach ($this->getActiveLanguages() as $languageKey => $language) {
            if (is_numeric($languageKey)) {
                $activeLanguagesIds[] = $languageKey;
            }
        }

        return $activeLanguagesIds;
    }

    /**
     * Checks if the provided language is one of the active languages
     * @param string $language - the short name of the language
     * @return bool
     */
    public function isActiveLanguage(string $language)
    {
        $this->getActiveLanguages();

        return isset($this->activeLangMap[$language]);
    }

    /**
     * Gets an array of all allowed languages shorts
     * @return array
     */
    public function getAllowedLanguages()
    {
        return $this->allowedLanguages;
    }

    /**
     * Returns the default language id and short name
     * @return array
     */
    public function getDefaultLanguage()
    {
        global $Core;

        return array(
            'id' => $Core->defaultLanguageId,
            'short' => $Core->defaultLanguage
        );
    }

    /**
     * Returns the default language id
     * @return int
     */
    public function getDefaultLanguageId()
    {
        global $Core;

        return $Core->defaultLanguageId;
    }

    /**
     * Returns the default language short name
     * @return string
     */
    public function getDefaultLanguageShortName()
    {
        global $Core;

        return $Core->defaultLanguage;
    }

    /**
     * Returns the current language id and short name
     * @return array
     */
    public function getCurrentLanguage()
    {
        if ($this->currentLanguageId === null) {
            return $this->getDefaultLanguage();
        }
    	return array(
    		'id' => $this->currentLanguageId,
    		'short' => $this->currentLanguage
    	);
    }

    /**
     * Returns the current language id
     * @return int
     */
    public function getCurrentLanguageId()
    {
        if ($this->currentLanguageId === null) {
            return $this->getDefaultLanguageId();
        }
    	return $this->currentLanguageId;
    }

    /**
     * Returns the current language short name
     * @return string
     */
    public function getCurrentLanguageShortName()
    {
        if ($this->currentLanguageId === null) {
            return $this->getDefaultLanguageName();
        }
    	return $this->currentLanguage;
    }

    /**
     * Returns the name of the language with the provided langugage id
     * Throws Exception if the language does not exist
     * @param int $langId - the id of the language
     * @throws Exception
     * @return string
     */
    public function getNameById(int $langId)
    {
        $info = $this->getById($langId);

        if (empty($info)) {
            throw new Exception("This language does not exist");
        }

        return $info['name'];
    }

    /**
     * Returns the native name of the language with the provided langugage id
     * Throws Exception if the language does not exist
     * @param int $langId - the id of the language
     * @throws Exception
     * @return string
     */
    public function getNativeNameById(int $langId)
    {
        $info = $this->getById($langId);

        if (empty($info)) {
            throw new Exception("This language does not exist");
        }

        return $info['native_name'];
    }

    /**
     * Returns the short of the language with the provided langugage id
     * Throws Exception if the language does not exist
     * @param int $langId - the id of the language
     * @throws Exception
     * @return string
     */
    public function getShortById(int $langId)
    {
        $info = $this->getById($langId);

        if (empty($info)) {
            throw new Exception("This language does not exist");
        }

        return $info['short'];
    }

    /**
     * Returns the id of the language with the provided langugage name or short
     * Throws Exception if the language does not exist
     * @param string $language - the name or short of the language
     * @throws Exception
     * @return int
     */
    public function getIdByName(string $language)
    {
        global $Core;

        $language = $Core->db->real_escape_string($language);

        $this->translateResult = false;

        $info = $this->getAll(1, "`name` = '$language' OR `short` = '$language'");

        $this->translateResult = true;

        if (empty($info)) {
            throw new Exception("This language does not exist");
        }

        $info = current($info);

        return intval($info['id']);
    }
}
