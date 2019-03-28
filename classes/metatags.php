<?php
class MetaTags
{
    /**
     * @var string
     * The meta title
     */
    private $title;
    
    /**
     * @var string
     * The meta description
     */
    private $description;
    
    /**
     * @var string
     * The meta keywords
     */
    private $keywords;
    
    /**
     * @var string
     * The meta open graph title
     */
    private $og_title;
    
    /**
     * @var string
     * The meta open graph description
     */
    private $og_description;
    
    /**
     * @var string
     * The meta open graph type
     */
    private $og_type;
    
    /**
     * @var string
     * The meta open graph url
     */
    private $og_url;
    
    /**
     * @var string
     * The meta open graph image
     */
    private $og_image;
    
    /**
     * Attempts to get the a property of the meta class
     * Retursn empty string if the property is not found
     * @param string $property - the name of the propery
     * @return string
     */
    public function __get(string $property) 
    {
        $property = strtolower($property);
        if (isset($this->$property)) {
            return $this->$property;
        }
        return '';
    }
    
    /**
     * Sets the meta property value
     * Throws exception if the property does not exist
     * @param string $property - the name of the propery
     * @param string $value - the new value
     * @throws Exception
     */
    public function __set(string $property, string $value)
    {
        $property = strtolower($property);
        if (!array_key_exists($property, get_object_vars($this))) {
            throw new Exception("This variable ({$property}) does not exist!");
        }
        
        $this->$property = $value;
    }
    
    /**
     * Outputs the entire meta tags as HTML
     * Use this in the header
     */
    public function insertMeta()
    {
        global $Core;

        echo '<title>'.($this->title ? htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8') : $Core->siteName).'</title>';
        echo '<meta charset="UTF-8"/>';
        echo '<meta name="viewport" content="height=device-height, width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=0" />';
        echo '<meta name="apple-mobile-web-app-capable" content="yes" />';
        echo '<meta name="HandheldFriendly" content="true"/>';
        echo '<meta name="robots" content="INDEX,FOLLOW" />';

        echo '<meta name="keywords" content="'.($this->keywords ? htmlspecialchars(($this->keywords), ENT_QUOTES, 'UTF-8') : $Core->siteName).'"/>';

        if($this->description){
            echo '<meta name="description" content="'.(htmlspecialchars($this->description, ENT_QUOTES, 'UTF-8')).'"/>';
        }

        echo '<meta property="og:type" content="'.($this->og_type ? $this->og_type : 'website').'"/>';
        echo '<meta property="og:url" content="'.(htmlspecialchars(($this->og_url ? $this->og_url : $Core->siteDomain.($_SERVER['REQUEST_URI'] != '/' ? $_SERVER['REQUEST_URI'] : '')), ENT_QUOTES, 'UTF-8')).'" />';

        if($this->og_title || $this->title){
            echo '<meta property="og:title" content="'.(htmlspecialchars(($this->og_title ? $this->og_title : ($this->title ? $this->title : $Core->siteName)), ENT_QUOTES, 'UTF-8')).'" />';
        }

        if($this->og_description || $this->description){
            echo '<meta property="og:description" content="'.(htmlspecialchars(($this->og_description ? $this->og_description : $this->description), ENT_QUOTES, 'UTF-8')).'" />';
        }

        if(!empty($this->image) && @file_get_contents($Core->siteDomain.$this->image)){
            echo '<meta property="image" content="'.$Core->siteDomain.(htmlspecialchars($this->image, ENT_QUOTES, 'UTF-8')).'"/>';
            @list($width, $height) = getimagesize($Core->siteDomain.$this->image);
            if(isset($width) && !empty($width)){
                echo '<meta property="image:width" content="'.$width.'"/>';
            }
            if(isset($height) && !empty($height)){
                echo '<meta property="image:height" content="'.$height.'"/>';
            }
        }

        if(!empty($this->og_image) && @file_get_contents($Core->siteDomain.$this->og_image)){
            echo '<meta property="og:image" content="'.$Core->siteDomain.(htmlspecialchars($this->og_image, ENT_QUOTES, 'UTF-8')).'"/>';
            @list($width, $height) = getimagesize($Core->siteDomain.$this->og_image);
            if(isset($width) && !empty($width)){
                echo '<meta property="og:image:width" content="'.$width.'"/>';
            }
            if(isset($height) && !empty($height)){
                echo '<meta property="og:image:height" content="'.$height.'"/>';
            }
        }elseif(!empty($this->image) && @file_get_contents($Core->siteDomain.$this->image)){
            echo '<meta property="og:image" content="'.$Core->siteDomain.(htmlspecialchars($this->image, ENT_QUOTES, 'UTF-8')).'"/>';
            @list($width, $height) = getimagesize($Core->siteDomain.$this->image);
            if(isset($width) && !empty($width)){
                echo '<meta property="og:image:width" content="'.$width.'"/>';
            }
            if(isset($height) && !empty($height)){
                echo '<meta property="og:image:height" content="'.$height.'"/>';
            }
        }
    }
}
