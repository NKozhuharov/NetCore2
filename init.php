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
    $contents = file_get_contents('php://input');

    if (!empty($contents)) {
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
            if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
                $_DELETE = json_decode($contents, true);
                if (empty($_DELETE)) {
                    exit("Invalid JSON: ".$contents);
                }
            } else {
                mb_parse_str($contents, $_DELETE);
            }
            $_REQUEST = array_merge($_REQUEST, $_DELETE);
        } elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
                $_PUT = json_decode($contents, true);
                if (empty($_PUT)) {
                    exit("Invalid JSON: ".$contents);
                }
            } else {
                mb_parse_str($contents, $_PUT);
            }
            $_REQUEST = array_merge($_REQUEST, $_PUT);
        } elseif ($_SERVER['REQUEST_METHOD'] == 'PATCH') {
            if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
                $_PATCH = json_decode($contents, true);
                if (empty($_PATCH)) {
                    exit("Invalid JSON: ".$contents);
                }
            } else {
                mb_parse_str($contents, $_PATCH);
            }
            $_REQUEST = array_merge($_REQUEST, $_PATCH);
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
                $_POST = json_decode($contents, true);
                if (empty($_POST)) {
                    exit("Invalid JSON: ".$contents);
                }
            } else {
                mb_parse_str($contents, $_POST);
            }
            $_REQUEST = array_merge($_REQUEST, $_POST);
        }
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

    //init Language class to listen for language change if multilanguage is allowed
    if ($Core->allowMultilanguage) {
        $Core->Language;
    }

    //init images class if present
    if ($Core->{$Core->imagesModel}) {
        $Core->{$Core->imagesModel};
    }

    //init users class if present
    if ($Core->{$Core->userModel}) {
        $Core->{$Core->userModel}->init();
    }

    if ($Core->multiLanguageLinks === true) {
        $Core->Links->setLanguageChangeLinks(
            $Core->Links->getLanguageChangeLinksOfController()
        );
    }

    //get required files
    $Core->Rewrite->getFiles();

    unset($info, $platformFiles, $file);
} catch (Success $e) {
    ob_end_clean();

    if (isset($Core) && is_object($Core->exceptionhandler)) {
        $Core->exceptionhandler->Success($e);
    } else {
        header('HTTP/1.1 500 Internal Server Error', true, 500);

        echo "Internal Server Error";
    }

    die;
} catch (Error $e) {
    ob_end_clean();

    if (isset($Core) && is_object($Core->exceptionhandler)) {
        $Core->exceptionhandler->Error($e);
    } else {
        header('HTTP/1.1 500 Internal Server Error', true, 500);

        echo "Internal Server Error";
    }

    die;
} catch (ForbiddenException $e) {
    ob_end_clean();

    if (isset($Core) && is_object($Core->exceptionhandler)) {
        $Core->exceptionhandler->ForbiddenException($e);
    } else {
        header('HTTP/1.1 500 Internal Server Error', true, 500);

        echo "Internal Server Error";
    }

    die;
} catch (BaseException $e) {
    ob_end_clean();

    if (isset($Core) && is_object($Core->exceptionhandler)) {
        $Core->exceptionhandler->BaseException($e);
    } else {
        header('HTTP/1.1 500 Internal Server Error', true, 500);

        echo "Internal Server Error";
    }

    die;
} catch (Exception $e) {
    ob_end_clean();

    if (isset($Core) && is_object($Core->exceptionhandler)) {
        $Core->exceptionhandler->Exception($e);
    } else {
        header('HTTP/1.1 500 Internal Server Error', true, 500);

        echo "Internal Server Error";
    }

    die;
}
