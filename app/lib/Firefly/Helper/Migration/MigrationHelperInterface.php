<?php

namespace Firefly\Helper\Migration;

/**
 * Interface MigrationHelperInterface
 *
 * @package Firefly\Helper\Migration
 */
interface MigrationHelperInterface
{
    /**
     * @param $path
     *
     * @return mixed
     */
    public function loadFile($path);

    /**
     * @return mixed
     */
    public function validFile();

    /**
     * @return mixed
     */
    public function migrate();

} 