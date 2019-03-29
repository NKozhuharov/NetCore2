<?php
class Images extends Base{
      protected $allowedSizes = array(
        'org' => array(
            'org' => array('width' => false, 'height' => false),
        ),
        'width' => array(
            '1920' => array('width' => 1920, 'height' => false)
        ),
        'height'=> array(
            '1080' => array('width' => false, 'height' => 1080)
        ),
        'fixed' => array(
            '1920-1080' => array('width' => 1920, 'height' => 1080)
        ),
        false => array(
            false => array('width' => false, 'height' => false)
        )
    );

    protected $sizeLimit      = 10485760; //10MB
    protected $sizeLimitText  = '10MB';
    protected $uploadName     = 'imageUpload'; //$_REQUEST flag for showing it is upload
    protected $showResponse   = true;
    protected $allowDuplicate = false;
    protected $dieWhenDone    = true;
    protected $defaultFormat         = 'jpg';
    protected $autoConvertFormat    = true; //auto convert to $this->defaultFormat
    private $currentFolder  = false;
    private $folderNumber   = false;
    private $name           = false;
    private $orgMd5         = false;
    private $response       = array();
    private $upload         = false; //file upload or http request
    private $width          = false;
    private $height         = false;
    private $sizeType       = false;
    private $sizeKey            = false;
    private $imagick        = false;
    protected $hasWatermark   = 0;
    protected $watermark      = false; //'/var/www/site/www/img/watermark.png';

    public function __construct(){
        #header('Content-Type: image/jpg');
        #header('X-Accel-Redirect: /images_org'.urldecode($_REQUEST['image']));
        #die;
        #check for watermark when org
        #check for table
        #base
        #nginx
        #if storage == webdir and watermark

        global $Core;

        if(isset($_FILES[$this->uploadName], $_FILES[$this->uploadName]['tmp_name'])){
            if(is_array($_FILES[$this->uploadName]['tmp_name'])){
                $files = $Core->globalfunctions->reArrangeRequestFiles($_FILES[$this->uploadName]);
            }else{
                $files = array($_FILES[$this->uploadName]);
            }

            foreach($files as $file){
                $fileLocation = $this->checks($file);

                $this->setImagick($fileLocation);
                if($this->autoConvertFormat){
                    $this->convertFormat();
                }
                $this->orgMd5 = md5($this->imagick->__toString());
                $this->processOriginal();
            }

            if(count($this->response) == 1){
                if($this->showResponse){
                    echo $this->response[0];
                }else{
                    return $this->response[0];
                }
            }else{
                if($this->showResponse){
                    echo json_encode($this->response);
                }else{
                    return $this->response;
                }
            }

            if($this->dieWhenDone){
                die;
            }
        }elseif(substr($_SERVER['REQUEST_URI'], 0, strlen($Core->imagesWebDir)) == $Core->imagesWebDir){
            $this->checks();
            $this->setImagick($fileLocation);
            $this->orgMd5 = md5_file($fileLocation);
            $this->processResized();
        }
    }

