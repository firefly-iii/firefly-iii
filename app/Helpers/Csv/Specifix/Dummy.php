<?php

namespace FireflyIII\Helpers\Csv\Specifix;

/**
 * Class Dummy
 *
 * @package FireflyIII\Helpers\Csv\Specifix
 */
class Dummy
{
    /** @var array */
    protected $data;

    /** @var array */
    protected $row;


    /**
     * @return array
     */
    public function fix()
    {
        return $this->data;

    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @param array $row
     */
    public function setRow($row)
    {
        $this->row = $row;
    }


}