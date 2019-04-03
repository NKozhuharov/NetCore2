<?php
class Images extends Base
{
    /**
     * @var string
     * Table containing images info
     */
    protected $tableName = 'images';

    /**
     * @var array
     * The allowed image sizes containig the type ofthe image dimensions
     * If is empty all sizes are allowed except those with 0 or less
     * It is recommended to be set
     * Follow the structure below in order the script to work correctly
     */
    protected $allowedSizes = array(
        'org' => array(
            'org' => array('width' => false, 'height' => false),
        ),
        'width' => array(
            '1920' => array('width' => '1920', 'height' => false),
        ),
        'height'=> array(
            '1080' => array('width' => false, 'height' => '1080'),
        ),
        'fixed' => array(
            '1920-1080' => array('width' => '1920', 'height' => '1080'),
        ),
    );

    /**
     * @var int
     * Maximum image sizae in bytes
     */
    protected $sizeLimit = 10485760; //10MB

    /**
     * @var string
     * $_REQUEST flag for showing it is upload
     */
    protected $uploadName = 'imageUpload';

    /**
     * @var bool
     * Param when uploading images.
     * If set to true it will show the response. If count of the response is 1 it will show string otherwise JSON encoded array
     * If set to false it will return the response. If count of the response is 1 it will retur string otherwise array
     */
    protected $showResponse = true;

    /**
     * @var bool
     * If set to true it will allow uploading duplicated images otherwise it will use already inserted image
     * Not recommended to be set to true
     */
    protected $allowDuplicate = false;

    /**
     * @var bool
     * If is set to true the script will die after execution
     */
    protected $dieWhenDone = true;

    /**
     * @var string
     * Default file format. If is not empty all files will be converted to it.
     */
    protected $imageFormat = 'jpg';

    /**
     * @var string
     * Location for watermark image
     */
    protected $watermarkFile = '';

    /**
     * @var string
     * Current image folder
     */
    private $currentFolder = '';

    /**
     * @var string
     * Current image size type
     */
    private $sizeType = '';

    /**
     * @var string
     * Current image size Key
     */
    private $sizeKey = '';

    /**
     * @var int
     * Current image width
     */
    private $width = 0;

    /**
     * @var int
     * Current image height
     */
    private $height = 0;

    /**
     * @var bool
     * If is set to true allows created image size to be bigger than the original image size.
     * Else if the given size is bigger than the original image size, original image size will be used
     */
    private $allowSizeOverflow = false;

    /**
     * @var string
     * Current image name
     */
    private $name = '';

    /**
     * @var string
     * Original image md5 to store when uploading or use when creating on demand
     */
    private $orgMd5 = '';

    /**
     * @var array
     * Response when uploading
     */
    private $response = array();

    /**
     * instance of the Imagick class
     */
    private $imagick = false;

    /**
     * @return array or JSON
     */
    public function __construct()
    {
        #header('Content-Type: image/jpg');
        #header('X-Accel-Redirect: /images_org'.urldecode($_REQUEST['image']));
        #die;
        #check for watermark when org
        #check for table
        #base
        #nginx
        #if storage == webdir and watermark

        global $Core;

        if (!isset($_SERVER['REQUEST_URI'])) {
            throw new Exception('Unallowed action');
        }

        if($this->allowedSizes){
            $this->checkAllowedSizes();
        }

        //image upload
        if (isset($_FILES[$this->uploadName], $_FILES[$this->uploadName]['tmp_name'])) {
            if (is_array($_FILES[$this->uploadName]['tmp_name'])) {
                $files = $Core->globalfunctions->reArrangeRequestFiles($_FILES[$this->uploadName]);
            } else {
                $files = array($_FILES[$this->uploadName]);
            }

            $response = array();

            foreach($files as $file) {
                $fileLocation = $this->setUpload($file);

                $this->setImagick($fileLocation);

                $this->setImageFormat($fileLocation);

                $this->orgMd5 = md5($this->imagick->__toString());

                $response[] = $this->upload();
            }

            if (count($response) == 1) {
                if ($this->showResponse) {
                    echo $response[0];
                } else {
                    return $response[0];
                }
            } else {
                if($this->showResponse){
                    echo json_encode($response);
                }else{
                    return $response;
                }
            }

            if ($this->dieWhenDone) {
                die;
            }
        }
        //create image on demand
        elseif (substr($_SERVER['REQUEST_URI'], 0, strlen($Core->imagesWebDir)) == $Core->imagesWebDir) {
            $fileLocation = $this->setOnDemand();

            $this->setImagick($fileLocation);

            $this->setImageFormat($fileLocation);

            $this->orgMd5 = md5_file($fileLocation);

            $this->createOnDemand();
        }
    }

