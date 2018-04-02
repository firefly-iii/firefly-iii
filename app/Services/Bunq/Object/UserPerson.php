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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Services\Bunq\Object;

use Carbon\Carbon;

/**
 * Class UserPerson.
 */
class UserPerson extends BunqObject
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
    /** @var array */
    private $billingContracts = [];
    /** @var string */
    private $countryOfBirth;
    /** @var Carbon */
    private $created;
    /**
     * @var
     */
    private $customer;
    /**
     * @var
     */
    private $customerLimit;
    /**
     * @var
     */
    private $dailyLimit;
    /** @var Carbon */
    private $dateOfBirth;
    /** @var string */
    private $displayName;
    /** @var string */
    private $documentCountry;
    /** @var string */
    private $documentNumber;
    /** @var string */
    private $documentType;
    /** @var string */
    private $firstName;
    /** @var string */
    private $gender;
    /** @var int */
    private $id;
    /** @var string */
    private $language;
    /** @var string */
    private $lastName;
    /** @var string */
    private $legalName;
    /** @var string */
    private $middleName;
    /** @var string */
    private $nationality;
    /** @var array */
    private $notificationFilters = [];
    /** @var string */
    private $placeOfBirth;
    /** @var string */
    private $publicNickName;
    /** @var string */
    private $publicUuid;
    /**
     * @var mixed
     */
    private $region;
    /** @var int */
    private $sessionTimeout;
    /** @var string */
    private $status;
    /** @var string */
    private $subStatus;
    /** @var string */
    private $taxResident;
    /** @var Carbon */
    private $updated;
    /** @var int */
    private $versionTos;

    /**
     * UserPerson constructor.
     *
     * @param array $data
     *
     */
    public function __construct(array $data)
    {
        if (0 === count($data)) {
            $this->created     = new Carbon;
            $this->updated     = new Carbon;
            $this->dateOfBirth = new Carbon;

            return;
        }

        $this->id              = (int)$data['id'];
        $this->created         = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['created']);
        $this->updated         = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['updated']);
        $this->status          = $data['status'];
        $this->subStatus       = $data['sub_status'];
        $this->publicUuid      = $data['public_uuid'];
        $this->displayName     = $data['display_name'];
        $this->publicNickName  = $data['public_nick_name'];
        $this->language        = $data['language'];
        $this->region          = $data['region'];
        $this->sessionTimeout  = (int)$data['session_timeout'];
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
        $this->versionTos      = (int)$data['version_terms_of_service'];
        $this->documentNumber  = $data['document_number'];
        $this->documentType    = $data['document_type'];
        $this->documentCountry = $data['document_country_of_issuance'];

        // TODO create aliases
        // TODO create avatar
        // TODO create daily limit
        // TODO create notification filters
        // TODO create address main, postal
        // TODO  document front, back attachment
        // TODO customer, customer_limit
        // TODO billing contracts
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
            'id'                           => $this->id,
            'created'                      => $this->created->format('Y-m-d H:i:s.u'),
            'updated'                      => $this->updated->format('Y-m-d H:i:s.u'),
            'status'                       => $this->status,
            'sub_status'                   => $this->subStatus,
            'public_uuid'                  => $this->publicUuid,
            'display_name'                 => $this->displayName,
            'public_nick_name'             => $this->publicNickName,
            'language'                     => $this->language,
            'region'                       => $this->region,
            'session_timeout'              => $this->sessionTimeout,
            'first_name'                   => $this->firstName,
            'middle_name'                  => $this->middleName,
            'last_name'                    => $this->lastName,
            'legal_name'                   => $this->legalName,
            'tax_resident'                 => $this->taxResident,
            'date_of_birth'                => $this->dateOfBirth->format('Y-m-d'),
            'place_of_birth'               => $this->placeOfBirth,
            'country_of_birth'             => $this->countryOfBirth,
            'nationality'                  => $this->nationality,
            'gender'                       => $this->gender,
            'version_terms_of_service'     => $this->versionTos,
            'document_number'              => $this->documentNumber,
            'document_type'                => $this->documentType,
            'document_country_of_issuance' => $this->documentCountry,
        ];

        return $data;
    }
}
