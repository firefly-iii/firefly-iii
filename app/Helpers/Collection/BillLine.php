<?php
/**
 * BillLine.php
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

namespace FireflyIII\Helpers\Collection;

use Carbon\Carbon;
use FireflyIII\Models\Bill as BillModel;

/**
 *
 * Class BillLine
 *
 * @package FireflyIII\Helpers\Collection
 */
class BillLine
{

    /** @var  string */
    protected $amount;
    /** @var  BillModel */
    protected $bill;
    /** @var  bool */
    protected $hit;
    /** @var  string */
    protected $max;
    /** @var  string */
    protected $min;
    /** @var  Carbon */
    private $lastHitDate;
    /** @var  int */
    private $transactionJournalId;

    /**
     * BillLine constructor.
     */
    public function __construct()
    {
        $this->lastHitDate = new Carbon;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount ?? '0';
    }

    /**
     * @param string $amount
     */
    public function setAmount(string $amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return BillModel
     */
    public function getBill(): BillModel
    {
        return $this->bill;
    }

    /**
     * @param BillModel $bill
     */
    public function setBill(BillModel $bill)
    {
        $this->bill = $bill;
    }

    /**
     * @return Carbon
     */
    public function getLastHitDate(): Carbon
    {
        return $this->lastHitDate;
    }

    /**
     * @param Carbon $lastHitDate
     */
    public function setLastHitDate(Carbon $lastHitDate)
    {
        $this->lastHitDate = $lastHitDate;
    }

    /**
     * @return string
     */
    public function getMax(): string
    {
        return $this->max;
    }

    /**
     * @param string $max
     */
    public function setMax(string $max)
    {
        $this->max = $max;
    }

    /**
     * @return string
     */
    public function getMin(): string
    {
        return $this->min;
    }

    /**
     * @param string $min
     */
    public function setMin(string $min)
    {
        $this->min = $min;
    }

    /**
     * @return int
     */
    public function getTransactionJournalId(): int
    {
        return $this->transactionJournalId ?? 0;
    }

    /**
     * @param int $transactionJournalId
     */
    public function setTransactionJournalId(int $transactionJournalId)
    {
        $this->transactionJournalId = $transactionJournalId;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return intval($this->bill->active) === 1;
    }

    /**
     * @return bool
     */
    public function isHit(): bool
    {
        return $this->hit;
    }

    /**
     * @param bool $hit
     */
    public function setHit(bool $hit)
    {
        $this->hit = $hit;
    }
}