    public function checks(array $file){
        global $Core;

        if(!isset($_SERVER['REQUEST_URI'])){
            throw new Exception($Core->language->error_unallowed_action);
        }elseif(!empty($file)){
            if(empty($file['tmp_name'])){
                throw new Exception($Core->language->error_image_is_empty);
            }elseif(!stristr(mime_content_type($file['tmp_name']), 'image')){
                throw new Error($Core->language->error_the_file.' `'.$file['name'].'` '.$Core->language->error_is_not_valid_image);
            }elseif($this->sizeLimit && filesize($file['tmp_name']) > $this->sizeLimit){
                throw new Exception($Core->language->error_image_must_not_be_bigger_than_.$this->sizeLimitText);
            }

            $this->folderNumber = $Core->globalfunctions->getFolder($Core->imagesStorage, true);
            $this->name         = substr($file['name'], 0, strripos($file['name'], '.'));
            $fileLocation         = urldecode($file['tmp_name']);

            if(isset($_REQUEST['width']) || isset($_REQUEST['height']) || isset($_REQUEST['fixed'])){
                if(isset($_REQUEST['fixed'])){
                    $this->sizeType          = 'fixed';
                    $this->sizeKey           = $_REQUEST['fixed'];
                }elseif(isset($_REQUEST['width'])){
                    $this->sizeType          = 'width';
                    $this->sizeKey           = $_REQUEST['width'];
                }elseif(isset($_REQUEST['height'])){
                    $this->sizeType          = 'height';
                    $this->sizeKey           = $_REQUEST['height'];
                }
                $this->currentFolder = $this->sizeType.'/'.$this->sizeKey.'/'.$this->folderNumber.'/';
            }else{
                $this->sizeType          = 'org';
                $this->sizeKey           = 'org';
                $this->currentFolder = $this->sizeType.'/'.$this->folderNumber.'/';
            }
            //image upload
            $this->upload = true;
        }elseif(preg_match("~^".$Core->imagesWebDir."(width|height)/([\d]+)/([\d]+)/(.*)~",$_SERVER['REQUEST_URI'] ,$m)){
            $this->sizeType      = $m[1];
            $this->sizeKey       = $m[2];
            $this->name          = urldecode(substr($m[4], 0, strripos($m[4], '.')));
            $this->currentFolder = $m[1].'/'.$m[2].'/'.$m[3].'/';
            $fileLocation          = urldecode($Core->imagesStorage.$m[3].'/'.$m[4]);

            if(!$this->allowedSizes){
                if($m[1] == 'width'){
                    $this->width  = $m[2];
                }elseif($m[1] == 'height'){
                    $this->height = $m[2];
                }
            }

            if(!is_file($fileLocation)){
                //unexisting file
                $Core->doOrDie();
            }
        }elseif(preg_match("~^".$Core->imagesWebDir."(fixed)/([\d]+-[\d]+)/([\d]+)/(.*)~",$_SERVER['REQUEST_URI'] ,$m)){
            $this->sizeType          = $m[1];
            $this->sizeKey           = $m[2];
            $this->name          = urldecode(substr($m[4], 0, strripos($m[4], '.')));
            $this->currentFolder = $m[1].'/'.$m[2].'/'.$m[3].'/';
            $fileLocation          = urldecode($Core->imagesStorage.$m[3].'/'.$m[4]);

            if(!$this->allowedSizes){
                $rato = explode('-', $m[2]);
                if(count($rato == 2)){
                    $this->width  = $rato[0];
                    $this->height = $rato[1];
                }
            }

            if(!is_file($fileLocation)){
                //unexisting file
                $Core->doOrDie();
            }
        }elseif(preg_match("~^".$Core->imagesWebDir."(org)/([\d]+)/(.*)~",$_SERVER['REQUEST_URI'] ,$m)){
            $this->sizeType          = 'org';
            $this->sizeKey           = 'org';
            $this->name          = urldecode(substr($m[3], 0, strripos($m[3], '.')));
            $this->currentFolder = $m[1].'/'.$m[2].'/';
            $fileLocation          = urldecode($Core->imagesStorage.$m[2].'/'.$m[3]);

            if(!is_file($fileLocation)){
                //unexisting file
                $Core->doOrDie();
            }
        }else{
            //unexisting file
            $Core->doOrDie();
        }

        if($this->allowedSizes && !isset($this->allowedSizes[$this->sizeType])){
            throw new Exception($Core->language->error_unallowed_action);
        }elseif($this->allowedSizes && !isset($this->allowedSizes[$this->sizeType][$this->sizeKey])){
            throw new Exception($Core->language->unallowed_image_size);
        }elseif($this->allowedSizes){
            $this->width  = $this->allowedSizes[$this->sizeType][$this->sizeKey]['width'];
            $this->height = $this->allowedSizes[$this->sizeType][$this->sizeKey]['height'];
        }

        if(!$this->autoConvertFormat){
            $ext          = mime_content_type($fileLocation);
            $this->defaultFormat = substr($ext, strrpos($ext, '/') + 1);
        }

        return $fileLocation;
    }

    public function processOriginal(){
        global $Core;

        if(!$this->allowDuplicate){
            $exists = $this->exists($this->orgMd5);
        }else{
            $exists = false;
        }

        $this->resize();

        if(!$exists){
            if(isset($_REQUEST['watermark'])){
                $this->hasWatermark = 1;
            }else{
                $this->hasWatermark = 0;
            }
            $this->getName();

            //keep original witout watermark
            $this->insertImage($Core->imagesStorage.$this->folderNumber.'/', $this->name, $this->orgMd5, 1, $this->hasWatermark);
        }else{
            $this->name = substr($exists, strripos($exists, '/')+1);
            $this->name = substr($this->name, 0, strripos($this->name, '.'));
        }

        $this->response[] = $Core->imagesStorage.$this->folderNumber.'/'.$this->name.'.'.$this->defaultFormat;
    }

    public function processResized(){
        global $Core;

        $this->resize();

        //add watermark if needed
        if($Core->db->result("SELECT `watermark` FROM `{$Core->dbName}`.`images` WHERE `hash` = '{$this->orgMd5}' AND `org` = 1")){
            $this->addWatermark();
            $this->hasWatermark = 1;
        }

        $file = $this->imagick->__toString();

        //check if resized image exists and upload is on
        //!!! imagick __toString returns different string from file_get_contents

        if(is_file($Core->imagesDir.$this->currentFolder.$this->name.'.'.$this->defaultFormat)){
            if(!$this->upload){
                $this->show($file);
            }

            $this->response[] = urldecode($Core->imagesWebDir.$this->currentFolder.$this->name.'.'.$this->defaultFormat);
            return;
        }
        $this->insertImage($Core->imagesDir.$this->currentFolder, $this->name, md5($file), NULL, $this->hasWatermark);

        if(!$this->upload){
            $this->show($file);
        }
        $this->response[] = urldecode($Core->imagesWebDir.$this->currentFolder.$this->name.'.'.$this->defaultFormat);
        return;
    }

