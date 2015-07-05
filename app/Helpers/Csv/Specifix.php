<?php

namespace FireflyIII\Helpers\Csv;

/**
 * Class Specifix
 *
 * @package FireflyIII\Helpers\Csv
 */
class Specifix
{
    /** @var array */
    protected $data;

    /** @var array */
    protected $row;


    /**
     * Implement bank and locale related fixes.
     */
    public function fix()
    {
        $this->rabobankFixEmptyOpposing();

    }

    /**
     * Fixes Rabobank specific thing.
     */
    protected function rabobankFixEmptyOpposing()
    {
        if (strlen($this->data['opposing-account']) == 0) {
            $this->data['opposing-account'] = $this->row[10];
        }
        $this->data['description'] = trim(str_replace($this->row[10], '', $this->data['description']));
    }


    /**
     * @return array
     */
    public function getData()
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
     * @return array
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * @param array $row
     */
    public function setRow($row)
    {
        $this->row = $row;
    }


}