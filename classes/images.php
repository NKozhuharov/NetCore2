<?php
class Images extends Base{
    protected $tableName = 'images';

    protected $allowedSizes = array(
        'org' => array(
            'org' => array('width' => false, 'height' => false),
        ),
        'width' => array(
            '1920' => array('width' => 1920, 'height' => false)
            ,'50' => array('width' => 50, 'height' => false)
        ),
        'height'=> array(
            '1080' => array('width' => false, 'height' => 1080)
        ),
        'fixed' => array(
            '1920-1080' => array('width' => 1920, 'height' => 1080)
        ),
        /*false => array(
            false => array('width' => false, 'height' => false)
        )*/
    );

    protected $sizeLimit      = 10485760; //10MB
    protected $sizeLimitText  = '10MB';
    protected $uploadName     = 'imageUpload'; //$_REQUEST flag for showing it is upload
    protected $showResponse   = true;
    protected $allowDuplicate = false;
    protected $dieWhenDone    = true;
    protected $defaultFormat  = 'jpg';
    private $currentFolder  = false;
    private $folderNumber   = false;
    private $name           = false;
    private $orgMd5         = false;
    private $response       = array();
    private $width          = false;
    private $height         = false;
    private $sizeType       = false;
    private $sizeKey            = false;
    private $imagick        = false;
    protected $watermarkFile      = false; //'/var/www/site/www/img/watermark.png';

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
                if($this->defaultFormat){
                    $this->convertFormat();
                }
                $this->orgMd5 = md5($this->imagick->__toString());

