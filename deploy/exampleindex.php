<?php
error_reporting(E_ALL);

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('html_errors', 1);
ini_set('display_startup_errors', 1);

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0 ");

define('GLOBAL_PATH','/var/www/');

define('PROJECT_PATH', 'project-path/');
define('SITE_PATH', 'site-path');

require_once(GLOBAL_PATH."platform/init.php");

/*
//This is when a user system is in place
if ($Core->{$Core->userModel} && !$Core->{$Core->userModel}->logged_in) {
    $Core->{$Core->userModel}->logout();
}
*/
