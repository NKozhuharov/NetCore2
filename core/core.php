<?php
class Core
{
    //PLATFORM VAIRABLES
    /**
     * @var db
     * An instance of the Database class
     */
    private $db;

    /**
     * @var mc
     * An instance of the Memcache class
     */
    private $mc;

    /**
     * @var string
     * The name of the database
     */
    private $dbName;

    /**
     * @var array
     * Contains all the subfolders of the main classes folder
     */
    private $moduleDirectories = array();

    /**
     * @var array
     * Contains the classes inheritance description
     */
    private $classesRequriementTree = array();

    /**
     * @var string
     * The main directory with the project files
     */
    private $siteDir;

    /**
     * @var string
     * The directory with the controllers
     */
    private $controllersDir;

    /**
     * @var string
     * The directory with the views
     */
    private $viewsDir;

    /**
     * @var string
     * The timezone of the project
     * For a list of supported timezones - http://php.net/manual/en/timezones.php
     */
    private $timezone = 'Europe/Sofia';

    /**
     * @var string
     * The logo of the project
     * Mostly used in the admin panel
     */
    private $logo = '/img/logo.svg';

    /**
     * @var int
     * Global cache time reset for all MYSQL queries no matter what
     * It has to be set to true
     */
    private $resetQueryCache = false;

    /**
     * @var bool
     * Shows if the request is ajax
     * If it is, the platfornm won't include header and footer
     */
    private $ajax = false;

    /**
     * @var bool
     * Allows the platform to dump the controller variables in JSON format
     */
    private $json = false;

    /**
     * @var bool
     * Set to true if the current script is bot
     */
    private $isBot = false;

    /**
    * @var array
    * Files that should be included before the current controller, array
    * $Core->siteDir(like /var/www/sitename/admin/) will be concatenated in the start and .php on the end
    */
    private $jsonAdditionalFiles = array();

    /**
     * @var string
     * The name of the global exception handler class
     */
    private $exceptionHandlerName = 'ExceptionHandler';

    /**
     * @var object
     * An instance of the gloal exception handler class
     */
    private $exceptionhandler;

    /**
     * @var bool
     * Shows if the site has more than 1 language
     */
    private $allowMultilanguage = true;

    /**
     * @var string
     * The short name of the default language
     */
    private $defaultLanguage = 'en';

    /**
     * @var int
     * The id of the default language
     */
    private $defaultLanguageId = 41;

    /**
     * @var bool
     * Set to true, if the links of the site must be available in multiple languages
     */
    private $multiLanguageLinks = false;

    /**
     * @var string
     * Set the name of the model, used to handle the user session (default is User)
     */
    private $userModel;

    /**
     * @var string
     * A phrase to put before the password for each user
     */
    private $userModelBeforePass = 'WERe]r#$%{}^JH[~ghGHJ45 #$';

    /**
     * @var string
     * A phrase to put after the password for each user
     */
    private $userModelAfterPass = '9 Y{]}innv89789#$%^&';

    /**
     * @var string
     * Set the name of the model, used to log the requests (default is RequestLogs)
     */
    private $requestLogsModel = 'requestlogs';

    /**
     * @var bool
     * Set to true to log user requests when $this->requestLogsModel is called
     */
    private $logRequests;

    /**
     * @var string
     * Set the name of the model, used to draw the menu in the admin panel (default is Menu)
     */
    private $menuModel;

    /**
     * @var string
     * Set the name of the model, used to handle the user messages (default is Messages)
     */
    private $messagesModel;

    /**
     * @var string
     * Set the name of the model, used to handle the files (default is Files)
     */
    private $filesModel;

    /**
     * @var string
     * Where to store the files of the project
     */
    private $filesDir = 'files/';

    /**
     * @var string
     * The url to the files of the project
     */
    private $filesWebDir = '/files/';

    /**
     * @var int
     * How many files to store in a signe folder
     */
    private $folderLimit = 30000;

