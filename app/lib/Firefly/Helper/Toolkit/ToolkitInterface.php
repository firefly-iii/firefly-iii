<?php

namespace Firefly\Helper\Toolkit;

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
     * @param null $titleField
     *
     * @return mixed
     */
    public function makeSelectList(Collection $set, $titleField = null);

    public function next();

    public function prev();

    public function checkImportJobs();

    /**
     * @param string $start
     * @param string $end
     * @param int $steps
     */
    public function colorRange($start, $end, $steps = 5);

}