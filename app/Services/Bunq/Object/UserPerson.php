<?php
/**
 * UserPerson.php
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
 * Class UserPerson
 *
 * @package Bunq\Object
 */
class UserPerson extends BunqObject
{
    private $addressMain;
    private $addressPostal;
    /** @var array */
    private $aliases = [];
    private $avatar;
    /** @var array */
    private $billingContracts = [];
    /** @var string */
    private $countryOfBirth = '';
    /** @var Carbon */
    private $created;
    private $customer;
    private $customerLimit;
    private $dailyLimit;
    /** @var Carbon */
    private $dateOfBirth;
    /** @var string */
    private $displayName = '';
    /** @var string */
    private $documentCountry = '';
    /** @var string */
    private $documentNumber = '';
    /** @var string */
    private $documentType = '';
    /** @var string */
    private $firstName = '';
    /** @var string */
    private $gender = '';
    /** @var int */
    private $id = 0;
    /** @var string */
    private $language = '';
    /** @var string */
    private $lastName = '';
    /** @var string */
    private $legalName = '';
    /** @var string */
    private $middleName = '';
    /** @var string */
    private $nationality = '';
    /** @var array */
    private $notificationFilters = [];
    /** @var string */
    private $placeOfBirth = '';
    /** @var string */
    private $publicNickName = '';
    /** @var string */
    private $publicUuid = '';
    private $region;
    /** @var int */
    private $sessionTimeout = 0;
    /** @var string */
    private $status = '';
    /** @var string */
    private $subStatus = '';
    /** @var string */
    private $taxResident = '';
    /** @var Carbon */
    private $updated;
    /** @var int */
    private $versionTos = 0;

    /**
     * UserPerson constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (count($data) === 0) {
            return;
        }
        $this->id              = intval($data['id']);
        $this->created         = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['created']);
        $this->updated         = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['updated']);
        $this->status          = $data['status'];
        $this->subStatus       = $data['sub_status'];
        $this->publicUuid      = $data['public_uuid'];
        $this->displayName     = $data['display_name'];
        $this->publicNickName  = $data['public_nick_name'];
        $this->language        = $data['language'];
        $this->region          = $data['region'];
        $this->sessionTimeout  = intval($data['session_timeout']);
        $this->firstName       = $data['first_name'];
        $this->middleName      = $data['middle_name'];
        $this->lastName        = $data['last_name'];
        $this->legalName       = $data['legal_name'];
        $this->taxResident     = $data['tax_resident'];
        $this->dateOfBirth     = Carbon::createFromFormat('Y-m-d', $data['date_of_birth']);
        $this->placeOfBirth    = $data['place_of_birth'];
        $this->countryOfBirth  = $data['country_of_birth'];
        $this->nationality     = $data['nationality'];
        $this->gender          = $data['gender'];
        $this->versionTos      = intval($data['version_terms_of_service']);
        $this->documentNumber  = $data['document_number'];
        $this->documentType    = $data['document_type'];
        $this->documentCountry = $data['document_country_of_issuance'];

        // create aliases
        // create avatar
        // create daily limit
        // create notification filters
        // create address main, postal
        // document front, back attachment
        // customer, customer_limit
        // billing contracts
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }


}
