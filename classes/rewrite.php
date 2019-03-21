<?php
class Rewrite{
    private $controller  = false;
    private $view        = false;
    private $PATH        = false;
    public  $GET         = false;
    public  $URL         = false;
    public  $URLGETPART  = '';
    public  $currentPage = 1;

    public function __construct(){
        global $Core;

        //file is local bot
        if(!isset($_SERVER['REQUEST_URI'])){
            return false;
        }

        $q = urldecode($_SERVER['REQUEST_URI']);

        if(mb_stristr($q, '?')){
            $qs = substr($q, 0, strpos($q, '?'));
            preg_match_all("{/([^/]*)}", $qs, $matches);
        }else{
            preg_match_all("{/([^/]*)}", $q, $matches);
        }

        $matches   = $matches[1];
        $this->GET = $matches;

        if(stristr($q,'?')){
            $this->URLGETPART = substr($q,strpos($q,'?'));
        }

        if(isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) && intval($_REQUEST['page']) !== 1){
            $this->currentPage = $_REQUEST['page'];
        }elseif(isset($matches[count($matches)-1]) && is_numeric($matches[count($matches)-1]) && intval($matches[count($matches)-1]) !== 1){
            $this->currentPage = $matches[count($matches)-1];
            unset($matches[count($matches)-1]);
        }elseif($Core->allowFirstPage && isset($matches[count($matches)-1]) && is_numeric($matches[count($matches)-1]) && intval($matches[count($matches)-1]) === 1){
            unset($matches[count($matches)-1]);
        }

        if($this->currentPage <= 0){
            $this->doOrDie();
        }

        $this->PATH = implode('/', $matches);
        $this->URL  = '/'.$this->PATH;

        if(isset($Core->rewriteOverride[$this->PATH])){
            $this->PATH = $Core->rewriteOverride[$this->PATH];
        }elseif(in_array($this->PATH, $Core->rewriteOverride)){
            $this->doOrDie();
        }

        $this->controller = $this->PATH;
        $this->view       = $this->PATH;
        return true;
    }

    public function getFiles(){
        global $Core, $_DELETE, $_PUT, $_PATCH;

        ob_start();

        if(is_file($Core->controllersDir.$this->controller.'.php')){
            if($Core->requiredFiles){
                if(is_string($Core->requiredFiles)){
                    $Core->requiredFiles = array($Core->requiredFiles);
                }

                foreach($Core->requiredFiles as $req){
                    if(is_file($Core->projectDir.$req.'.php')){
                        require_once($Core->projectDir.$req.'.php');
                    }else{
                        throw new Exception('Required file: '.$req.' does not exists');
                    }
                }
                unset($req);
            }

            require_once($Core->controllersDir.$this->controller.'.php');
        }else{
            $this->doOrDie();
        }

        //use as API with JSON response
        if($Core->API == true){
            header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, PATCH, DELETE");
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Headers: *");
            $Core->requiredFiles = array_merge($Core->requiredFiles, array($Core->controllersDir.$this->controller => $Core->controllersDir.$this->controller));
            $output = array();

            foreach($Core->requiredFiles as $kRequired => $requiredFile){
                $Core->requiredFiles[$kRequired] = $Core->projectDir.$requiredFile.'.php';
            }
            //add controller to $Core->requiredFiles to be processeds
            $Core->requiredFiles[$Core->controllersDir.$this->controller] = $Core->controllersDir.$this->controller.'.php';
            unset($kRequired, $requiredFile);

            foreach($Core->requiredFiles as $requiredFile){
                if(preg_match_all('~(?!\$Core|\$_DELETE|\$_PUT|\$_PATCH|\$GLOBALS|\$_SERVER|\$_REQUEST|\$_POST|\$_GET|\$_FILES|\$_ENV|\$_COOKIE|\$_SESSION)\$[A-Za-z0-9-_]+~', file_get_contents($requiredFile), $vars)){
                    unset($requiredFile);
                    $vars[0] = array_unique($vars[0]);

                    foreach($vars[0] as $r){
                        $r = substr($r, 1);
                        if(isset(${$r})){
                            $output[$r] = ${$r};
                        }
                    }

                    unset($vars, $r);
                }
            }

            //remove controller from $Core->requiredFiles list
            unset($Core->requiredFiles[$Core->controllersDir.$this->controller], $output['output'], $output['vars'], $output['r']);

            header('Content-Type: application/json');
            echo json_encode($output, JSON_PRETTY_PRINT);

            $page = ob_get_contents();
            ob_end_clean();
            echo $page;

            die;
        }
        //end API

        if(is_file($Core->viewsDir.$this->view.'.html')){
            require_once($Core->viewsDir.$this->view.'.html');
        }elseif(is_file($Core->viewsDir.$this->view.'.php')){
            require_once($Core->viewsDir.$this->view.'.php');
        }

        $page = ob_get_contents();
        ob_end_clean();

        if(!$Core->ajax){
            require_once $Core->projectDir.'/includes/header.php';
        }

        echo $page;

        if(!$Core->ajax){
            require_once $Core->projectDir.'/includes/footer.php';
        }

        return true;
    }

    public function setController($controller){
        $this->controller = $controller;
        return true;
    }

    public function setView($view){
        $this->view = $view;
        return true;
    }

    protected function doOrDie($check = false){
        global $Core;

        if(!$check){
            $this->setController($Core->pageNotFoundLocation);
            $this->setView($Core->pageNotFoundLocation);
            $this->getFiles();
            die;
        }

        return true;
    }
}
?>