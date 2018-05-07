<?php
/**
 * Transaction.php
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

namespace FireflyIII\Support\Import\Placeholder;

use FireflyIII\Exceptions\FireflyException;

/**
 * Class ImportTransaction
 */
class ImportTransaction
{
    /** @var string */
    private $accountBic;
    /** @var string */
    private $accountIban;
    /** @var int */
    private $accountId;
    /** @var string */
    private $accountName;
    /** @var string */
    private $amount;
    /** @var string */
    private $amountCredit;
    /** @var string */
    private $amountDebit;
    /** @var int */
    private $billId;
    /** @var int */
    private $budgetId;
    /** @var int */
    private $categoryId;
    /** @var int */
    private $currencyId;
    /** @var string */
    private $date;
    /** @var string */
    private $description;
    /** @var string */
    private $externalId;
    /** @var string */
    private $foreignAmount;
    /** @var int */
    private $foreignCurrencyId;
    /** @var array */
    private $meta;
    /** @var array */
    private $modifiers;
    /** @var string */
    private $note;
    /** @var string */
    private $opposingBic;
    /** @var string */
    private $opposingIban;
    /** @var int */
    private $opposingId;
    /** @var string */
    private $opposingName;
    /** @var array */
    private $tags;

    /**
     * ImportTransaction constructor.
     */
    public function __construct()
    {
        $this->tags        = [];
        $this->modifiers   = [];
        $this->meta        = [];
        $this->description = '';
        $this->note        = '';
    }

    /**
     * @param ColumnValue $columnValue
     *
     * @throws FireflyException
     */
    public function addColumnValue(ColumnValue $columnValue): void
    {
        switch ($columnValue->getRole()) {
            default:
                throw new FireflyException(
                    sprintf('ImportTransaction cannot handle role "%s" with value "%s"', $columnValue->getRole(), $columnValue->getValue())
                );
            case 'account-id':
                // could be the result of a mapping?
                $this->accountId = $this->getMappedValue($columnValue);
                break;
            case 'account-name':
                $this->accountName = $columnValue->getValue();
                break;
            case 'sepa-ct-id';
            case 'sepa-ct-op';
            case 'sepa-db';
            case 'sepa-cc':
            case 'sepa-country';
            case 'sepa-ep';
            case 'sepa-ci';
            case 'internal-reference':
            case 'date-interest':
            case 'date-invoice':
            case 'date-book':
            case 'date-payment':
            case 'date-process':
            case 'date-due':
                $this->meta[$columnValue->getRole()] = $columnValue->getValue();
                break;
            case'amount_debit':
                $this->amountDebit = $columnValue->getValue();
                break;
            case'amount_credit':
                $this->amountCredit = $columnValue->getValue();
                break;
            case 'amount':
                $this->amount = $columnValue->getValue();
                break;
            case 'amount_foreign':
                $this->foreignAmount = $columnValue->getValue();
                break;
            case 'foreign-currency-id':
                $this->foreignCurrencyId = $this->getMappedValue($columnValue);
                break;
            case 'bill-id':
                $this->billId = $this->getMappedValue($columnValue);
                break;
            case 'budget-id':
                $this->budgetId = $this->getMappedValue($columnValue);
                break;
            case 'category-id':
                $this->categoryId = $this->getMappedValue($columnValue);
                break;
            case 'currency-id':
                $this->currencyId = $this->getMappedValue($columnValue);
                break;
            case 'date-transaction':
                $this->date = $columnValue->getValue();
                break;
            case 'description':
                $this->description .= $columnValue->getValue();
                break;
            case 'note':
                $this->note .= $columnValue->getValue();
                break;
            case 'external-id':
                $this->externalId = $columnValue->getValue();
                break;
            case 'rabo-debit-credit':
            case 'ing-debit-credit':
                $this->modifiers[$columnValue->getRole()] = $columnValue->getValue();
                break;
            case 'opposing-id':
                $this->opposingId = $this->getMappedValue($columnValue);
                break;
            case 'opposing-name':
                $this->opposingName = $columnValue->getValue();
                break;
            case 'opposing-bic':
                $this->opposingBic = $columnValue->getValue();
                break;
            case 'tags-comma':
                // todo split using pre-processor.
                $this->tags = $columnValue->getValue();
                break;
            case 'tags-space':
                // todo split using pre-processor.
                $this->tags = $columnValue->getValue();
                break;
            case 'account-iban':
                $this->accountIban = $columnValue->getValue();
                break;
            case 'opposing-iban':
                $this->opposingIban = $columnValue->getValue();
                break;
            case '_ignore':
            case 'bill-name':
            case 'currency-name':
            case 'currency-code':
            case 'foreign-currency-code':
            case 'currency-symbol':
            case 'budget-name':
            case 'category-name':
            case 'account-number':
            case 'opposing-number':
        }
    }

    /**
     * Returns the mapped value if it exists in the ColumnValue object.
     *
     * @param ColumnValue $columnValue
     *
     * @return int
     */
    private function getMappedValue(ColumnValue $columnValue): int
    {
        return $columnValue->getMappedValue() > 0 ? $columnValue->getMappedValue() : (int)$columnValue->getValue();
    }

}