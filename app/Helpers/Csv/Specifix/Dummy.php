<?php
/**
 * Dummy.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv\Specifix;

/**
 * Class Dummy
 *
 * @package FireflyIII\Helpers\Csv\Specifix
 */
class Dummy extends Specifix implements SpecifixInterface
{
    /** @var array */
    protected $data;

    /** @var array */
    protected $row;

    /**
     * Dummy constructor.
     */
    public function __construct()
    {
        $this->setProcessorType(self::POST_PROCESSOR);
    }

    /**
     * @return array
     */
    public function fix(): array
    {
        return $this->data;

    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param array $row
     */
    public function setRow(array $row)
    {
        $this->row = $row;
    }


}
