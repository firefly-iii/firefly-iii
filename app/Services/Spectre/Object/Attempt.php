<?php
/**
 * Attempt.php
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
 *
 * Class Attempt
 *
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.ShortVariable)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Attempt extends SpectreObject
{
    /** @var string */
    private $apiMode;
    /** @var int */
    private $apiVersion;
    /** @var bool */
    private $automaticFetch;
    /** @var bool */
    private $categorize;
    /** @var Carbon */
    private $consentGivenAt;
    /** @var array */
    private $consentTypes;
    /** @var Carbon */
    private $createdAt;
    /** @var array */
    private $customFields;
    /** @var bool */
    private $dailyRefresh;
    /** @var string */
    private $deviceType;
    /** @var array */
    private $excludeAccounts;
    /** @var Carbon */
    private $failAt;
    /** @var string */
    private $failErrorClass;
    /** @var string */
    private $failMessage;
    /** @var array */
    private $fetchScopes;
    /** @var bool */
    private $finished;
    /** @var bool */
    private $finishedRecent;
    /** @var Carbon */
    private $fromDate;
    /** @var int */
    private $id;
    /** @var bool */
    private $interactive;
    /** @var string */
    private $locale;
    /** @var bool */
    private $partial;
    /** @var string */
    private $remoteIp;
    /** @var bool */
    private $showConsentInformation;
    /** @var array */
    private $stages;
    /** @var bool */
    private $storeCredentials;
    /** @var Carbon */
    private $successAt;
    /** @var Carbon */
    private $toDate;
    /** @var Carbon */
    private $updatedAt; // undocumented
    /** @var string */
    private $userAgent;

    /**
     * Attempt constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->apiMode                = $data['api_mode'];
        $this->apiVersion             = $data['api_version'];
        $this->automaticFetch         = $data['automatic_fetch'];
        $this->categorize             = $data['categorize'];
        $this->createdAt              = new Carbon($data['created_at']);
        $this->consentGivenAt         = new Carbon($data['consent_given_at']);
        $this->consentTypes           = $data['consent_types'];
        $this->customFields           = $data['custom_fields'];
        $this->dailyRefresh           = $data['daily_refresh'];
        $this->deviceType             = $data['device_type'];
        $this->userAgent              = $data['user_agent'] ?? '';
        $this->remoteIp               = $data['remote_ip'];
        $this->excludeAccounts        = $data['exclude_accounts'];
        $this->failAt                 = new Carbon($data['fail_at']);
        $this->failErrorClass         = $data['fail_error_class'];
        $this->failMessage            = $data['fail_message'];
        $this->fetchScopes            = $data['fetch_scopes'];
        $this->finished               = $data['finished'];
        $this->finishedRecent         = $data['finished_recent'];
        $this->fromDate               = new Carbon($data['from_date']);
        $this->id                     = $data['id'];
        $this->interactive            = $data['interactive'];
        $this->locale                 = $data['locale'];
        $this->partial                = $data['partial'];
        $this->showConsentInformation = $data['show_consent_confirmation'];
        $this->stages                 = $data['stages'] ?? [];
        $this->storeCredentials       = $data['store_credentials'];
        $this->successAt              = new Carbon($data['success_at']);
        $this->toDate                 = new Carbon($data['to_date']);
        $this->updatedAt              = new Carbon($data['updated_at']);

    }

    /**
     * @return Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    /**
     * @return Carbon
     */
    public function getFailAt(): Carbon
    {
        return $this->failAt;
    }

    /**
     * @return null|string
     */
    public function getFailErrorClass(): ?string
    {
        return $this->failErrorClass;
    }

    /**
     * @return null|string
     */
    public function getFailMessage(): ?string
    {
        return $this->failMessage;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [
            'api_mode'                  => $this->apiMode,
            'api_version'               => $this->apiVersion,
            'automatic_fetch'           => $this->automaticFetch,
            'categorize'                => $this->categorize,
            'created_at'                => $this->createdAt->toIso8601String(),
            'consent_given_at'          => $this->consentGivenAt->toIso8601String(),
            'consent_types'             => $this->consentTypes,
            'custom_fields'             => $this->customFields,
            'daily_refresh'             => $this->dailyRefresh,
            'device_type'               => $this->deviceType,
            'user_agent'                => $this->userAgent,
            'remote_ip'                 => $this->remoteIp,
            'exclude_accounts'          => $this->excludeAccounts,
            'fail_at'                   => $this->failAt->toIso8601String(),
            'fail_error_class'          => $this->failErrorClass,
            'fail_message'              => $this->failMessage,
            'fetch_scopes'              => $this->fetchScopes,
            'finished'                  => $this->finished,
            'finished_recent'           => $this->finishedRecent,
            'from_date'                 => $this->fromDate->toIso8601String(),
            'id'                        => $this->id,
            'interactive'               => $this->interactive,
            'locale'                    => $this->locale,
            'partial'                   => $this->partial,
            'show_consent_confirmation' => $this->showConsentInformation,
            'stages'                    => $this->stages,
            'store_credentials'         => $this->storeCredentials,
            'success_at'                => $this->successAt->toIso8601String(),
            'to_date'                   => $this->toDate->toIso8601String(),
            'updated_at'                => $this->updatedAt->toIso8601String(),
        ];

        return $array;
    }


}
