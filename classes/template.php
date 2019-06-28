<?php
class Template
{
    const TEMPLATES_PATH = 'templates/';

    /**
     * @var object
     * An instance of the class the template represents
     */
    private $instance;

    /**
     * @var string
     * The name of the template file
     */
    private $templateName;

    /**
     * @var string
     * The name of the template class
     */
    private $className;

    /**
     * Creates a new instance of the Tempalte class
     * It will require a file from the templates directory and initialize it
     * You can use the methods of the file as templates to output HTML
     * It will throw Exception if an invalid template name is provided or the file does not exist
     * @param string $templateName - the name of the template; the file that contains it must be lowercase the same string
     * @param array $data - optional data for the template instance
     * @throws Exception
     */
    public function __construct(string $templateName, array $data = null)
    {
        global $Core;

        if (empty($templateName)) {
            throw new Exception("Template name cannot be empty");
        }

        $this->templateName = mb_strtolower($templateName);
        if (is_file($Core->siteDir.self::TEMPLATES_PATH.$this->templateName.".php")) {
            require_once($Core->siteDir.self::TEMPLATES_PATH.$this->templateName.".php");

            $this->className = ucfirst("{$this->templateName}Templates");

            if (!class_exists($this->className)) {
                throw new Exception("Wrong class name in {$this->templateName}.php! It must be '{$this->className}'!");
            }

            $this->instance = new $this->className($data);
        } else {
            throw new Exception("Template file {$this->templateName}.php does not exist! Please create it in the 'templates' folder");
        }
    }

    /**
     * Executes a method of the template class. It will output all HTML from it.
     * It will throw Exception if an invalid method name is provided
     * @param string $methodName - the name of the method
     * @param array $data - optional data for the method
     * @throws Exception
     */
    public function drawHtml(string $methodName, array $data = null)
    {
        if (empty($methodName)) {
            throw new Exception("Method name must not be empty!");
        }

        if (!in_array($methodName, get_class_methods($this->className))) {
            throw new Exception("Method '$methodName' does not exist! It must be a public method of the {$this->className} class!");
        }

        $this->instance->$methodName($data);
    }

    /**
     * Executes a method of the template class. It will return all HTML as string
     * @param string $methodName - the name of the method
     * @param array $data - optional data for the method
     * @return string
     */
    public function getHtml(string $methodName, array $data = null)
    {
        ob_start();
            $this->drawHtml($methodName, $data);
            $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
}
