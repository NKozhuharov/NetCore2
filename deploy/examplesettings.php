<?php
$info = array(
    'db' => array(
         'select' => array(
             'host' => 'localhost'
            ,'user' => 'root'
            ,'pass' => '123'
            ,'db'   => 'dbname'
            ,'charset' => "utf8"
         )
         ,'insert' => array(
             'host' => 'localhost'
            ,'user' => 'root'
            ,'pass' => '123'
            ,'db'   => 'dbname'
            ,'charset' => "utf8"
         )
    ),
    'mc' => array(
         array('host' => 'localhost','port' => 11211)
    ),
    'classesRequriementTree' => array(
        
    ),
    'mailConfig'             => array(
        'Username'   => '',
        'Password'   => '',
        'Host'       => '',
        'SMTPSecure' => '',
        'Port'       => '',
    ),
    'debugIps'             => array(
        
    ),
    'siteName'             => 'Admin',
    'siteDomain'           => 'https://admin.datamind.bg',
    'itemsPerPage'         => 10,
    'userModel'            => 'adminuser',
    'menuModel'            => 'adminmenu',
    'exceptionHandlerName' => 'adminexception',
    'filesModel'           => 'AdminUserFiles',
    'filesDir'             => 'files/',
    'imagesStorage'        => 'images/org/',
    'imagesModel'          => 'adminimages',
    'defaultLanguage'      => 'bg',
    'defaultLanguageId'    => 24,
    'allowFirstPage'       => true,
    'doNotStrip'           => array(
        
    ),
);
