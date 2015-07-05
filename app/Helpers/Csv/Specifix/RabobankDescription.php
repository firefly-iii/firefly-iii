<?php

namespace FireflyIII\Helpers\Csv\Specifix;

/**
 * Class RabobankDescription
 *
 * @package FireflyIII\Helpers\Csv\Specifix
 */
class RabobankDescription
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
        $this->rabobankFixEmptyOpposing();

        return $this->data;

    }

    /**
     * Fixes Rabobank specific thing.
     */
    protected function rabobankFixEmptyOpposing()
    {
        if (strlen($this->data['opposing-account-name']) == 0) {
            $this->data['opposing-account-name'] = $this->row[10];
        }
        $this->data['description'] = trim(str_replace($this->row[10], '', $this->data['description']));
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