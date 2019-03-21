<?php
    if(!defined('SITE_PATH')){
        exit("Please define a SITE_PATH global variable!");
    }

    if(!defined('SITE_NAME')){
        exit("Please define a SITE_NAME global variable!");
    }

    if(!is_file(GLOBAL_PATH.SITE_PATH."settings/".SITE_NAME.".php")){
        exit('No configuration file found for site "'.SITE_PATH.SITE_NAME.'"');
    }

    //old php version fix
    if(!class_exists('Error')){
        class Error extends Exception{}
    }
    //old php version fix
    if(!class_exists('Success')){
        class Success extends Exception{}
    }
    //initialize request methods
    //in addition to work they are called in the rewrite class
    //they are also added to $_REQUEST
    //but won't work inside a function must use global
    $_DELETE = array();
    $_PUT    = array();
    $_PATCH  = array();

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

    //require current site settings
    require_once(GLOBAL_PATH.SITE_PATH."settings/".SITE_NAME.".php");
    require_once(GLOBAL_PATH.SITE_PATH."settings/classestree.php");
    
    if (!isset($info) || empty($info) || !is_array($info)) {
        exit('Variable $info must be present in the site config file and must be a non-empty array!');
    }

    $platformFiles = array('class.mc','class.db','globalTemplates','core');
    
    foreach ($platformFiles as $file) {
        if (!is_file(GLOBAL_PATH.'platform/core/'.$file.".php")) {
            exit("File $file.php is missing!");
        }
        require_once(GLOBAL_PATH.'platform/core/'.$file.'.php');
    }

    $Core = new Core($info, $classesTree);

    unset($info, $platformFiles, $file);
?>