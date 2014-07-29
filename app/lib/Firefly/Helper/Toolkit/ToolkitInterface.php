<?php

namespace Firefly\Helper\Toolkit;

use Illuminate\Http\Request;

/**
 * Interface ToolkitInterface
 *
 * @package Firefly\Helper\Toolkit
 */
interface ToolkitInterface
{
    /**
     * @return mixed
     */
    public function getDateRange(Request $request);

    /**
     * @return mixed
     */
    public function getDateRangeDates();

} 