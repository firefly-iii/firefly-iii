<?php
/**
 * MonetaryAccountSetting.php
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
 * Class MonetaryAccountSetting
 *
 * @package FireflyIII\Services\Bunq\Object
 */
class MonetaryAccountSetting extends BunqObject
{
    /** @var string */
    private $color = '';
    /** @var string */
    private $defaultAvatarStatus = '';
    /** @var string */
    private $restrictionChat = '';

    /**
     * MonetaryAccountSetting constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->color               = $data['color'];
        $this->defaultAvatarStatus = $data['default_avatar_status'];
        $this->restrictionChat     = $data['restriction_chat'];

        return;
    }

    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @return string
     */
    public function getDefaultAvatarStatus(): string
    {
        return $this->defaultAvatarStatus;
    }

    /**
     * @return string
     */
    public function getRestrictionChat(): string
    {
        return $this->restrictionChat;
    }


}
