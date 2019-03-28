<?php
if (!defined('PROJECT_PATH')) {
    exit("Define a PROJECT_PATH global variable");
}

if (!defined('SITE_PATH')) {
    exit("Define a SITE_PATH global variable");
}

if (!is_file(GLOBAL_PATH.PROJECT_PATH."settings/".SITE_PATH.".php")) {
    exit('No configuration file found for site "'.PROJECT_PATH.SITE_PATH.'"');
}

//old php version fix
if (!class_exists('Error')) {
    class Error extends Exception {}
}
//old php version fix
if (!class_exists('Success')) {
    class Success extends Exception {}
}

//initialize request methods
//in addition to work they are called in the rewrite class
//they are also added to $_REQUEST
//but won't work inside a function must use global
$_DELETE = array();
$_PUT = array();
$_PATCH = array();

if (isset($_SERVER['REQUEST_METHOD'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
        parse_str(file_get_contents('php://input'), $_DELETE);
        $_REQUEST = array_merge($_REQUEST, $_DELETE);
    } else if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        parse_str(file_get_contents('php://input'), $_PUT);
        $_REQUEST = array_merge($_REQUEST, $_PUT);
    } else if ($_SERVER['REQUEST_METHOD'] == 'PATCH') {
        parse_str(file_get_contents('php://input'), $_PATCH);
        $_REQUEST = array_merge($_REQUEST, $_PATCH);
    }
}

try {
    //require current site settings
    require_once (GLOBAL_PATH.PROJECT_PATH."settings/".SITE_PATH.".php");

    if (!isset($info) || empty($info) || !is_array($info)) {
        exit('Variable $info must be present in the site config file and must be a non-empty array!');
    }

    $platformFiles = array(
        'class.mc',
        'class.db',
        'globalTemplates',
        'core');

    foreach ($platformFiles as $file) {
        if (!is_file(GLOBAL_PATH.'platform/core/'.$file.".php")) {
            exit("File $file.php is missing!");
        }
        require_once (GLOBAL_PATH.'platform/core/'.$file.'.php');
    }

    $Core = new Core($info);

    //init users class if present
    if ($Core->{$Core->userModel}) {
        $Core->{$Core->userModel}->init();
    }

    //get required files
    $Core->Rewrite->getFiles();

    unset($info, $platformFiles, $file);
} catch (Success $e) {
    $Core->exceptionhandler->Success($e);
} catch (Error $e) {
    $Core->exceptionhandler->Error($e);
} catch (BaseException $e) {
    $Core->exceptionhandler->BaseException($e);
} catch (exception $e) {
    $Core->exceptionhandler->Exception($e);
}