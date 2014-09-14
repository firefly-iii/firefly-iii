<?php

namespace Firefly\Helper\Toolkit;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Interface ToolkitInterface
 *
 * @package Firefly\Helper\Toolkit
 */
interface ToolkitInterface
{
    /**
     *
     * @return null
     */
    public function getDateRange();

    /**
     * Takes any collection and tries to make a sensible select list compatible array of it.
     *
     * @param Collection $set
     * @param null       $titleField
     *
     * @return mixed
     */
    public function makeSelectList(Collection $set, $titleField = null);

    public function bootstrapDaterange();

    public function next();
    public function prev();

public function checkImportJobs();

}