                $this->upload();
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
            $fileLocation = $this->checks();
            $this->setImagick($fileLocation);
            $this->orgMd5 = md5_file($fileLocation);
            $this->processResized();
        }
    }

    public function checks(array $file = null){
        global $Core;

        if(!isset($_SERVER['REQUEST_URI'])){
            throw new Exception('Unallowed action');
        }elseif($file){
            if (empty($file['tmp_name'])) {
                throw new BaseException("Image is empty");
            } elseif (!stristr(mime_content_type($file['tmp_name']), 'image')) {
                throw new BaseException("The file %%`{$file['name']}`%% is not valid");
            } elseif ($this->sizeLimit && filesize($file['tmp_name']) > $this->sizeLimit) {
                throw new BaseException("Image must not be bigger than %%{$this->sizeLimitText}%%");
            }

            $this->folderNumber  = $Core->globalfunctions->getFolder($Core->imagesStorage, true);
            $this->name          = substr($file['name'], 0, strripos($file['name'], '.'));
            $fileLocation        = urldecode($file['tmp_name']);
            $this->sizeType      = 'org';
            $this->sizeKey       = 'org';
            $this->currentFolder = $this->sizeType.'/'.$this->folderNumber.'/';
        } elseif (preg_match("~^".$Core->imagesWebDir."(width|height)/([\d]+)/([\d]+)/(.*)~",$_SERVER['REQUEST_URI'] ,$m)){
            $this->sizeType      = $m[1];
            $this->sizeKey       = $m[2];
            $this->name          = urldecode(substr($m[4], 0, strripos($m[4], '.')));
            $this->currentFolder = $m[1].'/'.$m[2].'/'.$m[3].'/';
            $fileLocation        = urldecode($Core->imagesStorage.$m[3].'/'.$m[4]);

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
            $this->sizeType      = $m[1];
            $this->sizeKey       = $m[2];
            $this->name          = urldecode(substr($m[4], 0, strripos($m[4], '.')));
            $this->currentFolder = $m[1].'/'.$m[2].'/'.$m[3].'/';
            $fileLocation        = urldecode($Core->imagesStorage.$m[3].'/'.$m[4]);

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
            $this->sizeType      = 'org';
            $this->sizeKey       = 'org';
            $this->name          = urldecode(substr($m[3], 0, strripos($m[3], '.')));
            $this->currentFolder = $m[1].'/'.$m[2].'/';
            $fileLocation        = urldecode($Core->imagesStorage.$m[2].'/'.$m[3]);

            if(!is_file($fileLocation)){
                //unexisting file
                $Core->doOrDie();
            }
        }else{
            //unexisting file
            $Core->doOrDie();
        }

        if ($this->allowedSizes) {
            if (!isset($this->allowedSizes[$this->sizeType])) {
                throw new Exception($Core->language->unallowed_action);
            } elseif ($this->allowedSizes && !isset($this->allowedSizes[$this->sizeType][$this->sizeKey])) {
                throw new Exception($Core->language->unallowed_image_size);
            } else {
                $this->width  = $this->allowedSizes[$this->sizeType][$this->sizeKey]['width'];
                $this->height = $this->allowedSizes[$this->sizeType][$this->sizeKey]['height'];
            }
        }

        if(empty($this->defaultFormat)){
            $fileType            = mime_content_type($fileLocation);
            $this->defaultFormat = substr($fileType, strrpos($fileType, '/') + 1);
        }

        return $fileLocation;
    }

    /**
     * Stores the original image and sets the responce data for the current file(image location)
     */
    public function upload(){
        global $Core;

        if(!$this->allowDuplicate){
            $exists = $this->isExistingImage($this->orgMd5);
        }else{
            $exists = false;
        }

        $this->resize();

        if(!$exists){
            if($this->watermarkFile){
                $hasWatermark = 1;
            }else{
                $hasWatermark = 0;
            }

            $this->setName();

            //Keep original witout watermark
            $this->insertImage($Core->imagesStorage.$this->folderNumber.'/', $this->orgMd5, 1, $hasWatermark);
        }else{
            $this->name = substr($exists, strripos($exists, '/')+1);
            $this->name = substr($this->name, 0, strripos($this->name, '.'));
        }

        $this->response[] = $Core->imagesStorage.$this->folderNumber.'/'.$this->name.'.'.$this->defaultFormat;
    }

    /**
     * Resizes the image, then stores the file, saves the info in $this->tableName and then show a preview of the image
     */
    public function processResized(){
        global $Core;

        $this->resize();

        if (isWatermarkRequired()) {
            $this->addWatermark();

            $hasWatermark = 1;
        } else {
            $hasWatermark = 0;
        }

        //!!! imagick __toString returns different string from file_get_contents
        $file = $this->imagick->__toString();

        $this->insertImage($Core->imagesDir.$this->currentFolder, md5($file), 0, $hasWatermark);

        $this->previewImage($file);
    }

    /**
     * Checks if watermark is required
     * @return bool
     */
    public function isWatermarkRequired()
    {
        if($this->getAll(null, "`hash` = '{$this->orgMd5}' AND `org` = 1")) {
            return true;
        }

        return false;
    }

    /**
     * Saves image image file and stores its info in $this->tableName
     * @param string $folder - the destination of the file
     * @param string $md5 - md5 hash of the file
     * @param int $isOrg - flag showing is the file the original image
     * @param int $isOrg - flag showing if the file is having watermark when it's not the original or if it's the original that his children should have watermark
     */
    public function insertImage(string $folder, string $md5, int $isOrg = 0, int $watermark = 0)
    {
        global $Core;

        if(!is_dir($folder)){
            mkdir($folder, 0755, true);
        }

        $destination = $folder.$this->name.'.'.$this->defaultFormat;

        $this->imagick->writeImage($destination);

        if(!$isOrg){
            $destination = str_ireplace($Core->imagesDir, '', $Core->imagesWebDir.$destination);
        }

        $this->insert(
            array(
                'name'      => $this->name,
                'hash'      => $md5,
                'src'       => $destination,
                'org'       => $isOrg,
                'watermark' => $watermark,
            )
        );
    }

    /**
     * Adds watermark to current imagick file
     */
    public function addWatermark()
    {
        global $Core;

        if(!is_file($this->watermarkFile)){
            throw new Exception('Unexisting watermark file');
        }

        // Open the watermark
        $watermark = new Imagick();
        $watermark->readImage($this->watermarkFile);

        // Overlay the watermark on the original image
        $im_d = $this->imagick->getImageGeometry();
        $im_w = $im_d['width'];
        $im_h = $im_d['height'];

        $watermark_d = $watermark->getImageGeometry();
        $watermark_w = $watermark_d['width'];
        $watermark_h = $watermark_d['height'];

        $x_loc = $im_w/2 - $watermark_w/2;
        $y_loc = $im_h/2 - $watermark_h/2;

        //cereate image with watermark
        $this->imagick->compositeImage($watermark, imagick::COMPOSITE_OVER, $x_loc, $y_loc);
    }

    /**
     * Resizes current Imagick file by the setted params
     */
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

    /**
     * Sets the name for the curent Imagick file
     */
    public function setName(){
        global $Core;

        $name = $Core->globalfunctions->getUrl($this->name);
        $name = $Core->globalfunctions->getHref($name, 'images', 'name');

        $this->name = $name;
    }

    /**
     * Sets Imagick  by given file name
     */
    public function setImagick(string $file){
        $this->imagick = new Imagick($file);
    }

    /**
     * Converts the curent Imagick file if its format is not equal to $this->defaultFormat
     */
    public function convertFormat(){
        if(strtolower($this->imagick->getImageFormat()) != $this->defaultFormat){
            $this->imagick->setImageFormat($this->defaultFormat);
        }
    }

    /**
     * Previews image
     * @param string $file - contents of the file
     */
    public function previewImage(string $file){
        header("Content-Type: image/".$this->defaultFormat);

        echo $file;
        die;
    }

    /**
     * Checks if image exists in database by given hash(md5 of the file)
     * @param string $md5 - the file md5()
     * @return bool
     */
    public function isExistingImage(string $md5){
        if ($this->getAll(null, "`hash` = '{$md5}'")) {
            return true;
        }

        return false;
    }

    /**
     * Returns image id by given source
     * @param string src
     * @return int
     */
    public function getIdBySrc(string $src){
        if ($res = $this->getAll(null, "`src` = '{$src}'")) {
            return intval(current($res['id']));
        }

        return 0;
    }

    /**
     * Returns image src by given id
     * @param int id
     * @return string
     */
    public function getSrcById(int $id){
        if ($res = $this->getById($id)) {
            $res['src'];
        }

        return '';
    }
}
