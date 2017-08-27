<?php
/**
 * MonetaryAccountSetting.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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