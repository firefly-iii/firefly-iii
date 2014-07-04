<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 03/07/14
 * Time: 21:33
 */

namespace Firefly\Helper\Migration;


interface MigrationHelperInterface
{
    public function loadFile($path);

    public function validFile();

} 