    /**
     * Sets uploaded file/s and sets the needed class variables
     * Throws Exception if the uploaded file is not a valid image or bigger thnan $this->sizeLimit
     * @param string $file - temp file location
     * @throws Exception
     */
    private function setUpload(array $file)
    {
        global $Core;

        if (empty($file['tmp_name'])) {
            throw new BaseException("Image is empty");
        } elseif (!stristr(mime_content_type($file['tmp_name']), 'image')) {
            throw new BaseException("The file %%`{$file['name']}`%% is not valid");
        } elseif ($this->sizeLimit && filesize($file['tmp_name']) > $this->sizeLimit) {
            throw new BaseException("Image must not be bigger than %%{$Core->GlobalFunctions->formatBytes($this->sizeLimit)}%%");
        }

        $this->name          = substr($file['name'], 0, strripos($file['name'], '.'));
        $this->sizeType      = 'org';
        $this->sizeKey       = 'org';
        $fileLocation        = urldecode($file['tmp_name']);

        $this->setImageDimensions();

        return $fileLocation;
    }

    /**
     * Stores the original image and sets the responce data for the current file(image location)
     * Throws Exception if $Core->imagesStorage is equal to $Core->imagesDir and watermark is required
     * @throws Exception
     */
    public function upload(){
        global $Core;

        $addOrgFolder = '';

        if ($Core->imagesStorage === $Core->imagesDir) {
            if ($this->watermarkFile) {
               throw new Exception('Adding watermark is not allowed when `$Core->imagesStorage` is the same as `$Core->imagesDir`');
            }

            $addOrgFolder = 'org/';
        }

        $folderNumber = $Core->globalfunctions->getFolder($Core->imagesStorage.$addOrgFolder, true);

        $this->resize();

        if(!$this->allowDuplicate){
            $exists = $this->isExistingImage($this->orgMd5);
        }else{
            $exists = false;
        }

        if(!$exists){
            if($this->watermarkFile){
                $hasWatermark = 1;
            }else{
                $hasWatermark = 0;
            }

            $this->name = $Core->globalfunctions->getHref($Core->globalfunctions->getUrl($this->name), $this->tableName, 'name');
            //Keep original witout watermark
            $this->insertImage($Core->imagesStorage.$addOrgFolder.$folderNumber.'/', $this->orgMd5, 1, $hasWatermark);
        }else{
            $this->name = $exists;
        }

        return $Core->imagesStorage.$addOrgFolder.$folderNumber.'/'.$this->name.'.'.$this->imageFormat;
    }

