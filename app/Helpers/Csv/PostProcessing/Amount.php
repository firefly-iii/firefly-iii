<?php
/**
 * Amount.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

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
    public function process(): array
    {
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
