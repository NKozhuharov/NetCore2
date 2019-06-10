<?php
class Rewrite
{
    /**
     * @var string
     * The name of the controller
     */
    private $controller;

    /**
     * @var string
     * The name of the view (usually same as controller)
     */
    private $view;

    /**
     * @var array
     * A breakdown of all parts of the URL (on /) without GET parameters
     */
    private $urlBreakdown;

    /**
     * @var string
     * The entire url without the domain and the get request
     */
    private $url;

    /**
     * @var string
     * The part of the url after ? (get request)
     */
    private $urlGetPart = '';

    /**
     * @var int
     * The current page for the pagination
     */
    private $currentPage = 1;

    /**
     * Creates an instance of the Rewrite class
     * It will parse the current URL and determine the current controller and view and the current page
     * It will not do anything if the user is bot
     */
    public function __construct()
    {
        global $Core;

        //file is local bot
        if (!isset($_SERVER['REQUEST_URI']) || $Core->isBot) {
            return;
        }

        $q = urldecode($_SERVER['REQUEST_URI']);

        if (mb_stristr($q, '?')) {
            $qs = substr($q, 0, strpos($q, '?'));
            preg_match_all("{/([^/]*)}", $qs, $matches);
        } else {
            preg_match_all("{/([^/]*)}", $q, $matches);
        }

        $matches = $matches[1];
        $this->urlBreakdown = $matches;

        if (stristr($q, '?')) {
            $this->urlGetPart = substr($q, strpos($q, '?'));
        }

        if (isset($matches[count($matches) - 1]) && is_numeric($matches[count($matches) - 1])) {
            if ($Core->allowfirstPage === false && intval($matches[count($matches) - 1]) === 1) {
                $this->showPageNotFound();
            }

            $this->currentPage = intval($matches[count($matches) - 1]);

            unset($matches[count($matches) - 1]);
        } elseif (isset($_REQUEST['page']) && is_numeric($_REQUEST['page'])) {
            if ($Core->allowfirstPage === false && intval($_REQUEST['page']) === 1) {
                $this->showPageNotFound();
            }

            $this->currentPage = intval($_REQUEST['page']);
        }

        if ($this->currentPage < 1) {
            $this->showPageNotFound();
        }

        //if the request is for file it will not search subdirectories
        if (!empty($matches) && "/$matches[0]/" == $Core->filesWebDir) {
            $path = $matches[0];
        } else{
            $path = implode('/', $matches);
        }

        if (substr($path, -1) === '/') {
            $this->showPageNotFound();
        }

        $this->url = '/'.$path;

        if (isset($Core->rewriteOverride[$path])) {
            $path = $Core->rewriteOverride[$path];
        } elseif (!empty($Core->rewriteOverride) && in_array($path, $Core->rewriteOverride)) {
            $this->showPageNotFound();
        }

        $this->controller = $Core->Links->parseLink($path);
        $this->view = $Core->Links->parseLink($path);
    }

    /**
     * Access to the local variable
     * @string $varName - the name of the variable
     */
    public function __get(string $varName)
    {
        if (isset($this->$varName)) {
            return $this->$varName;
        }
    }

    /**
     * Local function for showing 'page not found'
     */
    private function showPageNotFound()
    {
        global $Core;

        $this->setController($Core->pageNotFoundLocation);
        $this->setView($Core->pageNotFoundLocation);
        $this->getFiles();
        die;
    }

    /**
     * Allows to dump the output of the current controller as JSON
     * It allows additional files to be loaded before the controller using the $Core->jsonAdditionalFiles
     */
    public function controllerAsJson()
    {
        global $Core, $_DELETE, $_PUT, $_PATCH;

        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, PATCH, DELETE");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        header('Content-Type: application/json');

        $jsonAdditionalFiles = $Core->jsonAdditionalFiles;

        if (!is_array($jsonAdditionalFiles)) {
            throw new Exception("jsonAdditionalFiles must be an array");
        }

        foreach ($jsonAdditionalFiles as $kRequired => $requiredFile) {
            $jsonAdditionalFiles[$kRequired] = $Core->siteDir.$requiredFile.'.php';
        }

        unset($kRequired, $requiredFile);

        $jsonAdditionalFiles[] = $Core->controllersDir.$this->controller.'.php';

        foreach ($jsonAdditionalFiles as $req) {
            if (is_file($req)) {
                require_once ($req);
            } else {
                throw new Exception('Required file: '.$req.' does not exists');
            }
        }

        $output = array();

        foreach ($jsonAdditionalFiles as $requiredFile) {
            if (preg_match_all(
                '~(?!\$Core|\$_DELETE|\$_PUT|\$_PATCH|\$GLOBALS|\$_SERVER|\$_REQUEST|\$_POST|\$_GET|\$_FILES|\$_ENV|\$_COOKIE|\$_SESSION)\$[A-Za-z0-9-_]+~',
                file_get_contents($requiredFile),
                $vars)
            ) {
                    unset($requiredFile);
                    $vars[0] = array_unique($vars[0]);

                    foreach ($vars[0] as $r) {
                        $r = substr($r, 1);
                        if (isset(${$r})) {
                            $output[$r] = ${$r};
                        }
                    }

                    unset($vars, $r);
                }
        }

        unset($output['output'], $output['vars'], $output['r']);

        exit(json_encode($output, JSON_PRETTY_PRINT));
    }

    /**
     * Requires the controller and the view.
     * If the request is not AJAX or for JSON dump it will include the header and footer
     */
    public function getFiles()
    {
        global $Core, $_DELETE, $_PUT, $_PATCH;

        if ($Core->json === true) {
            $this->controllerAsJson();
        }

        if (is_file($Core->controllersDir.$this->controller.'.php')) {
            require_once ($Core->controllersDir.$this->controller.'.php');
        } else {
            $this->showPageNotFound();
        }

        ob_start();
            if (is_file($Core->viewsDir.$this->view.'.html')) {
                require_once ($Core->viewsDir.$this->view.'.html');
            } elseif (is_file($Core->viewsDir.$this->view.'.php')) {
                require_once ($Core->viewsDir.$this->view.'.php');
            }

            $page = ob_get_contents();
        ob_end_clean();

        ob_start();
            if (!$Core->ajax) {
                require_once $Core->siteDir.'/includes/header.php';
            }

            echo $page;

            if (!$Core->ajax) {
                require_once $Core->siteDir.'/includes/footer.php';
            }
            $page = ob_get_contents();
        ob_end_clean();

        echo $page;
    }

    /**
     * Allows the user to change the current controller
     * @param string $controller - the new name for the controller
     */
    public function setController(string $controller)
    {
        $this->controller = $controller;
    }

    /**
     * Allows the user to change the current view
     * @param string $controller - the new name for the view
     */
    public function setView(string $view)
    {
        $this->view = $view;
    }

    /**
     * Allows the user to change the current page
     * @param int $page - the new number of the current page
     */
    public function setCurrentPage(int $page)
    {
        $this->currentPage = $page;
    }
}
