<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 03/07/14
 * Time: 21:34
 */

namespace Firefly\Helper\Migration;


class MigrationHelper implements MigrationHelperInterface
{
    protected $path;
    protected $JSON;

    public function loadFile($path)
    {
        $this->path = $path;
    }

    public function validFile()
    {
        // file does not exist:
        if(!file_exists($this->path)) {
            return false;
        }

        // load the content:
        $content = file_get_contents($this->path);
        if($content === false) {
            return false;
        }

        // parse the content
        $this->JSON = json_decode($content);
        if(is_null($this->JSON)) {
            return false;
        }
    }
}