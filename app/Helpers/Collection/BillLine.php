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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Helpers\Collection;

use Carbon\Carbon;
use FireflyIII\Models\Bill as BillModel;
use FireflyIII\Models\TransactionCurrency;

/**
 * Class BillLine.
 *
 * @codeCoverageIgnore
 */
class BillLine
{
    /** @var string The amount */
    protected $amount;
    /** @var BillModel The bill. */
    protected $bill;
    /** @var bool Is it hit this period */
    protected $hit;
    /** @var string What was the max amount. */
    protected $max;
    /** @var string What was the min amount. */
    protected $min;
    /** @var TransactionCurrency The transaction currency */
    private $currency;
    /** @var Carbon Latest date that payment is expected. */
    private $endOfPayDate;
    /** @var Carbon Date of last hit */
    private $lastHitDate;
    /** @var Carbon Date of last payment */
    private $payDate;
    /** @var int Journal */
    private $transactionJournalId;

    /**
     * BillLine constructor.
     */
    public function __construct()
    {
        $this->lastHitDate = new Carbon;
    }

    /**
     * Amount getter.
     *
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount ?? '0';
    }

    /**
     * Amount setter.
     *
     * @param string $amount
     */
    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * Bill getter.
     *
     * @return BillModel
     */
    public function getBill(): BillModel
    {
        return $this->bill;
    }

    /**
     * Bill setter.
     *
     * @param BillModel $bill
     */
    public function setBill(BillModel $bill): void
    {
        $this->bill = $bill;
    }

    /**
     * @return TransactionCurrency
     */
    public function getCurrency(): TransactionCurrency
    {
        return $this->currency;
    }

    /**
     * @param TransactionCurrency $currency
     */
    public function setCurrency(TransactionCurrency $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * End of pay date getter.
     *
     * @return Carbon
     */
    public function getEndOfPayDate(): Carbon
    {
        return $this->endOfPayDate;
    }

    /**
     * End of pay date setter.
     *
     * @param Carbon $endOfPayDate
     */
    public function setEndOfPayDate(Carbon $endOfPayDate): void
    {
        $this->endOfPayDate = $endOfPayDate;
    }

    /**
     * Last hit date getter.
     *
     * @return Carbon
     */
    public function getLastHitDate(): Carbon
    {
        return $this->lastHitDate;
    }

    /**
     * Last hit date setter.
     *
     * @param Carbon $lastHitDate
     */
    public function setLastHitDate(Carbon $lastHitDate): void
    {
        $this->lastHitDate = $lastHitDate;
    }

    /**
     * Max getter.
     *
     * @return string
     */
    public function getMax(): string
    {
        return $this->max;
    }

    /**
     * Max setter.
     *
     * @param string $max
     */
    public function setMax(string $max): void
    {
        $this->max = $max;
    }

    /**
     * Min getter.
     *
     * @return string
     */
    public function getMin(): string
    {
        return $this->min;
    }

    /**
     * Min setter.
     *
     * @param string $min
     */
    public function setMin(string $min): void
    {
        $this->min = $min;
    }

    /**
     * Pay date getter.
     *
     * @return Carbon
     */
    public function getPayDate(): Carbon
    {
        return $this->payDate;
    }

    /**
     * Pay date setter.
     *
     * @param Carbon $payDate
     */
    public function setPayDate(Carbon $payDate): void
    {
        $this->payDate = $payDate;
    }

    /**
     * Journal ID getter.
     *
     * @return int
     */
    public function getTransactionJournalId(): int
    {
        return $this->transactionJournalId ?? 0;
    }

    /**
     * Journal ID setter.
     *
     * @param int $transactionJournalId
     */
    public function setTransactionJournalId(int $transactionJournalId): void
    {
        $this->transactionJournalId = $transactionJournalId;
    }

    /**
     * Is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return 1 === (int)$this->bill->active;
    }

    /**
     * Is hit.
     *
     * @return bool
     */
    public function isHit(): bool
    {
        return $this->hit;
    }

    /**
     * Set is hit.
     *
     * @param bool $hit
     */
    public function setHit(bool $hit): void
    {
        $this->hit = $hit;
    }
}
