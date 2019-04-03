<?php
class Core{
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
     * @var int
     * Query cache time; set to -1 for recache
     */
    private $cacheTime = 0;

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
     * @var string
     * The short name of the default language
     */
    private $defaultLanguage = 'bg';

    /**
     * @var int
     * The id of the default language
     */
    private $defaultLanguageId = 24;

    private $doNotStrip                = false;          //do not strip these parameters
    private $pageNotFoundLocation      = '/not-found';   //moust not be numeric so the rewrite can work
    private $allowFirstPage            = false;          //if allowed url like "/1" won't redirect to $pageNotFoundLocation
    private $isBot                     = false;          //current script is bot
    //domain
    private $siteDomain;
    private $siteName;
    //rewrite override
    private $rewriteOverride           = array('' => 'index');
    //user model name
    private $userModel = null;
    //menu model name
    private $menuModel                 = false;
    //notifications(messages) model name
    private $messagesModel             = false;
    //limits
    private $folderLimit               = 30000;
    //pagination limits
    private $itemsPerPage              = 25;
    private $numberOfPagesInPagination = 5;
    //images
    private $imagesModel               = null;
    private $imagesStorage             = 'images_org/'; //original images not web accessible
    private $imagesDir                 = 'images/';
    private $imagesWebDir              = '/images/';
    //files
    private $filesDir                  = 'files/';
    private $filesWebDir               = '/files/';
    //mail config
    private $mailConfig                = array(
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
    private $generalErrorText = 'Sorry There has been some kind of an error with your request. If this persists please Contact Us.';

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
            throw new Exception ("'db' and 'mc' variables are required for a Core instance!");
        }

        $this->ajax = (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
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

        $this->siteDir     = GLOBAL_PATH.PROJECT_PATH.SITE_PATH.'/';
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
                $this->classesRequriementTree[strtolower($child)] = strtolower($parent);
                unset($this->classesRequriementTree[$child]);
            }
        }

        date_default_timezone_set($this->timezone);
        //set locale for date functions

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
     * @return object|false
     */
    private function initCoreModel(string $className, string $fileLocation)
    {
        if (is_file($fileLocation)) {
            require_once($fileLocation);
            $this->$className = new $className();
            return $this->$className;
        }
        return false;
    }

    /**
     * Requires a model from the site models
     * @param string $className - the name of the class (in lowercase)
     * @return object|false
     */
    private function requireSiteSpecificModel(string $className)
    {
        return $this->initCoreModel(
            $className,
            $this->siteDir.'models/'.$className.'.php'
        );
    }

    /**
     * Requires a model from the project global models
     * @param string $className - the name of the class (in lowercase)
     * @return object|false
     */
    private function requireProjectGlobalModel(string $className)
    {
        return $this->initCoreModel(
            $className,
            GLOBAL_PATH.PROJECT_PATH.'classes/'.$className.'.php'
        );
    }

    /**
     * Requires a model from the platform main models
     * @param string $className - the name of the class (in lowercase)
     * @return object|false
     */
    private function requirePlatformModel(string $className)
    {
        return $this->initCoreModel(
            $className,
            GLOBAL_PATH.'platform/classes/'.$className.'.php'
        );
    }

    /**
     * Requires a model from the platform modules
     * @param string $className - the name of the class (in lowercase)
     * @return object|false
     */
    private function requireModuleModel(string $className)
    {
        foreach ($this->moduleDirectories as $coreDirectory) {
            $model = $this->initCoreModel(
                $className, $coreDirectory.'/'.$className.'.php'
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
     * @return object|false
     */
    private function requireModel(string $className)
    {
        $model = $this->requireSiteSpecificModel($className);
        if ($model !== false) {
            return $model;
        }
        unset($model);

        $model = $this->requireProjectGlobalModel($className);
        if ($model !== false) {
            return $model;
        }
        unset($model);

        $model = $this->requirePlatformModel($className);
        if ($model !== false) {
            return $model;
        }
        unset($model);

        $model = $this->requireModuleModel($className);
        if ($model !== false) {
            return $model;
        }

        return false;
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

        $varName = strtolower($varName);

        if ($varName === 'exceptionhandler') {

            if (empty($this->exceptionhandler)) {
                return $this->requireModel(strtolower($this->exceptionHandlerName));
            } else {
                return $this->exceptionhandler;
            }
        }

        if (!isset($this->$varName)) {
            if (isset($this->classesRequriementTree[$varName])) {
                $parentName = $this->classesRequriementTree[$varName];

                if (!isset($this->$parentName)) {
                    $this->requireModel($parentName);
                }

                unset($parentName);

                return $this->requireModel($varName);
            }

            return $this->requireModel($varName);
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
            throw new Success('<script>window.location.replace("'.$url.'")</script>');
        } else {
            header("Location: ".$url, 1, 302);
            exit();
        }

        return true;
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
     * If the client has no IP address (is bot) or no debug ips are specified, he is considered developer
     * @return bool
     */
    public function clientIsDeveoper()
    {
        if ($this->isBot || !isset($_SERVER['REMOTE_ADDR']) || empty($this->debugIps)) {
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
            throw new Exeption("Items per page must be bigger than 0");
        }

        $this->itemsPerPage = $itemsPerPage;
    }
}
