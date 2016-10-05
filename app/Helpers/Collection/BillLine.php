<?php
/**
 * BillLine.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\Bill as BillModel;

/**
 *
 * Class BillLine
 *
 * @package FireflyIII\Helpers\Collection
 */
class BillLine
{

    /** @var  bool */
    protected $active;
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

    /** @var  int */
    private $transactionJournalId;

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
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active)
    {
        $this->active = $active;
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
