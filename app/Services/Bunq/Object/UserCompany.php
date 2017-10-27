<?php
/**
 * UserCompany.php
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
 * Class UserCompany
 *
 * @package FireflyIII\Services\Bunq\Object
 */
class UserCompany extends BunqObject
{
    private $addressMain;
    private $addressPostal;
    /** @var array */
    private $aliases = [];
    private $avatar;
    /** @var string */
    private $cocNumber = '';
    /** @var string */
    private $counterBankIban = '';
    /** @var Carbon */
    private $created;
    private $dailyLimit;
    private $directorAlias;
    /** @var string */
    private $displayName = '';
    /** @var int */
    private $id = 0;
    /** @var string */
    private $language = '';
    /** @var string */
    private $name = '';
    /** @var array */
    private $notificationFilters = [];
    /** @var string */
    private $publicNickName = '';
    /** @var string */
    private $publicUuid = '';
    /** @var string */
    private $region = '';
    /** @var string */
    private $sectorOfIndustry = '';
    /** @var int */
    private $sessionTimeout = 0;
    /** @var string */
    private $status = '';
    /** @var string */
    private $subStatus = '';
    /** @var  string */
    private $typeOfBusinessEntity = '';
    /** @var array */
    private $ubos = [];
    /** @var Carbon */
    private $updated;
    /** @var int */
    private $versionTos = 0;

    /**
     * UserCompany constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id                   = intval($data['id']);
        $this->created              = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['created']);
        $this->updated              = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['updated']);
        $this->status               = $data['status'];
        $this->subStatus            = $data['sub_status'];
        $this->publicUuid           = $data['public_uuid'];
        $this->displayName          = $data['display_name'];
        $this->publicNickName       = $data['public_nick_name'];
        $this->language             = $data['language'];
        $this->region               = $data['region'];
        $this->sessionTimeout       = intval($data['session_timeout']);
        $this->versionTos           = intval($data['version_terms_of_service']);
        $this->cocNumber            = $data['chamber_of_commerce_number'];
        $this->typeOfBusinessEntity = $data['type_of_business_entity'] ?? '';
        $this->sectorOfIndustry     = $data['sector_of_industry'] ?? '';
        $this->counterBankIban      = $data['counter_bank_iban'];
        $this->name                 = $data['name'];

    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }


}