     /**
     * Sets required file and sets the needed class variables
     * Redirects no $Core->pageNotFoundLocation if the required file doesn't exists
     */
    public function setOnDemand(){
        global $Core;

        if (preg_match("~^".$Core->imagesWebDir."(width|height)/((?!0)[\d]+)/((?!0)[\d]+)/(.*)~",$_SERVER['REQUEST_URI'] ,$m)) {
            $this->sizeType      = $m[1];
            $this->sizeKey       = $m[2];
            $this->name          = urldecode(substr($m[4], 0, strripos($m[4], '.')));
            $this->currentFolder = $m[1].'/'.$m[2].'/'.$m[3].'/';
            $fileLocation        = urldecode($Core->imagesStorage.$m[3].'/'.$m[4]);

            if ($m[1] == 'width') {
                $this->width  = $m[2];
            } elseif ($m[1] == 'height') {
                $this->height = $m[2];
            }
        } elseif (preg_match("~^".$Core->imagesWebDir."(fixed)/((?!0)[\d]+-(?!0)[\d]+)/((?!0)[\d]+)/(.*)~",$_SERVER['REQUEST_URI'] ,$m)) {
            $this->sizeType      = $m[1];
            $this->sizeKey       = $m[2];

            $ratio               = explode('-', $m[2]);
            $this->width         = $ratio[0];
            $this->height        = $ratio[1];

            $this->name          = urldecode(substr($m[4], 0, strripos($m[4], '.')));
            $this->currentFolder = $m[1].'/'.$m[2].'/'.$m[3].'/';
            $fileLocation        = urldecode($Core->imagesStorage.$m[3].'/'.$m[4]);
        } elseif (preg_match("~^".$Core->imagesWebDir."(org)/((?!0)[\d]+)/(.*)~",$_SERVER['REQUEST_URI'] ,$m)) {
            $this->sizeType      = 'org';
            $this->sizeKey       = 'org';
            $this->name          = urldecode(substr($m[3], 0, strripos($m[3], '.')));
            $this->currentFolder = $m[1].'/'.$m[2].'/';
            $fileLocation        = urldecode($Core->imagesStorage.$m[2].'/'.$m[3]);
        } else {
            //invalid file location or size type
            $Core->doOrDie();
        }

        if (!is_file($fileLocation)) {
            //unexisting file
            $Core->doOrDie();
        }

        $this->setImageDimensions();

        return $fileLocation;
    }

    /*
     * Sets the current image width and height and check if the setted values are allowed
     * Redirects no $Core->pageNotFoundLocation if type, width or height is not allowed
     */
    private function setImageDimensions()
    {
        global $Core;

        if ($this->allowedSizes) {
            if (!isset($this->allowedSizes[$this->sizeType]) || !isset($this->allowedSizes[$this->sizeType][$this->sizeKey])) {
                $Core->doOrDie();
            } else {
                $this->width  = $this->allowedSizes[$this->sizeType][$this->sizeKey]['width'];
                $this->height = $this->allowedSizes[$this->sizeType][$this->sizeKey]['height'];
            }
        }
    }

