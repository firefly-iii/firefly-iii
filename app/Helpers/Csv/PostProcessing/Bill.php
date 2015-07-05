<?php

namespace FireflyIII\Helpers\Csv\PostProcessing;

/**
 * Class Bill
 *
 * @package FireflyIII\Helpers\Csv\PostProcessing
 */
class Bill implements PostProcessorInterface
{

    /** @var  array */
    protected $data;

    /**
     * @return array
     */
    public function process()
    {

        // get bill id.
        if (!is_null($this->data['bill'])) {
            $this->data['bill-id'] = $this->data['bill']->id;
        }

        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }
}