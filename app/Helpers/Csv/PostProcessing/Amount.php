<?php
declare(strict_types = 1);

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
        $amount               = $this->data['amount'] ?? '0';
        $modifier             = strval($this->data['amount-modifier']);
        $this->data['amount'] = bcmul($amount, $modifier);

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
