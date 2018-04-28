<?php
/**
 * Payment.php
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

namespace FireflyIII\Services\Bunq\Object;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;

/**
 * Class Payment
 */
class Payment extends BunqObject
{
    /** @var LabelMonetaryAccount */
    private $alias;
    /** @var Amount */
    private $amount;
    /** @var array */
    private $attachments = [];
    /** @var LabelMonetaryAccount */
    private $counterParty;
    /** @var Carbon */
    private $created;
    /** @var string */
    private $description;
    /** @var int */
    private $id;
    /** @var string */
    private $merchantReference;
    /** @var int */
    private $monetaryAccountId;
    /** @var string */
    private $subType;
    /** @var string */
    private $type;
    /** @var Carbon */
    private $updated;

    /**
     * Payment constructor.
     *
     * @param array $data
     *
     */
    public function __construct(array $data)
    {
        $this->id                = $data['id'];
        $this->created           = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['created']);
        $this->updated           = Carbon::createFromFormat('Y-m-d H:i:s.u', $data['updated']);
        $this->monetaryAccountId = (int)$data['monetary_account_id'];
        $this->amount            = new Amount($data['amount']);
        $this->description       = $data['description'];
        $this->type              = $data['type'];
        $this->merchantReference = $data['merchant_reference'];
        $this->alias             = new LabelMonetaryAccount($data['alias']);
        $this->counterParty      = new LabelMonetaryAccount($data['counterparty_alias']);
        $this->subType           = $data['sub_type'];
    }

    /**
     * @return Amount
     */
    public function getAmount(): Amount
    {
        return $this->amount;
    }

    /**
     * @return LabelMonetaryAccount|null
     */
    public function getCounterParty(): ?LabelMonetaryAccount
    {
        return $this->counterParty;
    }

    /**
     * @return Carbon
     */
    public function getCreated(): Carbon
    {
        return $this->created;
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
     * @return string
     */
    public function getSubType(): string
    {
        return $this->subType;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     * @throws FireflyException
     */
    public function toArray(): array
    {
        throw new FireflyException(sprintf('Cannot convert %s to array.', \get_class($this)));
    }

}
