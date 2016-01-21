<?php
/**
 * Specifix.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Helpers\Csv\Specifix;

/**
 * Class Specifix
 *
 * @package FireflyIII\Helpers\Csv\Specifix
 */
class Specifix
{

    /** @var  int */
    protected $processorType;

    /**
     * @return int
     */
    public function getProcessorType()
    {
        return $this->processorType;
    }

    /**
     * @param $processorType
     *
     * @return $this
     */
    public function setProcessorType($processorType)
    {
        $this->processorType = $processorType;

        return $this;
    }


}