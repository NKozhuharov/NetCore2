<?php
    class Parts
    {
        const PARTS_PATH = '/parts';
        
        public function includePart(string $partName)
        {
            global $Core;
            
            if (is_file($Core->projectDir.self::PARTS_PATH.'/'.$partName.'.html')) {
                require($Core->projectDir.self::PARTS_PATH.'/'.$partName.'.html');  
            } else if (is_file($Core->projectDir.self::PARTS_PATH.'/'.$partName.'.php')) {
                require($Core->projectDir.self::PARTS_PATH.'/'.$partName.'.php');  
            } else {
                throw new Exception("The `{$partName}` part does not exist");
            }
        }
        
        public function addPart(string $partName)
        {
            $this->includePart($partName);
        }     
        
        
        public function getPart(string $partName)
        {
            $this->includePart($partName);
        }
    }
