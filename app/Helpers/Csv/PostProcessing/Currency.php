<?php
/**
 * Currency.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
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
    public function process(): array
    {

        // fix currency
        if (is_null($this->data['currency'])) {
            $currencyPreference     = Preferences::get('currencyPreference', env('DEFAULT_CURRENCY', 'EUR'));
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
