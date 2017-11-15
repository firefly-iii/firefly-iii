<?php
/**
 * Amount.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
