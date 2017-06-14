<?php
/**
 * ImportTransaction.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Object;


class ImportTransaction
{
    /** @var  string */
    private $amount;

    /** @var  ImportCurrency */
    private $currency;

    /** @var  string */
    private $date;

    /** @var  string */
    private $description;
    private $modifiers = [];
    /** @var bool */
    private $positive = true;

    public function __construct()
    {
        $this->currency = new ImportCurrency;
    }

    public function addToModifier(array $modifier)
    {
        $this->modifiers[] = $modifier;
    }

    /**
     * @return ImportCurrency
     */
    public function getCurrency(): ImportCurrency
    {
        return $this->currency;
    }

    /**
     * @param ImportCurrency $currency
     */
    public function setCurrency(ImportCurrency $currency)
    {
        $this->currency = $currency;
    }

    /**
     * @param string $amount
     */
    public function setAmount(string $amount)
    {
        $this->amount = $amount;
    }

    /**
     * @param string $date
     */
    public function setDate(string $date)
    {
        $this->date = $date;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @param bool $positive
     */
    public function setPositive(bool $positive)
    {
        $this->positive = $positive;
    }

}