    /**
     * Resizes the image, then stores the file, saves the info in $this->tableName and then shows a preview of the image
     * Warning: imagick __toString returns different string from file_get_contents
     */
    public function createOnDemand(){
        global $Core;

        $this->resize();

        if ($this->isWatermarkRequired()) {
            $this->addWatermark();

            $hasWatermark = 1;
        } else {
            $hasWatermark = 0;
        }

        $file = $this->imagick->__toString();

        $this->insertImage($Core->imagesDir.$this->currentFolder, md5($file), 0, $hasWatermark);

        $this->previewImage($file);
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

        $destination = $folder.$this->name.'.'.$this->imageFormat;

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
     * Static checks when $this->alloweedSizes is not empty
     * @throws Exception
     */
    private function checkAllowedSizes()
    {
        if (!is_array($this->allowedSizes)) {
            throw new Exception('Allowed sizes must be an array');
        }

        foreach($this->allowedSizes as $sizeType => $sizeValue) {
            if (!is_array($sizeValue)) {
                 throw new Exception('Allowed sizes `'.$sizeType.'` value must be an array');
            }

            if (!in_array($sizeType, array('org', 'fixed', 'width', 'height'))) {
                throw new Exception('Allowed sizes key must be one of the following: `org`, `fixed`, `width`, `height`');
            }

            if ($sizeType == 'org') {
                if (
                    count($this->allowedSizes[$sizeType]) != 1 ||
                    !isset($this->allowedSizes[$sizeType]['org']) ||
                    !isset($this->allowedSizes[$sizeType]['org']['width']) ||
                    !isset($this->allowedSizes[$sizeType]['org']['height']) ||
                    (!is_bool($this->allowedSizes[$sizeType]['org']['width']) && !is_numeric($this->allowedSizes[$sizeType]['org']['width'])) ||
                    (!is_bool($this->allowedSizes[$sizeType]['org']['height']) && !is_numeric($this->allowedSizes[$sizeType]['org']['height'])) ||
                    (is_numeric($this->allowedSizes[$sizeType]['org']['width']) && intval($this->allowedSizes[$sizeType]['org']['width']) <= 0) ||
                    (is_numeric($this->allowedSizes[$sizeType]['org']['height']) && intval($this->allowedSizes[$sizeType]['org']['height']) <= 0)


                ) {
                     throw new Exception(get_class($this)." class error.\r\n\r\nallowedSizes['org'] value must be an array containing only one array with the following structure:\r\n\r\narray('org' => array('width' => false or numeric, 'height' => false or numeric))");
                }
            } elseif ($sizeType == 'width') {
                foreach ($this->allowedSizes[$sizeType] as $widthKey => $widthValue){
                    if (!is_numeric($widthKey)) {
                        throw new Exception(get_class($this)." class error.\r\n\r\nallowedSizes['width'] keys must be numeric (greater than 0)");
                    }

                    if (
                        !isset($widthValue['width']) ||
                        !isset($widthValue['height']) ||
                        !is_numeric($widthValue['width']) ||
                        $widthValue['height'] !== false ||
                        intval($widthKey) != intval($widthValue['width']) ||
                        intval($widthKey) <= 0 ||
                        intval($widthValue['width']) <= 0
                    ) {

                       throw new Exception(get_class($this)." class error.\r\n\r\nallowedSizes['width'] value must be an array containing arrays with the following structure:\r\n\r\n'1920' => array('width' => '1920' (equal to parent array key), 'height' => false (always false))");
                    }
                }
            } elseif ($sizeType == 'height') {
                foreach ($this->allowedSizes[$sizeType] as $heighthKey => $heightValue){
                    if (!is_numeric($widthKey)) {
                        throw new Exception(get_class($this)." class error.\r\n\r\n allowedSizes['height'] keys must be numeric (greater than 0)");
                    }

                    if (
                        !isset($heightValue['height']) ||
                        !isset($heightValue['width']) ||
                        !is_numeric($heightValue['height']) ||
                        $heightValue['width'] !== false ||
                        intval($heighthKey) != intval($heightValue['height']) ||
                        intval($heighthKey) <= 0 ||
                        intval($heightValue['height']) <= 0
                    ) {

                       throw new Exception(get_class($this)." class error.\r\n\r\nallowedSizes['height'] value must be an array containing arrays with the following structure:\r\n\r\n'1080' => array('width' => false (always false), 'height' => '1080' (equal to parent array key))");
                    }
                }
            } elseif ($sizeType == 'fixed') {
                foreach ($this->allowedSizes[$sizeType] as $fixedKey => $fixedValue){
                    $keyParts = explode('-', $fixedKey);

                    if (
                        count($keyParts) != 2 ||
                        !is_numeric($keyParts[0]) ||
                        !is_numeric($keyParts[1]) ||
                        $keyParts[0] <= 0 ||
                        $keyParts[1] <= 0
                    ) {
                        throw new Exception(get_class($this)." class error.\r\n\r\nallowedSizes['fixed'] keys must be 'numeric (greater than 0)-numeric (greater than 0)' eg. '1920-1080'");
                    }

                    if (
                        !isset($fixedValue['width']) ||
                        !isset($fixedValue['height']) ||
                        !is_numeric($fixedValue['width']) ||
                        !is_numeric($fixedValue['height']) ||
                        intval($fixedValue['width']) != intval($keyParts[0]) ||
                        intval($fixedValue['height']) != intval($keyParts[1])
                    ) {

                       throw new Exception(get_class($this)." class error.\r\n\r\nallowedSizes['fixed'] value must be an array containing arrays with the following structure:\r\n\r\n'1920-1080' => array('width' => 1920 (equal to the first part of parent array key), 'height' => '1080' (equal to the second part of parent array key))");
                    }
                }
            }
        }
    }

    /**
     * Checks if watermark is required
     * @return bool
     */
    public function isWatermarkRequired()
    {
        if($this->getAll(null, "`hash` = '{$this->orgMd5}' AND `org` = 1 AND `watermark` = 1")) {
            return true;
        }

        return false;
    }

    /**
     * Adds watermark to current image
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
     * Resizes current image by the setted params
     * If $this->sizeType = 'org' and width anf height are bigger than 0 it will resize the image keeping the aspect ratio
     * If $this->sizeType = 'org' and only width or height are bigger than 0 it will resize the image by give param
     * If $this->sizeType = 'org' and only width or height are bigger than 0 it will resize the image by give param
     * If $this->sizeType = 'width' or $this->sizeType = 'height' and is bigger than 0 it will resize the image by give param
     * If $this->sizeType = 'fixed' and both width and height are bigger than 0 it will resize the image strict by give params
     * In all $this->sizeType except 'fixed' if the given size is bigger than the original size and $this->allowSizeOverflow is not set to true the original size will be used
     */
    public function resize()
    {
        if (!$this->width && !$this->height && $this->sizeType == 'org') {
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
                if($cw > $this->width || $this->allowSizeOverflow) {
                    $this->imagick->resizeImage($this->width, null, imagick::FILTER_LANCZOS, 1);
                }

                if ($this->height != 0 && $this->imagick->getImageHeight() > $this->height) {
                    $this->imagick->resizeImage(null, $this->height, imagick::FILTER_LANCZOS, 1);
                }
            } else {
                if ($ch > $this->height || $this->allowSizeOverflow) {
                    $this->imagick->resizeImage(null, $this->height, imagick::FILTER_LANCZOS,1);
                }

                if ($this->width != 0 && $this->imagick->getImageWidth() > $this->width) {
                    $this->imagick->resizeImage($this->width, null, imagick::FILTER_LANCZOS, 1);
                }
            }
        }
    }

