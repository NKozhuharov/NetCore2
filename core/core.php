<?php
class Core{
    //PLATFORM VAIRABLES
    //system
    /**
     * @var db
     * An instance of the Database class
     */
    public $db;
    /**
     * @var mc
     * An instance of the Memcache class
     */
    public $mc;
    /**
     * @var string
     * The name of the database
     */
    public $dbName;
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
    public $projectDir;
    /**
     * @var string
     * The directory with the controllers
     */
    public $controllersDir;
    /**
     * @var string
     * The directory with the views
     */
    public $viewsDir;
    /**
     * @var string
     * The timezone of the project
     * For a list of supported timezones - http://php.net/manual/en/timezones.php
     */
    public $timezone = 'Europe/Sofia';
    /**
     * @var int
     * Query cache time; set to -1 for recache
     */
    public $cacheTime = 0;
    /**
     * @var bool
     * Shows if the request is ajax
     * If it is, the platfornm won't include header and footer
     */
    public $ajax = false; 
    
    public $doNotStrip                = false;          //do not strip these parameters
    public $pageNotFoundLocation      = '/not-found';   //moust not be numeric so the rwrite can work
    public $allowFirstPage            = false;          //if allowed url like "/1" won't redirect to $pageNotFoundLocation
    public $isBot                     = false;          //current script is bot
    //domain
    public $siteDomain;
    public $siteName;
    //rewrite override
    public $rewriteOverride           = array('' => 'index');
    //user model name
    public $userModel                 = false;
    //menu model name
    public $menuModel                 = false;
    //notifications(messages) model name
    public $messagesModel             = false;
    //limits
    public $folderLimit               = 30000;
    //pagination limits
    public $itemsPerPage              = 25;
    public $numberOfPagesInPagination = 5;
    //images
    public $imagesStorage             = 'images_org/'; //original images not web accessible
    public $imagesDir                 = 'images/';
    public $imagesWebDir              = '/images/';
    //files
    public $filesDir                  = 'files/';
    public $filesWebDir               = '/files/';
    //mail config
    public $mailConfig                = array(
        'Username'   => 'noreply@site.bg',
        'Password'   => 'pass',
        'Host'       => 'mail.site.bg',
        'SMTPSecure' => 'ssl',
        'Port'       => '465'
    );
    
    public $generalErrorText = 'Sorry There has been some kind of an error with your request. If this persists please Contact Us.';
    
    public $debugIps = array();
    
    //API VARIABLES
    
    /**
     * @var bool
     * Use the platform as API, it will return all no superglobals or constants that are set as JSON
     * If the requiredFiles have variables same as in the controller they will be overwritten like in the script
     */
    public $isAPI = false;   
    
    /**
     * @var array
     * Files that should be included before the current controller, array or string
     * $Core->projectDir(like /var/www/sitename/admin/) will be concatenated in the start and .php on the end
     */         
    public $APIRequiredFiles = array(); 
    
    //END OF PLATFORM VARIABLES

    public function __construct(array $info, array $classesTree = null){
        //catch if request is ajax
        $this->ajax = (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
            (isset($_REQUEST['ajax'])) ||
            (isset($AJAX) && $AJAX == true)
        ) ? true : false;

        foreach (glob(GLOBAL_PATH.'platform/classes/*.php') as $coreClass) {
            require_once($coreClass);
        }
        
        foreach (glob(GLOBAL_PATH.'platform/classes/*', GLOB_ONLYDIR) as $coreDirectory) {
            $this->moduleDirectories[] = $coreDirectory;
        }

        $this->dbName = $info['db']['select']['db'];
        $this->mc     = mc::getInstance($info['mc']);
        $this->db     = db::getInstance($info['db']);

        $this->projectDir = GLOBAL_PATH.SITE_PATH.SITE_NAME.'/';
        
        unset($info['db'], $info['mc']);
        
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
        
        $this->controllersDir = $this->projectDir.'controllers/';
        $this->viewsDir       = $this->projectDir.'views/';
        
        if (!empty($classesTree)) {
            foreach ($classesTree as $child => $parent) {
                $this->classesRequriementTree[strtolower($child)] = strtolower($parent);
            } 
        }

        return true;
    }
    
    private function initCoreModel(string $className, string $fileLocation)
    {
        if (is_file($fileLocation)) {
            require_once($fileLocation);
            $this->$className = new $className();
            return $this->$className;
        }
        return false;
    }
    
    private function requireSiteSpecificModel(string $className)
    {
        return $this->initCoreModel(
            $className,
            $this->projectDir.'models/'.$className.'.php'
        );
    }
    
    private function requireGlobalModel(string $className)
    {
        return $this->initCoreModel(
            $className,
            GLOBAL_PATH.SITE_PATH.'classes/'.$className.'.php'
        );
    }
    
    private function requirePlatformModel(string $className)
    {
        return $this->initCoreModel(
            $className,
            GLOBAL_PATH.'platform/classes/'.$className.'.php'
        );
    }
    
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
    
    
    private function requireModel(string $className) 
    {
        $model = $this->requireSiteSpecificModel($className);
        if ($model !== false) {
            return $model;
        }
        unset($model);
        
        $model = $this->requireGlobalModel($className);
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

    public function __get($var)
    {
        $var = strtolower($var);
        
        if (!isset($this->$var)) {
            if (isset($this->classesRequriementTree[$var])) {
                $parentName = $this->classesRequriementTree[$var];
                
                if (!isset($this->$parentName)) {
                    $this->requireModel($parentName); 
                }
                
                unset($parentName);
                
                return $this->requireModel($var);
            }
            
            return $this->requireModel($var);
        } else {
            return $this->$var;
        }
        return false;
    }

    //IMPORTANT! CALL INIT FUNCTION RIGHT AFTER CORE CONSTRUCTOR!
    public function init()
    {
        global $_DELETE, $_PUT, $_PATCH;

        date_default_timezone_set($this->timezone);
        //set locale for date functions
        setlocale(LC_ALL, mb_strtolower($this->language->currentLanguage).'_'.mb_strtoupper($this->language->currentLanguage).'.utf8');

        $this->globalFunctions->stripAllFields($_POST, $this->doNotStrip);
        $this->globalFunctions->stripAllFields($_GET, $this->doNotStrip);
        $this->globalFunctions->stripAllFields($_REQUEST, $this->doNotStrip);
        $this->globalFunctions->stripAllFields($_DELETE, $this->doNotStrip);
        $this->globalFunctions->stripAllFields($_PUT, $this->doNotStrip);
        $this->globalFunctions->stripAllFields($_PATCH, $this->doNotStrip);

        //WARNING: REWRITE NGNIX IN ORDER FOR IMAGES AND FILES TO WORK
        $this->imagesStorage = GLOBAL_PATH.SITE_PATH.$this->imagesStorage;
        $this->imagesDir     = GLOBAL_PATH.SITE_PATH.$this->imagesDir;
        $this->filesDir      = GLOBAL_PATH.SITE_PATH.$this->filesDir;

        return true;
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
     * Locks the page for all users, which are not in the debugIps
     */
    public function lockPage()
    {
        if (!in_array($_SERVER['REMOTE_ADDR'], $this->debugIps)) {
            exit('This page is under development. We are sorry for the inconvenience. For questions please contact your developers.');
        }
    }
}
?>