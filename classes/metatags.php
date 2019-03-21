<?php
    class MetaTags
    {
        private $title;
        private $description;
        private $keywords;
        private $og_title;
        private $og_description;
        private $og_type;
        private $og_url;
        private $og_image;
        
        
        public function __get(string $var) {
            $var = strtolower($var);
            if (isset($this->$var)) {
                return $this->$var;
            }
            return '';
        }
        
        public function __set(string $var, $value)
        {
            $var = strtolower($var);
            if (!array_key_exists($var, get_object_vars($this))) {
                throw new Exception("This variable ({$var}) does not exist!");
            }
            
            $this->$var = $value;
        }
        
        /**
         * Outputs the entire meta tags
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
