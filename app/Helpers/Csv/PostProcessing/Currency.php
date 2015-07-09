<?php

namespace FireflyIII\Helpers\Csv\PostProcessing;

use FireflyIII\Models\TransactionCurrency;
use Preferences;

/**
 * Class Currency
 *
 * @package FireflyIII\Helpers\Csv\PostProcessing
 */
class Currency implements PostProcessorInterface
{

    /** @var  array */
    protected $data;

    /**
     * @return array
     */
    public function process()
    {

        // fix currency
        if (is_null($this->data['currency'])) {
            $currencyPreference     = Preferences::get('currencyPreference', 'EUR');
            $this->data['currency'] = TransactionCurrency::whereCode($currencyPreference->data)->first();
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
