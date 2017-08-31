<?php
/**
 * UserLight.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Object;

use Carbon\Carbon;

/**
 * Class UserLight
 *
 * @package FireflyIII\Services\Bunq\Object
 */
class UserLight extends BunqObject
{
    /** @var array */
    private $aliases = [];
    /** @var Carbon */
    private $created;
    /** @var string */
    private $displayName = '';
    /** @var string */
    private $firstName = '';
    /** @var int */
    private $id = 0;
    /** @var string */
    private $lastName = '';
    /** @var string */
    private $legalName = '';
    /** @var string */
    private $middleName = '';
    /** @var string */
    private $publicNickName = '';
    /** @var string */
    private $publicUuid = '';
    /** @var Carbon */
    private $updated;

    /**
     * UserLight constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (count($data) === 0) {
            return;
        }
        $this->id             = intval($data['id']);
        $this->created        = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['created']);
        $this->updated        = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['updated']);
        $this->publicUuid     = $data['public_uuid'];
        $this->displayName    = $data['display_name'];
        $this->publicNickName = $data['public_nick_name'];
        $this->firstName      = $data['first_name'];
        $this->middleName     = $data['middle_name'];
        $this->lastName       = $data['last_name'];
        $this->legalName      = $data['legal_name'];
        // aliases
    }

}