    /**
     * Sets Imagick by given file name
     */
    public function setImagick(string $file){
        $this->imagick = new Imagick($file);
    }

    /**
     * If is empty $this->imageFormat sets it to the format of the given file location.
     * Else if the format of the given file is not the same as $this->imageFormat, sets the Imagick format to $this->imageFormat.
     * @param strin $fileLocation
     */
    private function setImageFormat(string $fileLocation){
        if(empty($this->imageFormat)){
            $fileMimeType            = mime_content_type($fileLocation);
            $this->imageFormat = substr($fileMimeType, strrpos($fileMimeType, '/') + 1);
        }

        if(strtolower($this->imagick->getImageFormat()) != strtolower($this->imageFormat)){
            $this->imagick->setImageFormat(strtolower($this->imageFormat));
        }
    }

    /**
     * Previews image
     * @param string $file - contents of the file
     */
    public function previewImage(string $file){
        header("Content-Type: image/".$this->imageFormat);

        die($file);
    }

    /**
     * Checks if image exists in database by given hash(md5 of the file)
     * @param string $md5 - the file md5()
     * @return string - if file exists returns it's file name
     */
    public function isExistingImage(string $md5){
        if ($res = $this->getAll(null, "`hash` = '{$md5}'")) {
            return current($res)['name'];
        }

        return '';
    }

    /**
     * Returns image id by given source
     * @param string src
     * @return int
     */
    public function getIdBySrc(string $src){
        if ($res = $this->getAll(null, "`src` = '{$src}'")) {
            return intval(current($res)['id']);
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
