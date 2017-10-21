<?php
/**
 * MonetaryAccountBank.php
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
 * Class MonetaryAccountBank
 *
 * @package FireflyIII\Services\Bunq\Object
 */
class MonetaryAccountBank extends BunqObject
{
    /** @var array */
    private $aliases = [];
    /** @var  Avatar */
    private $avatar;
    /** @var  Amount */
    private $balance;
    /** @var Carbon */
    private $created;
    /** @var string */
    private $currency = '';
    /** @var  Amount */
    private $dailyLimit;
    /** @var  Amount */
    private $dailySpent;
    /** @var string */
    private $description = '';
    /** @var int */
    private $id = 0;
    /** @var  MonetaryAccountProfile */
    private $monetaryAccountProfile;
    /** @var array */
    private $notificationFilters = [];
    /** @var  Amount */
    private $overdraftLimit;
    /** @var string */
    private $publicUuid = '';
    /** @var string */
    private $reason = '';
    /** @var string */
    private $reasonDescription = '';
    /** @var  MonetaryAccountSetting */
    private $setting;
    /** @var string */
    private $status = '';
    /** @var string */
    private $subStatus = '';
    /** @var Carbon */
    private $updated;
    /** @var int */
    private $userId = 0;

    /**
     * MonetaryAccountBank constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id                     = $data['id'];
        $this->created                = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['created']);
        $this->updated                = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['updated']);
        $this->balance                = new Amount($data['balance']);
        $this->currency               = $data['currency'];
        $this->dailyLimit             = new Amount($data['daily_limit']);
        $this->dailySpent             = new Amount($data['daily_spent']);
        $this->description            = $data['description'];
        $this->publicUuid             = $data['public_uuid'];
        $this->status                 = $data['status'];
        $this->subStatus              = $data['sub_status'];
        $this->userId                 = $data['user_id'];
        $this->status                 = $data['status'];
        $this->subStatus              = $data['sub_status'];
        $this->monetaryAccountProfile = new MonetaryAccountProfile($data['monetary_account_profile']);
        $this->setting                = new MonetaryAccountSetting($data['setting']);
        $this->overdraftLimit         = new Amount($data['overdraft_limit']);

        $this->publicUuid = $data['public_uuid'];

        // create aliases:
        foreach ($data['alias'] as $alias) {
            $this->aliases[] = new Alias($alias);
        }
        /** @var array $filter */
        foreach ($data['notification_filters'] as $filter) {
            $this->notificationFilters[] = new NotificationFilter($filter);
        }

        return;
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @return Amount
     */
    public function getBalance(): Amount
    {
        return $this->balance;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return MonetaryAccountSetting
     */
    public function getSetting(): MonetaryAccountSetting
    {
        return $this->setting;
    }

}
