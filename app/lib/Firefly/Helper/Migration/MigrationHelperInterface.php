<?php

namespace Firefly\Helper\Migration;


interface MigrationHelperInterface
{
    public function loadFile($path);

    public function validFile();

    public function migrate();

} 