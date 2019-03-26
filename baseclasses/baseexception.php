<?php
    class BaseException extends Exception
    {
        /**
         * @var array
         * Additional data for the exception
         */
        private $data;
        
        /**
         * @var string
         * The class name which threw the exception
         */
        private $className;
        
        /**
         * Create a new instance of the BaseException class
         * @param string $message - the exception message
         * @param array $data - additional data for the exception
         * @param string $className - the class name which threw the exception
         */
        public function __construct(string $message, array $data = null, string $className = null) 
        {
            parent::__construct($message, 0, null);
    
            $this->data = $data;
            $this->className = $className;
        }
        
        /**
         * Returns additional data for the exception
         * @return array
         */
        public function getData() 
        { 
            return $this->data; 
        }
        
        /**
         * Returns the class name which threw the exception
         * @return string
         */
        public function getClassName()
        {
            return $this->className;
        }
    }
