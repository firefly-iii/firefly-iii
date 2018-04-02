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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Object;

use Carbon\Carbon;

/**
 * Class UserCompany.
 */
class UserCompany extends BunqObject
{
    /**
     * @var
     */
    private $addressMain;
    /**
     * @var
     */
    private $addressPostal;
    /** @var array */
    private $aliases = [];
    /**
     * @var
     */
    private $avatar;
    /** @var string */
    private $cocNumber;
    /** @var string */
    private $counterBankIban;
    /** @var Carbon */
    private $created;
    /**
     * @var
     */
    private $dailyLimit;
    /**
     * @var
     */
    private $directorAlias;
    /** @var string */
    private $displayName;
    /** @var int */
    private $id;
    /** @var string */
    private $language;
    /** @var string */
    private $name;
    /** @var array */
    private $notificationFilters = [];
    /** @var string */
    private $publicNickName;
    /** @var string */
    private $publicUuid;
    /** @var string */
    private $region;
    /** @var string */
    private $sectorOfIndustry;
    /** @var int */
    private $sessionTimeout;
    /** @var string */
    private $status;
    /** @var string */
    private $subStatus;
    /** @var string */
    private $typeOfBusinessEntity;
    /** @var array */
    private $ubos = [];
    /** @var Carbon */
    private $updated;
    /** @var int */
    private $versionTos;

    /**
     * UserCompany constructor.
     *
     * @param array $data
     *
     */
    public function __construct(array $data)
    {
        $this->id                   = (int)$data['id'];
        $this->created              = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['created']);
        $this->updated              = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['updated']);
        $this->status               = $data['status'];
        $this->subStatus            = $data['sub_status'];
        $this->publicUuid           = $data['public_uuid'];
        $this->displayName          = $data['display_name'];
        $this->publicNickName       = $data['public_nick_name'];
        $this->language             = $data['language'];
        $this->region               = $data['region'];
        $this->sessionTimeout       = (int)$data['session_timeout'];
        $this->versionTos           = (int)$data['version_terms_of_service'];
        $this->cocNumber            = $data['chamber_of_commerce_number'];
        $this->typeOfBusinessEntity = $data['type_of_business_entity'] ?? '';
        $this->sectorOfIndustry     = $data['sector_of_industry'] ?? '';
        $this->counterBankIban      = $data['counter_bank_iban'];
        $this->name                 = $data['name'];

        // TODO alias
        // TODO avatar
        // TODO daily_limit_without_confirmation_login
        // TODO notification_filters
        // TODO address_main
        // TODO address_postal
        // TODO director_alias
        // TODO ubo
        // TODO customer
        // TODO customer_limit
        // TODO billing_contract
        // TODO pack_membership
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'id'                         => $this->id,
            'created'                    => $this->created->format('Y-m-d H:i:s.u'),
            'updated'                    => $this->updated->format('Y-m-d H:i:s.u'),
            'status'                     => $this->status,
            'sub_status'                 => $this->subStatus,
            'public_uuid'                => $this->publicUuid,
            'display_name'               => $this->displayName,
            'public_nick_name'           => $this->publicNickName,
            'language'                   => $this->language,
            'region'                     => $this->region,
            'session_timeout'            => $this->sessionTimeout,
            'version_terms_of_service'   => $this->versionTos,
            'chamber_of_commerce_number' => $this->cocNumber,
            'type_of_business_entity'    => $this->typeOfBusinessEntity,
            'sector_of_industry'         => $this->sectorOfIndustry,
            'counter_bank_iban'          => $this->counterBankIban,
            'name'                       => $this->name,
        ];

        // TODO alias
        // TODO avatar
        // TODO daily_limit_without_confirmation_login
        // TODO notification_filters
        // TODO address_main
        // TODO address_postal
        // TODO director_alias
        // TODO ubo
        // TODO customer
        // TODO customer_limit
        // TODO billing_contract
        // TODO pack_membership

        return $data;
    }
}
