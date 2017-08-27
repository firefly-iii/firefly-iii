<?php
/**
 * Currency.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Object;

/**
 * Class Amount
 *
 * @package FireflyIII\Services\Bunq\Object
 */
class Amount extends BunqObject
{
    /** @var string */
    private $currency = '';
    /** @var string */
    private $value = '';

    /**
     * Amount constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->currency = $data['currency'];
        $this->value    = $data['value'];

        return;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }


}