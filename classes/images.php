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
     * @var string
     * the field containing the names of the images in the table
     * default('name')
     */
    protected $imageNameField = 'name';

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
     * Current processed file location
     */
    private $currentFileLocation = '';

    /**
     * @var string
     * Current image size type
     */
    protected $sizeType = '';

    /**
     * @var string
     * Current image size Key
     */
    protected $sizeKey = '';

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
    protected $orgMd5 = '';

    /**
     * @var array
     * Response when uploading
     */
    private $response = array();

    /**
     * instance of the Imagick class
     */
    protected $imagick = false;

    /**
     * Creates a new instance of the Images class
     * @return array|string(JSON)
     */
    public function __construct()
    {
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

            foreach ($files as $file) {
                $this->currentFileLocation = $this->setUpload($file);

                $this->setImagick();

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
                if ($this->showResponse) {
                    echo json_encode($response);
                } else {
                    return $response;
                }
            }

            if ($this->dieWhenDone) {
                die;
            }
        }
        //create image on demand
        elseif (mb_substr($_SERVER['REQUEST_URI'], 0, mb_strlen($Core->imagesWebDir)) == $Core->imagesWebDir) {
            $this->setOnDemand();

            $this->setImagick();

            $this->orgMd5 = md5_file($this->currentFileLocation);

            $this->createOnDemand();
        }
    }

    /**
     * Checks the uploaded file
     * Throws BaseException if the uploaded file is not a valid image or bigger thnan $this->sizeLimit
     * Returns bool
     * @param array $file - temp file
     * @throws BaseException
     * @return bool
     */
    protected function checkUploadedImage(array $file)
    {
        global $Core;

        if (empty($file['tmp_name'])) {
            throw new BaseException("Image is empty");
        } elseif (!stristr(mime_content_type($file['tmp_name']), 'image')) {
            throw new BaseException("The file %%`{$file['name']}`%% is not valid image");
        } elseif ($this->sizeLimit && filesize($file['tmp_name']) > $this->sizeLimit) {
            throw new BaseException("Image must not be bigger than %%{$Core->GlobalFunctions->formatBytes($this->sizeLimit)}%%");
        }
    }

    /**
     * Sets uploaded file/s and sets the needed class variables
     * Returns the temp file location
     * You can override org size by presetting $this->sizeType and $this->sizeKey
     * and set the values in $this->allowedSizes
     * @param array $file - temp file
     * @return string $fileLocation - file location
     */
    protected function setUpload(array $file)
    {
        global $Core;

        $this->checkUploadedImage($file);

        $this->name = mb_substr($file['name'], 0, mb_strripos($file['name'], '.'));

        if (empty($this->sizeType)) {
            $this->sizeType = 'org';
        }

        if (empty($this->sizeKey)) {
            $this->sizeKey = 'org';
        }

        if(!isset($this->allowedSizes[$this->sizeType])) {
            throw new Exception('Allowed sizes type must be one of the following: `org`, `fixed`, `width`, `height`');
        }

        if(!isset($this->allowedSizes[$this->sizeType][$this->sizeKey])) {
            throw new Exception("Unallowed size key `{$this->sizeKey}` for size type `{$this->sizeType}`");
        }

        $fileLocation = urldecode($file['tmp_name']);

        $this->setImageDimensions();

        return $fileLocation;
    }

    /**
     * Stores the original image and sets the response data for the current file(image location)
     * Throws Exception if $Core->imagesStorage is equal to $Core->imagesDir and watermark is required
     * @throws Exception
     */
    private function upload()
    {
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

        if (!$this->allowDuplicate) {
            $exists = $this->isExistingImage($this->orgMd5);
        } else {
            $exists = false;
        }

        if (!$exists) {
            if ($this->watermarkFile) {
                $hasWatermark = 1;
            } else {
                $hasWatermark = 0;
            }

            $this->name = $Core->globalfunctions->getHref($Core->globalfunctions->getUrl($this->name), $this->tableName, $this->imageNameField);
            //Keep original witout watermark
            $this->insertImage($Core->imagesStorage.$addOrgFolder.$folderNumber.'/', $this->orgMd5, 1, $hasWatermark);
        } else {
            $this->name = $exists;
        }

        return $Core->imagesStorage.$addOrgFolder.$folderNumber.'/'.$this->name.'.'.$this->imageFormat;
    }

    /**
     * Sets required file and sets the needed class variables
     * Redirects no $Core->pageNotFoundLocation if the required file doesn't exists
     * Returns the original file location
     * @return string
     */
    private function setOnDemand()
    {
        global $Core;

        if (preg_match("~^".$Core->imagesWebDir."(width|height)/((?!0)[\d]+)/((?!0)[\d]+)/(.*)~",$_SERVER['REQUEST_URI'] ,$m)) {
            $this->sizeType            = $m[1];
            $this->sizeKey             = $m[2];
            $this->name                = urldecode(mb_substr($m[4], 0, mb_strripos($m[4], '.')));
            $this->currentFolder       = $m[1].'/'.$m[2].'/'.$m[3].'/';
            $this->currentFileLocation = urldecode($Core->imagesStorage.$m[3].'/'.$m[4]);

            if ($m[1] == 'width') {
                $this->width  = $m[2];
            } elseif ($m[1] == 'height') {
                $this->height = $m[2];
            }
        } elseif (preg_match("~^".$Core->imagesWebDir."(fixed)/((?!0)[\d]+-(?!0)[\d]+)/((?!0)[\d]+)/(.*)~",$_SERVER['REQUEST_URI'] ,$m)) {
            $this->sizeType            = $m[1];
            $this->sizeKey             = $m[2];

            $ratio                     = explode('-', $m[2]);
            $this->width               = $ratio[0];
            $this->height              = $ratio[1];

            $this->name                = urldecode(mb_substr($m[4], 0, mb_strripos($m[4], '.')));
            $this->currentFolder       = $m[1].'/'.$m[2].'/'.$m[3].'/';
            $this->currentFileLocation = urldecode($Core->imagesStorage.$m[3].'/'.$m[4]);
        } elseif (preg_match("~^".$Core->imagesWebDir."(org)/((?!0)[\d]+)/(.*)~",$_SERVER['REQUEST_URI'] ,$m)) {
            $this->sizeType            = 'org';
            $this->sizeKey             = 'org';
            $this->name                = urldecode(mb_substr($m[3], 0, mb_strripos($m[3], '.')));
            $this->currentFolder       = $m[1].'/'.$m[2].'/';
            $this->currentFileLocation = urldecode($Core->imagesStorage.$m[2].'/'.$m[3]);
        } else {
            //invalid file location or size type
            $Core->doOrDie();
        }

        if (!is_file($this->currentFileLocation)) {
            //unexisting file
            $Core->doOrDie();
        }

        $this->setImageDimensions();
    }

    /**
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
    private function createOnDemand()
    {
        global $Core;

        $this->resize();

        if ($this->isWatermarkRequired()) {
            $this->addWatermark();

            $hasWatermark = 1;
        } else {
            $hasWatermark = 0;
        }

        $file = $this->imagick->__toString();

        $fileLocation = $this->insertImage($Core->imagesDir.$this->currentFolder, md5($file), 0, $hasWatermark);

        $this->previewImage($fileLocation);
    }

    /**
     * Saves image image file and stores its info in $this->tableName
     * @param string $folder - the destination of the file
     * @param string $md5 - md5 hash of the file
     * @param int $isOrg - flag showing is the file the original image
     * @param int $isOrg - flag showing if the file must have watermark when it's not the original.
     * @return $fileLocation - the location of the created file
     * If it's the original, his children must have watermark
     */
    private function insertImage(string $folder, string $md5, int $isOrg = null, int $watermark = null)
    {
        global $Core;

        if ($isOrg === null) {
            $isOrg = 0;
        }

        if ($watermark === null) {
            $watermark = 0;
        }

        if(!is_dir($folder)){
            mkdir($folder, 0755, true);
        }

        $fileLocation = $folder.$this->name.'.'.$this->imageFormat;

        if ($this->imageFormat != 'gif') {
            $this->imagick->writeImage($fileLocation);
        } else {
            $this->imagick->writeImages($fileLocation, true);
        }

        if(!$isOrg){
            $fileLocation = str_ireplace($Core->imagesDir, '', $Core->imagesWebDir.$fileLocation);
        }

        $this->insert(
            array(
                'name'      => $this->name,
                'hash'      => $md5,
                'src'       => $fileLocation,
                'org'       => $isOrg,
                'watermark' => $watermark,
            )
        );

        return $fileLocation;
    }

    /**
     * Validates org size type, using the allowedSizes property
     * @throws Exception
     */
    private function validateOrgImageSizeType()
    {
        if (
            count($this->allowedSizes['org']) != 1 ||
            !isset($this->allowedSizes['org']['org']) ||
            !isset($this->allowedSizes['org']['org']['width']) ||
            !isset($this->allowedSizes['org']['org']['height']) ||
            (!is_bool($this->allowedSizes['org']['org']['width']) && !is_numeric($this->allowedSizes['org']['org']['width'])) ||
            (!is_bool($this->allowedSizes['org']['org']['height']) && !is_numeric($this->allowedSizes['org']['org']['height'])) ||
            (is_numeric($this->allowedSizes['org']['org']['width']) && intval($this->allowedSizes['org']['org']['width']) <= 0) ||
            (is_numeric($this->allowedSizes['org']['org']['height']) && intval($this->allowedSizes['org']['org']['height']) <= 0)
        ) {
             throw new Exception(
                get_class($this)." class error.".PHP_EOL.PHP_EOL.
                "allowedSizes['org'] value must be an array containing only one array with the following structure:".
                PHP_EOL.PHP_EOL."array('org' => array('width' => false or numeric, 'height' => false or numeric))"
             );
        }
    }

    /**
     * Validates width size type, using the allowedSizes property
     * @throws Exception
     */
    private function validateWidthImageSizeType()
    {
        foreach ($this->allowedSizes['width'] as $widthKey => $widthValue){
            if (!is_numeric($widthKey)) {
                throw new Exception(
                    get_class($this)." class error.".PHP_EOL.PHP_EOL.
                    "allowedSizes['width'] keys must be numeric (greater than 0)"
                );
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
               throw new Exception(
                   get_class($this)." class error.".PHP_EOL.PHP_EOL.
                   "allowedSizes['width'] value must be an array containing arrays with the following structure:".
                   PHP_EOL.PHP_EOL."'1920' => array('width' => '1920' (equal to parent array key), ".
                   "'height' => false (always false))"
               );
            }
        }
    }

   /**
     * Validates height size type, using the allowedSizes property
     * @throws Exception
     */
    private function validateHeightImageSizeType()
    {
        foreach ($this->allowedSizes['height'] as $heighthKey => $heightValue){
            if (!is_numeric($heighthKey)) {
                throw new Exception(
                    get_class($this)." class error.".PHP_EOL.PHP_EOL.
                    "allowedSizes['height'] keys must be numeric (greater than 0)"
                );
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
               throw new Exception(
                   get_class($this)." class error.".PHP_EOL.PHP_EOL.
                   "allowedSizes['height'] value must be an array containing arrays with the following structure:".
                   PHP_EOL.PHP_EOL."'1080' => array('width' => false (always false), ".
                   "'height' => '1080' (equal to parent array key))"
               );
            }
        }
    }

    /**
     * Validates fixed size type, using the allowedSizes property
     * @throws Exception
     */
    private function validateFixedImageSizeType()
    {
        foreach ($this->allowedSizes['fixed'] as $fixedKey => $fixedValue){
            $keyParts = explode('-', $fixedKey);

            if (
                count($keyParts) != 2 ||
                !is_numeric($keyParts[0]) ||
                !is_numeric($keyParts[1]) ||
                $keyParts[0] <= 0 ||
                $keyParts[1] <= 0
            ) {
                throw new Exception(
                    get_class($this)." class error.".PHP_EOL.PHP_EOL.
                    "nallowedSizes['fixed'] keys must be 'numeric (greater than 0)-numeric (greater than 0)' eg. '1920-1080'"
                );
            }

            if (
                !isset($fixedValue['width']) ||
                !isset($fixedValue['height']) ||
                !is_numeric($fixedValue['width']) ||
                !is_numeric($fixedValue['height']) ||
                intval($fixedValue['width']) != intval($keyParts[0]) ||
                intval($fixedValue['height']) != intval($keyParts[1])
            ) {
               throw new Exception(
                   get_class($this)." class error.".PHP_EOL.PHP_EOL.
                   "allowedSizes['fixed'] value must be an array containing arrays with the following structure:".PHP_EOL.
                   PHP_EOL."'1920-1080' => array('width' => 1920 (equal to the first part of parent array key), ".
                   "'height' => '1080' (equal to the second part of parent array key))"
               );
            }
        }
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
                throw new Exception('Allowed sizes type must be one of the following: `org`, `fixed`, `width`, `height`');
            }

            if ($sizeType == 'org') {
                $this->validateOrgImageSizeType();
            } elseif ($sizeType == 'width') {
                $this->validateWidthImageSizeType();
            } elseif ($sizeType == 'height') {
                $this->validateHeightImageSizeType();
            } elseif ($sizeType == 'fixed') {
                $this->validateFixedImageSizeType();
            }
        }
    }

    /**
     * Checks if watermark is required
     * @return bool
     */
    private function isWatermarkRequired()
    {
        if($this->getAll(0, "`hash` = '{$this->orgMd5}' AND `org` = 1 AND `watermark` = 1")) {
            return true;
        }

        return false;
    }

    /**
     * Adds watermark to current image
     */
    private function addWatermark()
    {
        global $Core;

        if (!is_file($this->watermarkFile)) {
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
     * Resizes current image by the setted params.
     * If $this->sizeType = 'org' and width anf height are bigger than 0 it will resize the image keeping the aspect ratio.
     * If $this->sizeType = 'org' and only width or height are bigger than 0 it will resize the image by give param.
     * If $this->sizeType = 'org' and only width or height are bigger than 0 it will resize the image by give param.
     * If $this->sizeType = 'width' or $this->sizeType = 'height' and is bigger than 0 it will resize the image by give param.
     * If $this->sizeType = 'fixed' and both width and height are bigger than 0 it will resize the image strict by give params.
     * In all $this->sizeType except 'fixed' if the given size is bigger than the original size and $this->allowSizeOverflow
     * is not set to true the original size will be used.
     */
    private function resize()
    {
        if (!$this->width && !$this->height && $this->sizeType == 'org') {
            return false;
        }

        //get image size
        $currentWidth = $this->imagick->getImageWidth();
        $currentHeight = $this->imagick->getImageHeight();

        //get image resize ratio
        $witdhRatio = $this->width / $currentWidth;
        $heightRatio = $this->height / $currentHeight;

        if ($this->imageFormat == 'gif') {
            $this->imagick = $this->imagick->coalesceImages();
        } elseif(in_array($this->imageFormat, array('png', 'svg'))) {
            $this->imagick->setBackgroundColor(new ImagickPixel('transparent'));

            if(in_array($this->imageFormat, array('svg'))) {
                $this->imagick->readImageBlob(file_get_contents($this->currentFileLocation));
            }
        }

        //resizes and crops
        if ($this->sizeType == 'fixed') {
            if($witdhRatio >= $heightRatio){
                $newHeight = floor($currentHeight * $witdhRatio);

                if ($this->imageFormat != 'gif') {
                    $this->imagick->resizeImage($this->width, null, imagick::FILTER_LANCZOS, 1);
                } else {
                    foreach ($this->imagick as $frame) {
                        $frame->thumbnailImage($this->width, null);
                        $frame->setImagePage($this->width, null, 0, 0);
                    }
                }

                if ($this->height != 0 && $newHeight > $this->height) {
                    if ($this->imageFormat != 'gif') {
                        $this->imagick->cropImage($this->width, $this->height, 0, floor(($newHeight - $this->height) / 2));
                    } else {
                        foreach ($this->imagick as $frame) {
                            $frame->thumbnailImage($this->width, $this->height);
                            $frame->setImagePage($this->width, $this->height, 0, 0);
                        }
                    }
                }
            } else {
                $newWidth = floor($currentWidth * $heightRatio);

                if ($this->imageFormat != 'gif') {
                    $this->imagick->resizeImage(null, $this->height, imagick::FILTER_LANCZOS, 1);
                } else {
                    foreach ($this->imagick as $frame) {
                        $frame->thumbnailImage(null, $this->height);
                        $frame->setImagePage(null, $this->height, 0, 0);
                    }
                }

                if ($this->width != 0 && $newWidth > $this->width) {
                    if ($this->imageFormat != 'gif') {
                        $this->imagick->cropImage($this->width, $this->height, ceil(($newWidth - $this->width) / 2 ), 0);
                    } else {
                        foreach ($this->imagick as $frame) {
                            $frame->thumbnailImage($this->width, $this->height);
                            $frame->setImagePage($this->width, $this->height, 0, 0);
                        }
                    }
                }
            }
        } else {
            if ($witdhRatio >= $heightRatio) {
                if ($currentWidth > $this->width || $this->allowSizeOverflow) {
                    if ($this->imageFormat != 'gif') {
                        $this->imagick->resizeImage($this->width, null, imagick::FILTER_LANCZOS, 1);
                    } else {
                        foreach ($this->imagick as $frame) {
                            $frame->thumbnailImage($this->width, null);
                            $frame->setImagePage($this->width, null, 0, 0);
                        }
                    }
                }

                if ($this->height != 0 && $this->imagick->getImageHeight() > $this->height) {
                    if ($this->imageFormat != 'gif') {
                        $this->imagick->resizeImage(null, $this->height, imagick::FILTER_LANCZOS, 1);
                    } else {
                        foreach ($this->imagick as $frame) {
                            $frame->thumbnailImage(null, $this->height);
                            $frame->setImagePage(null, $this->height, 0, 0);
                        }
                    }
                }
            } else {
                if ($currentHeight > $this->height || $this->allowSizeOverflow) {

                    if ($this->imageFormat != 'gif') {
                        $this->imagick->resizeImage(null, $this->height, imagick::FILTER_LANCZOS, 1);
                    } else {
                        foreach ($this->imagick as $frame) {
                            $frame->thumbnailImage(null, $this->height);
                            $frame->setImagePage(null, $this->height, 0, 0);
                        }
                    }
                }

                if ($this->width != 0 && $this->imagick->getImageWidth() > $this->width) {
                    if ($this->imageFormat != 'gif') {
                        $this->imagick->resizeImage($this->width, null, imagick::FILTER_LANCZOS, 1);
                    } else {
                        foreach ($this->imagick as $frame) {
                            $frame->thumbnailImage($this->width, null);
                            $frame->setImagePage($this->width, null, 0, 0);
                        }
                    }
                }
            }
        }

        if ($this->imageFormat == 'gif') {
            $this->imagick = $this->imagick->deconstructImages();
        }
    }

    /**
     * Sets Imagick by $this->currentFileLocation
     */
    private function setImagick()
    {
        $this->imagick = new Imagick($this->currentFileLocation);

        $this->setImageFormat();
    }

    /**
     * If $this->imageFormat is empty, sets it to the format of $this->currentFileLocation.
     * If the format of $this->currentFileLocation is not the same as $this->imageFormat, sets the Imagick format to $this->imageFormat.
     * @param strin $fileLocation
     */
    private function setImageFormat()
    {
        if (empty($this->imageFormat)) {
            $fileMimeType      = mime_content_type($this->currentFileLocation);
            $this->imageFormat = mb_substr($fileMimeType, mb_strrpos($fileMimeType, '/') + 1);
        }

        if (stristr($this->imageFormat, 'svg')) {
            $this->imageFormat = 'svg';
        }

        $this->imageFormat = mb_strtolower($this->imageFormat);

        if (mb_strtolower($this->imagick->getImageFormat()) != $this->imageFormat) {
            $this->imagick->setImageFormat($this->imageFormat);
        }
    }

    /**
     * Previews image by given file location
     * @param string $fileLocation - location of the file
     */
    private function previewImage(string $fileLocation)
    {
        header("Content-Type: image/".$this->imageFormat);

        readfile(GLOBAL_PATH.PROJECT_PATH.(mb_substr($fileLocation, 1)));

        die;
    }

    /**
     * Checks if image exists in database by given hash(md5 of the file)
     * @param string $md5 - the file md5()
     * @return string - if file exists returns it's file name
     */
    private function isExistingImage(string $md5)
    {
        if ($res = $this->getAll(0, "`hash` = '{$md5}'")) {
            return current($res)['name'];
        }

        return '';
    }

    /**
     * Returns image id by given source
     * @param string src - the image source in the database
     * @return int
     */
    public function getIdBySrc(string $src)
    {
        if ($res = $this->getAll(0, "`src` = '{$src}'")) {
            return intval(current($res)['id']);
        }

        return 0;
    }

    /**
     * Returns image src by given id
     * @param int id - the image id in the database
     * @return string
     */
    public function getSrcById(int $id)
    {
        if ($res = $this->getById($id)) {
            $res['src'];
        }

        return '';
    }
}