    public function insertImage($folder, $name, $md5, $isOrg = 0, $watermark = 0){
        global $Core;

        if(!is_dir($folder)){
            mkdir($folder, 0755, true);
        }

        $destination = $folder.$name.'.'.$this->defaultFormat;
        $this->imagick->writeImage($destination);

        $name = $Core->db->escape($name);
        $isOrg = intval($isOrg);
        if(!$isOrg){
            $destination = str_ireplace($Core->imagesDir, '', $Core->imagesWebDir.$destination);
        }
        $destination = $Core->db->escape($destination);

        $Core->db->query("INSERT INTO `{$Core->dbName}`.`images`(`name`, `hash`, `src`, `org`, `watermark`) VALUES('{$name}', '{$md5}', '{$destination}', '{$isOrg}', '{$watermark}')");
    }

    public function addWatermark(){
        global $Core;

        //check if watermark file exists
        if(!is_file($this->watermark)){
            throw new Exception($Core->language->error_invalid_watermark_file);
        }
        // Open the watermark
        $watermark = new Imagick();
        $watermark->readImage($this->watermark);

        // Overlay the watermark on the original image
        $im_d = $this->imagick->getImageGeometry();
        $im_w = $im_d['width'];
        $im_h = $im_d['height'];

        $watermark_d = $watermark->getImageGeometry();
        $watermark_w = $watermark_d['width'];
        $watermark_h = $watermark_d['height'];

        $x_loc = $im_w/2 - $watermark_w/2;
        $y_loc = $im_h/2 - $watermark_h/2;

        $this->imagick->compositeImage($watermark, imagick::COMPOSITE_OVER, $x_loc, $y_loc);
    }

    public function resize()
    {
        if (!$this->width && !$this->height) {
            return false;
        }

        //get  image size
        $cw = $this->imagick->getImageWidth();
        $ch = $this->imagick->getImageHeight();
        //get image resize ratio
        $wr = $this->width / $cw;
        $hr = $this->height / $ch;
        //resizes and crops
        if ($this->sizeType == 'fixed') {
            if($wr >= $hr){
                $nh = floor($ch * $wr);
                $this->imagick->resizeImage($this->width, null, imagick::FILTER_LANCZOS,1);

                if ($this->height != 0 && $nh > $this->height) {
                    $this->imagick->cropImage($this->width, $this->height, 0, floor(($nh-$this->height)/2));
                }
            } else {
                $nw = floor($cw * $hr);
                $this->imagick->resizeImage(null, $this->height, imagick::FILTER_LANCZOS,1);

                if ($this->width != 0 && $nw > $this->width) {
                    $this->imagick->cropImage($this->width, $this->height, ceil(($nw-$this->width) /2 ), 0);
                }
            }
        } else {
            if ($wr >= $hr) {
                if($cw > $this->width) {
                    $this->imagick->resizeImage($this->width, null, imagick::FILTER_LANCZOS, 1);
                }

                if ($this->height != 0 && $this->imagick->getImageHeight() > $this->height) {
                    $this->imagick->resizeImage(null, $this->height, imagick::FILTER_LANCZOS, 1);
                }
            } else {
                if ($ch > $this->height) {
                    $this->imagick->resizeImage(null, $this->height, imagick::FILTER_LANCZOS,1);
                }

                if ($this->width != 0 && $this->imagick->getImageWidth() > $this->width) {
                    $this->imagick->resizeImage($this->width, null, imagick::FILTER_LANCZOS, 1);
                }
            }
        }
    }

    public function getName(){
        global $Core;

        $name = $Core->globalfunctions->getUrl($this->name);
        $name = $Core->globalfunctions->getHref($name, 'images', 'name');

        $this->name = $name;

        return $name;
    }

    public function setImagick($file){
        $this->imagick = new Imagick($file);
    }

    public function convertFormat(){
        if(strtolower($this->imagick->getImageFormat()) != $this->defaultFormat){
            $this->imagick->setImageFormat($this->defaultFormat);
        }
    }

    public function show($file){
        header("Content-Type: image/jpeg");
        echo $file;
        die;
    }

    public function exists($md5){
        global $Core;
        return $Core->db->result("SELECT `src` FROM `{$Core->dbName}`.`images` WHERE `hash` = '{$md5}'");
    }

    public function getIdBySrc($src, $validate = false){
        global $Core;
        $res = $Core->db->result("SELECT `id` FROM `{$Core->dbName}`.`images` WHERE `src` = '{$src}'");
        if($validate && empty($res)){
            throw new Error($Core->language->error_image_not_found);
        }
        return $res;
    }

    public function getSrcById($id, $validate = false){
        global $Core;
        $res = $Core->db->result("SELECT `src` FROM `{$Core->dbName}`.`images` WHERE `id` = {$id}");
        if($validate && empty($res)){
            throw new Error($Core->language->error_image_not_found);
        }
        return $res;
    }
}
?>