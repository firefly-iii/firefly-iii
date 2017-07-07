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


use FireflyIII\Import\Converter\ConverterInterface;
use Steam;

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
     * @return string
     */
    public function getAmount(): string
    {
        // use converter:
        $this->amount = strval($this->parseAmount());


        // also apply modifiers:
        $this->amount = Steam::positive($this->amount);

        // Can handle ING
        foreach ($this->modifiers as $modifier) {
            $class = sprintf('FireflyIII\Import\Converter\%s', config(sprintf('csv.import_roles.%s.converter', $modifier['role'])));
            /** @var ConverterInterface $converter */
            $converter = app($class);
            if ($converter->convert($modifier['value']) === -1) {
                $this->amount = Steam::negative($this->amount);
            }
        }

        return $this->amount;
    }

    /**
     * @param string $amount
     */
    public function setAmount(string $amount)
    {
        $this->amount = $amount;
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

    /**
     * Some people, when confronted with a problem, think "I know, I'll use regular expressions." Now they have two problems.
     * - Jamie Zawinski
     *
     * @return float
     */
    private function parseAmount()
    {
        $value           = $this->amount;
        $len             = strlen($value);
        $decimalPosition = $len - 3;
        $decimal         = null;

        if (($len > 2 && $value{$decimalPosition} == '.') || ($len > 2 && strpos($value, '.') > $decimalPosition)) {
            $decimal = '.';
        }
        if ($len > 2 && $value{$decimalPosition} == ',') {
            $decimal = ',';
        }

        // if decimal is dot, replace all comma's and spaces with nothing. then parse as float (round to 4 pos)
        if ($decimal === '.') {
            $search = [',', ' '];
            $value  = str_replace($search, '', $value);
        }
        if ($decimal === ',') {
            $search = ['.', ' '];
            $value  = str_replace($search, '', $value);
            $value  = str_replace(',', '.', $value);
        }
        if (is_null($decimal)) {
            // replace all:
            $search = ['.', ' ', ','];
            $value  = str_replace($search, '', $value);
        }

        return round(floatval($value), 12);
    }

}
