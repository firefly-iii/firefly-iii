<?php
/**
 * LabelMonetaryAccount.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Object;


/**
 * Class LabelMonetaryAccount
 */
class LabelMonetaryAccount extends BunqObject
{
    /** @var Avatar */
    private $avatar;
    /** @var string */
    private $country;
    /** @var string */
    private $iban;
    /** @var bool */
    private $isLight;
    /** @var LabelUser */
    private $labelUser;

    /**
     * @return LabelUser
     */
    public function getLabelUser(): LabelUser
    {
        return $this->labelUser;
    }


    /**
     * LabelMonetaryAccount constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->iban      = $data['iban'];
        $this->isLight   = $data['is_light'];
        $this->avatar    = new Avatar($data['avatar']);
        $this->labelUser = new LabelUser($data['label_user']);
        $this->country   = $data['country'];
    }

    /**
     * @return string
     */
    public function getIban(): string
    {
        return $this->iban;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        die(sprintf('Cannot convert %s to array.', get_class($this)));
    }

}
