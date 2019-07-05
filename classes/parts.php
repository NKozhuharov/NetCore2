<?php
class Parts
{
    const PARTS_PATH = 'parts/';

    /**
     * Requires a file from the parts folder.
     * Supports PHP and HTML files
     * Throws Error if the file does not exist
     * @param string $partName - the name of the file
     * @param array $data - optional data to use in the file
     * @throws Error
     */
    public function includePart(string $partName, array $data = null)
    {
        global $Core;

        if (is_file($Core->siteDir.self::PARTS_PATH.$partName.'.html')) {
            require($Core->siteDir.self::PARTS_PATH.$partName.'.html');
        } else if (is_file($Core->siteDir.self::PARTS_PATH.$partName.'.php')) {
            require($Core->siteDir.self::PARTS_PATH.$partName.'.php');
        } else {
            throw new Error("The `{$partName}` part does not exist");
        }
    }

    /**
     * Aliast of includePart
     */
    public function addPart(string $partName, array $data = null)
    {
        $this->includePart($partName, $data);
    }

    /**
     * Aliast of includePart
     */
    public function getPart(string $partName, array $data = null)
    {
        $this->includePart($partName, $data);
    }
}
