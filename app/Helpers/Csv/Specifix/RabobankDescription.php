<?php

namespace FireflyIII\Helpers\Csv\Specifix;

use Log;

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
        Log::debug('RaboSpecifix: Opposing account name is "' . $this->data['opposing-account-name'] . '".');
        if (is_string($this->data['opposing-account-name']) && strlen($this->data['opposing-account-name']) == 0) {
            Log::debug('RaboSpecifix: opp-name is zero length, changed to: "' . $this->row[10] . '"');
            $this->data['opposing-account-name'] = $this->row[10];

            Log::debug('Description was: "' . $this->data['description'] . '".');
            $this->data['description'] = trim(str_replace($this->row[10], '', $this->data['description']));
            Log::debug('Description is now: "' . $this->data['description'] . '".');
        }

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