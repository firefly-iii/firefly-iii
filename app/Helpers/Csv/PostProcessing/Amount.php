<?php


namespace FireflyIII\Helpers\Csv\PostProcessing;

/**
 * Class Amount
 *
 * @package FireflyIII\Helpers\Csv\PostProcessing
 */
class Amount implements PostProcessorInterface
{

    /** @var  array */
    protected $data;

    /**
     * @return array
     */
    public function process()
    {
        bcscale(2);
        $this->data['amount'] = bcmul($this->data['amount'], $this->data['amount-modifier']);

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