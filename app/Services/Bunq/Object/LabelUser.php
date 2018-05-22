<?php
/**
 * LabelUser.php
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

use FireflyIII\Exceptions\FireflyException;
/**
 * @codeCoverageIgnore
 * Class LabelUser
 */
class LabelUser extends BunqObject
{
    /** @var Avatar */
    private $avatar;
    /** @var string */
    private $country;
    /** @var string */
    private $displayName;
    /** @var string */
    private $publicNickName;
    /** @var string */
    private $uuid;

    /**
     * LabelUser constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->uuid           = $data['uuid'];
        $this->displayName    = $data['display_name'];
        $this->country        = $data['country'];
        $this->publicNickName = $data['public_nick_name'];
        $this->avatar         = isset($data['avatar']) ? new Avatar($data['avatar']) : null;
    }

    /**
     * @return Avatar
     */
    public function getAvatar(): Avatar
    {
        return $this->avatar;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @return string
     */
    public function getPublicNickName(): string
    {
        return $this->publicNickName;
    }

    /**
     * @return array
     * @throws FireflyException
     */
    public function toArray(): array
    {
        throw new FireflyException(sprintf('Cannot convert %s to array.', \get_class($this)));
    }
}