    private $doNotStrip                = false;          //do not strip these parameters
    private $pageNotFoundLocation      = 'not-found';    //moust not be numeric so the rewrite can work
    private $allowFirstPage            = false;          //if allowed url like "/1" won't redirect to $pageNotFoundLocation

    //domain
    private $siteDomain;
    private $siteName;
    //rewrite override
    private $rewriteOverride           = array('' => 'index');

    //pagination limits
    private $itemsPerPage              = 25;
    private $numberOfPagesInPagination = 5;

    //images
    private $imagesModel               = null;
    private $imagesStorage             = 'images_org/'; //original images not web accessible
    private $imagesDir                 = 'images/';
    private $imagesWebDir              = '/images/';

    //mail config
    private $mailConfig = array(
        'Username'   => 'noreply@site.bg',
        'Password'   => 'pass',
        'Host'       => 'mail.site.bg',
        'SMTPSecure' => 'ssl',
        'Port'       => '465',
    );

    /**
     * @var string
     * The text to show when unhandled exception occurs
     */
    private $generalErrorText = 'Sorry, there has been some kind of an error with your request. Please try again later.';

    /**
     * @var array
     * A list of IPs, considered as developer IPs
     */
    private $debugIps = array();

    /**
     * Creates an instance of the Core class
     * Throws Exception if info does not contain 'db' or 'mc'
     * @param array $info - the configuration array for the platfrom
     * @throws Exception
     */
    public function __construct(array $info){
        global $_DELETE, $_PUT, $_PATCH;

        if (!isset($info['db']) || empty($info['db']) || !isset($info['mc']) || empty($info['mc'])) {
            exit("'db' and 'mc' variables are required for a Core instance!");
        }

        $this->ajax = (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && mb_strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
            (isset($_REQUEST['ajax'])) ||
            (isset($AJAX) && $AJAX == true)
        ) ? true : false;

        $this->json = (isset($_REQUEST['json']) && !empty($_REQUEST['json'])) ? true : false;

        //require base query builder
        $this->requireAllPHPFilesFromFolder(GLOBAL_PATH.'platform/basequery');
        //require base classes
        $this->requireAllPHPFilesFromFolder(GLOBAL_PATH.'platform/baseclasses');
        //require all Core classes
        $this->requireAllPHPFilesFromFolder(GLOBAL_PATH.'platform/classes');

        //initialize all core module directories
        foreach (glob(GLOBAL_PATH.'platform/classes/*', GLOB_ONLYDIR) as $moduleDirectory) {
            $this->moduleDirectories[] = $moduleDirectory;
        }

        $this->siteDir        = GLOBAL_PATH.PROJECT_PATH.SITE_PATH.'/';
        $this->controllersDir = $this->siteDir.'controllers/';
        $this->viewsDir       = $this->siteDir.'views/';

        //initialize the database and memcache instances
        $this->dbName = $info['db']['select']['db'];
        $this->mc     = mc::getInstance($info['mc']);
        $this->db     = db::getInstance($info['db']);

        //unset the db and mc variables from info and initialize the remaining varaibles as Core variables
        unset($info['db'], $info['mc']);

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        //if classes tree is provided, initialie the Core classesRequriementTree
        if (!empty($this->classesRequriementTree)) {
            foreach ($this->classesRequriementTree as $child => $parent) {
                $this->classesRequriementTree[mb_strtolower($child)] = mb_strtolower($parent);
                unset($this->classesRequriementTree[$child]);
            }
        }

        //set timezone
        date_default_timezone_set($this->timezone);

        $this->GlobalFunctions->stripTagsOfArray($_POST, $this->doNotStrip);
        $this->GlobalFunctions->stripTagsOfArray($_GET, $this->doNotStrip);
        $this->GlobalFunctions->stripTagsOfArray($_REQUEST, $this->doNotStrip);
        $this->GlobalFunctions->stripTagsOfArray($_DELETE, $this->doNotStrip);
        $this->GlobalFunctions->stripTagsOfArray($_PUT, $this->doNotStrip);
        $this->GlobalFunctions->stripTagsOfArray($_PATCH, $this->doNotStrip);

        //WARNING: REWRITE NGNIX IN ORDER FOR IMAGES AND FILES TO WORK
        $this->imagesStorage = GLOBAL_PATH.PROJECT_PATH.$this->imagesStorage;
        $this->imagesDir     = GLOBAL_PATH.PROJECT_PATH.$this->imagesDir;
        $this->filesDir      = GLOBAL_PATH.PROJECT_PATH.$this->filesDir;
    }

