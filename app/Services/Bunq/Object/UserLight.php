<?php
/**
 * UserLight.php
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
