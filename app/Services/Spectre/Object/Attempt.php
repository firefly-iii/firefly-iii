<?php
/**
 * Attempt.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Services\Spectre\Object;

use Carbon\Carbon;

/**
 * Class Attempt
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
    private $consentTypes = [];
    /** @var Carbon */
    private $createdAt;
    /** @var array */
    private $customFields = [];
    /** @var bool */
    private $dailyRefresh;
    /** @var string */
    private $deviceType;
    /** @var array */
    private $excludeAccounts = [];
    /** @var Carbon */
    private $failAt;
    /** @var string */
    private $failErrorClass;
    /** @var string */
    private $failMessage;
    /** @var string */
    private $fetchType;
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
    private $stages = [];
    /** @var bool */
    private $storeCredentials;
    /** @var Carbon */
    private $successAt;
    /** @var Carbon */
    private $toDate;
    /** @var Carbon */
    private $updatedAt;
    /** @var string */
    private $userAgent; // undocumented

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
        $this->fetchType              = $data['fetch_type'];
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



}