    /**
     * Requires all PHP files from the provided directory
     * @param string $path - the path to search for files
     */
    private function requireAllPHPFilesFromFolder(string $path)
    {
        foreach (glob($path.'/*.php') as $class) {
            require_once($class);
        }
    }

    /**
     * Requires a model with the specified class name and location and inits it as Core Model
     * @param string $className - the name of the class (in lowercase); also the name of the file
     * @param string $fileLocation - the path of the file to require
     * @param bool $init - to init the model as core variable or not
     * @return object|false
     */
    private function initCoreModel(string $className, string $fileLocation, bool $init)
    {
        if (is_file($fileLocation)) {
            require_once($fileLocation);
            if ($init === true) {
                $this->$className = new $className();
                return $this->$className;
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * Init a model from the site models
     * @param string $className - the name of the class (in lowercase)
     * @param bool $init - to init the model as core variable or not
     * @return object|false
     */
    private function initSiteSpecificModel(string $className, bool $init)
    {
        return $this->initCoreModel(
            $className,
            $this->siteDir.'models/'.$className.'.php',
            $init
        );
    }

    /**
     * Init a model from the project global models
     * @param string $className - the name of the class (in lowercase)
     * @param bool $init - to init the model as core variable or not
     * @return object|false
     */
    private function initProjectGlobalModel(string $className, bool $init)
    {
        return $this->initCoreModel(
            $className,
            GLOBAL_PATH.PROJECT_PATH.'classes/'.$className.'.php',
            $init
        );
    }

    /**
     * Init a model from the platform main models
     * @param string $className - the name of the class (in lowercase)
     * @param bool $init - to init the model as core variable or not
     * @return object|false
     */
    private function initPlatformModel(string $className, bool $init)
    {
        return $this->initCoreModel(
            $className,
            GLOBAL_PATH.'platform/classes/'.$className.'.php',
            $init
        );
    }

    /**
     * Init a model from the platform modules
     * @param string $className - the name of the class (in lowercase)
     * @param bool $init - to init the model as core variable or not
     * @return object|false
     */
    private function initModuleModel(string $className, bool $init)
    {
        foreach ($this->moduleDirectories as $coreDirectory) {
            $model = $this->initCoreModel(
                $className,
                $coreDirectory.'/'.$className.'.php',
                $init
            );
            if ($model !== false) {
                return $model;
            }
        }
        return false;
    }

    /**
     * Requires a model and inits is a Core variable
     * @param string $className - the name of the class (in lowercase)
     * @param bool $init - to init the model as core variable or not
     * @return object|false
     */
    private function initModel(string $className, bool $init)
    {
        $model = $this->initSiteSpecificModel($className, $init);
        if ($model !== false) {
            return $model;
        }
        unset($model);

        $model = $this->initProjectGlobalModel($className, $init);
        if ($model !== false) {
            return $model;
        }
        unset($model);

        $model = $this->initPlatformModel($className, $init);
        if ($model !== false) {
            return $model;
        }
        unset($model);

        $model = $this->initModuleModel($className, $init);
        if ($model !== false) {
            return $model;
        }

        return false;
    }

    /**
     * Requires all parent classes (if any) of the provided class
     * @param string $className - the name of the class
     */
    private function requireParentClasses(string $className)
    {
        $parents = array();

        while (isset($this->classesRequriementTree[$className])) {
            $parents[] = $this->classesRequriementTree[$className];
            $className = $this->classesRequriementTree[$className];
        }

        if (!empty($parents)) {
            foreach (array_reverse($parents) as $parent) {
                $this->initModel($parent, false);
            }
        }
    }

    /**
     * Gets a variable from Core;
     * If the variable is available, it will return it
     * If it's not it will attempt to require and initialize the model with the name of the variable
     * It will return false if no model is found
     * @param string $var - the name of the variable
     * @return mixed
     */
    public function __get(string $varName)
    {
        if (isset($this->$varName)) {
            return $this->$varName;
        }

        $varName = mb_strtolower($varName);

        if ($varName === 'exceptionhandler') {
            if (empty($this->exceptionhandler)) {
                return $this->initModel(mb_strtolower($this->exceptionHandlerName), true);
            } else {
                return $this->exceptionhandler;
            }
        }

        if (!isset($this->$varName)) {
            $this->requireParentClasses($varName);

            return $this->initModel($varName, true);
        } else {
            return $this->$varName;
        }
        return false;
    }

    /**
     * Redirects to the specifed URL
     * If it's an AJAX request, it will redirect with javascript
     * @param string $url - the requested url
     */
    public function redirect($url = '/')
    {
        if ($this->ajax) {
            die('<script>window.location.replace("'.$url.'")</script>');
        }

        header("Location: ".$url, 1, 302);
        exit();
    }

    /**
     * Check a condition; if it's false, it shows page not found
     * @param bool $check - the condition
     */
    public function doOrDie($check = false)
    {
        if (!$check) {
            $this->Rewrite->setController($this->pageNotFoundLocation);
            $this->Rewrite->setView($this->pageNotFoundLocation);
            $this->Rewrite->getFiles();
            die;
        }
    }

    /**
     * Shows a variable dump
     * If the debugIps is not empty, it will show ouput only for them
     * If it is empty, it will show out for all
     * @param mixed $var - the variable
     * @param bool $die - to die or not
     */
    public function dump($var, $die = true)
    {
        if ($this->debugIps) {
            if (in_array($_SERVER['REMOTE_ADDR'], $this->debugIps)) {
                echo '<pre>';
                var_dump($var);
                echo '</pre>';

                if ($die) {
                    die;
                }
            }
        } else {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';

            if ($die) {
                die;
            }
        }
    }

    /**
     * Checks if the client IP address is in the debug ips
     * If the client has no IP address and is not bot, he is considered intruder and therefore, not developer
     * If the client has is bot or no debug ips are specified, he is considered developer
     * @return bool
     */
    public function clientIsDeveloper()
    {
        if (!isset($_SERVER['REMOTE_ADDR']) && !$this->isBot) {
            return false;
        }

        if ($this->isBot || empty($this->debugIps)) {
            return true;
        }

        if (in_array($_SERVER['REMOTE_ADDR'], $this->debugIps)) {
            return true;
        }

        return false;
    }

    /**
     * Locks the page for all users, which are not in the debugIps
     */
    public function lockPage()
    {
        if (!in_array($_SERVER['REMOTE_ADDR'], $this->debugIps)) {
            exit('This page is under development. We are sorry for the inconvenience. For questions please contact your developers.');
        }
    }

    /**
     * Returns true if the request is considered AJAX
     */
    public function requestIsAjax()
    {
        return $this->ajax;
    }

    /**
     * Set the request to be considered AJAX request or not
     * @param bool $isAjax - if null or true, it will consider the request AJAX request
     */
    public function setAjaxRequest(bool $isAjax = null)
    {
        if ($isAjax === null) {
            $isAjax = true;
        }

        $this->ajax = $isAjax;
    }

    /**
     * Set a new items per page count, must be bigger than 0
     * @param int $itemsPerPage - the new items per page
     * @throws Expetion
     */
    public function setItemsPerPage(int $itemsPerPage)
    {
        if ($itemsPerPage <= 0) {
            exit("Items per page must be bigger than 0");
        }

        $this->itemsPerPage = $itemsPerPage;
    }
}
