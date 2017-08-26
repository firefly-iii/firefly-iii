<?php
/**
 * MonetaryAccountProfile.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Object;

/**
 * Class MonetaryAccountProfile
 *
 * @package FireflyIII\Services\Bunq\Object
 */
class MonetaryAccountProfile extends BunqObject
{
    /** @var string */
    private $profileActionRequired = '';
    /** @var  Amount */
    private $profileAmountRequired;
    private $profileDrain;
    private $profileFill;

    /**
     * MonetaryAccountProfile constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->profileDrain          = null;
        $this->profileFill           = null;
        $this->profileActionRequired = $data['profile_action_required'];
        $this->profileAmountRequired = new Amount($data['profile_amount_required']);
        return;
    }

}