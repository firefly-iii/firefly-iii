<?php
/**
 * Login.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Spectre\Object;

use Carbon\Carbon;


/**
 * Class Login
 *
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.ShortVariable)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Login extends SpectreObject
{
    /** @var Carbon */
    private $consentGivenAt;
    /** @var array */
    private $consentTypes;
    /** @var string */
    private $countryCode;
    /** @var Carbon */
    private $createdAt;
    /** @var int */
    private $customerId;
    /** @var bool */
    private $dailyRefresh;
    /** @var Holder */
    private $holderInfo;
    /** @var int */
    private $id;
    /** @var Attempt */
    private $lastAttempt;
    /** @var Carbon */
    private $lastSuccessAt;
    /** @var Carbon */
    private $nextRefreshPossibleAt;
    /** @var string */
    private $providerCode;
    /** @var int */
    private $providerId;
    /** @var string */
    private $providerName;
    /** @var bool */
    private $showConsentConfirmation;
    /** @var string */
    private $status;
    /** @var bool */
    private $storeCredentials;
    /** @var Carbon */
    private $updatedAt;

    /**
     * Login constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->consentGivenAt          = new Carbon($data['consent_given_at']);
        $this->consentTypes            = $data['consent_types'];
        $this->countryCode             = $data['country_code'];
        $this->createdAt               = new Carbon($data['created_at']);
        $this->updatedAt               = new Carbon($data['updated_at']);
        $this->customerId              = $data['customer_id'];
        $this->dailyRefresh            = $data['daily_refresh'];
        $this->holderInfo              = new Holder($data['holder_info']);
        $this->id                      = (int)$data['id'];
        $this->lastAttempt             = new Attempt($data['last_attempt']);
        $this->lastSuccessAt           = new Carbon($data['last_success_at']);
        $this->nextRefreshPossibleAt   = new Carbon($data['next_refresh_possible_at']);
        $this->providerCode            = $data['provider_code'];
        $this->providerId              = $data['provider_id'];
        $this->providerName            = $data['provider_name'];
        $this->showConsentConfirmation = $data['show_consent_confirmation'];
        $this->status                  = $data['status'];
        $this->storeCredentials        = $data['store_credentials'];

    }

    /**
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Attempt
     */
    public function getLastAttempt(): Attempt
    {
        return $this->lastAttempt;
    }

    /**
     * @return Carbon
     */
    public function getLastSuccessAt(): Carbon
    {
        return $this->lastSuccessAt;
    }

    /**
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->providerName;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return Carbon
     */
    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [
            'consent_given_at'          => $this->consentGivenAt->toIso8601String(),
            'consent_types'             => $this->consentTypes,
            'country_code'              => $this->countryCode,
            'created_at'                => $this->createdAt->toIso8601String(),
            'updated_at'                => $this->updatedAt->toIso8601String(),
            'customer_id'               => $this->customerId,
            'daily_refresh'             => $this->dailyRefresh,
            'holder_info'               => $this->holderInfo->toArray(),
            'id'                        => $this->id,
            'last_attempt'              => $this->lastAttempt->toArray(),
            'last_success_at'           => $this->lastSuccessAt->toIso8601String(),
            'next_refresh_possible_at'  => $this->nextRefreshPossibleAt->toIso8601String(),
            'provider_code'             => $this->providerCode,
            'provider_id'               => $this->providerId,
            'provider_name'             => $this->providerName,
            'show_consent_confirmation' => $this->showConsentConfirmation,
            'status'                    => $this->status,
            'store_credentials'         => $this->storeCredentials,

        ];

        return $array;
    }


}
