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
use FireflyIII\Import\Converter\Amount;
use FireflyIII\Import\Converter\AmountCredit;
use FireflyIII\Import\Converter\AmountDebit;
use FireflyIII\Import\Converter\ConverterInterface;
use Log;

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
    private $accountNumber;
    /** @var string */
    private $amount;
    /** @var string */
    private $amountCredit;
    /** @var string */
    private $amountDebit;
    /** @var int */
    private $billId;
    /** @var string */
    private $billName;
    /** @var int */
    private $budgetId;
    /** @var string */
    private $budgetName;
    /** @var int */
    private $categoryId;
    /** @var string */
    private $categoryName;
    /** @var string */
    private $currencyCode;
    /** @var int */
    private $currencyId;
    /** @var string */
    private $currencyName;
    /** @var string */
    private $currencySymbol;
    /** @var string */
    private $date;
    /** @var string */
    private $description;
    /** @var string */
    private $externalId;
    /** @var string */
    private $foreignAmount;
    /** @var string */
    private $foreignCurrencyCode;
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
    /** @var string */
    private $opposingNumber;
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

        // mappable items, set to 0:
        $this->accountId         = 0;
        $this->budgetId          = 0;
        $this->billId            = 0;
        $this->currencyId        = 0;
        $this->categoryId        = 0;
        $this->foreignCurrencyId = 0;
        $this->opposingId        = 0;

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
            case 'account-iban':
                $this->accountIban = $columnValue->getValue();
                break;
            case 'account-name':
                $this->accountName = $columnValue->getValue();
                break;
            case 'account-bic':
                $this->accountBic = $columnValue->getValue();
                break;
            case 'account-number':
                $this->accountNumber = $columnValue->getValue();
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
            case 'bill-id':
                $this->billId = $this->getMappedValue($columnValue);
                break;
            case 'bill-name':
                $this->billName = $columnValue->getValue();
                break;
            case 'budget-id':
                $this->budgetId = $this->getMappedValue($columnValue);
                break;
            case 'budget-name':
                $this->budgetName = $columnValue->getValue();
                break;
            case 'category-id':
                $this->categoryId = $this->getMappedValue($columnValue);
                break;
            case 'category-name':
                $this->categoryName = $columnValue->getValue();
                break;
            case 'currency-id':
                $this->currencyId = $this->getMappedValue($columnValue);
                break;
            case 'currency-name':
                $this->currencyName = $columnValue->getValue();
                break;
            case 'currency-code':
                $this->currencyCode = $columnValue->getValue();
                break;
            case 'currency-symbol':
                $this->currencySymbol = $columnValue->getValue();
                break;
            case 'external-id':
                $this->externalId = $columnValue->getValue();
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

            case 'foreign-currency-id':
                $this->foreignCurrencyId = $this->getMappedValue($columnValue);
                break;
            case 'foreign-currency-code':
                $this->foreignCurrencyCode = $columnValue->getValue();
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

            case 'opposing-id':
                $this->opposingId = $this->getMappedValue($columnValue);
                break;
            case 'opposing-iban':
                $this->opposingIban = $columnValue->getValue();
                break;
            case 'opposing-name':
                $this->opposingName = $columnValue->getValue();
                break;
            case 'opposing-bic':
                $this->opposingBic = $columnValue->getValue();
                break;
            case 'opposing-number':
                $this->opposingNumber = $columnValue->getValue();
                break;

            case 'rabo-debit-credit':
            case 'ing-debit-credit':
                $this->modifiers[$columnValue->getRole()] = $columnValue->getValue();
                break;

            case 'tags-comma':
                // todo split using pre-processor.
                $this->tags = $columnValue->getValue();
                break;
            case 'tags-space':
                // todo split using pre-processor.
                $this->tags = $columnValue->getValue();
                break;
            case '_ignore':
                break;

        }
    }

    /**
     * Calculate the amount of this transaction.
     *
     * @return string
     * @throws FireflyException
     */
    public function calculateAmount(): string
    {
        Log::debug('Now in importTransaction->calculateAmount()');
        $info = $this->selectAmountInput();

        if (0 === \count($info)) {
            Log::error('No amount information for this row.');

            return '';
        }
        $class = $info['class'] ?? '';
        if ('' === $class) {
            Log::error('No amount information (conversion class) for this row.');

            return '';
        }

        Log::debug(sprintf('Converter class is %s', $info['class']));
        /** @var ConverterInterface $amountConverter */
        $amountConverter = app($info['class']);
        $result          = $amountConverter->convert($info['amount']);
        Log::debug(sprintf('First attempt to convert gives "%s"', $result));
        // modify
        /**
         * @var string $role
         * @var string $modifier
         */
        foreach ($this->modifiers as $role => $modifier) {
            $class = sprintf('FireflyIII\Import\Converter\%s', config(sprintf('csv.import_roles.%s.converter', $role)));
            /** @var ConverterInterface $converter */
            $converter = app($class);
            Log::debug(sprintf('Now launching converter %s', $class));
            $conversion = $converter->convert($modifier);
            if ($conversion === -1) {
                $result = app('steam')->negative($result);
            }
            if ($conversion === 1) {
                $result = app('steam')->positive($result);
            }
            Log::debug(sprintf('convertedAmount after conversion is  %s', $result));
        }

        Log::debug(sprintf('After modifiers the result is: "%s"', $result));


        return $result;
    }

    /**
     * The method that calculates the foreign amount isn't nearly as complex,\
     * because Firefly III only supports one foreign amount field. So the foreign amount is there
     * or isn't. That's about it. However, if it's there, modifiers will be applied too.
     *
     * @return string
     */
    public function calculateForeignAmount(): string
    {
        if (null === $this->foreignAmount) {
            Log::debug('ImportTransaction holds no foreign amount info.');
            return '';
        }
        /** @var ConverterInterface $amountConverter */
        $amountConverter = app(Amount::class);
        $result          = $amountConverter->convert($this->foreignAmount);
        Log::debug(sprintf('First attempt to convert foreign amount gives "%s"', $result));
        /**
         * @var string $role
         * @var string $modifier
         */
        foreach ($this->modifiers as $role => $modifier) {
            $class = sprintf('FireflyIII\Import\Converter\%s', config(sprintf('csv.import_roles.%s.converter', $role)));
            /** @var ConverterInterface $converter */
            $converter = app($class);
            Log::debug(sprintf('Now launching converter %s', $class));
            $conversion = $converter->convert($modifier);
            if ($conversion === -1) {
                $result = app('steam')->negative($result);
            }
            if ($conversion === 1) {
                $result = app('steam')->positive($result);
            }
            Log::debug(sprintf('Foreign amount after conversion is  %s', $result));
        }

        Log::debug(sprintf('After modifiers the foreign amount is: "%s"', $result));

        return $result;
    }

    /**
     * This array is being used to map the account the user is using.
     *
     * @return array
     */
    public function getAccountData(): array
    {
        return [
            'iban'   => $this->accountIban,
            'name'   => $this->accountName,
            'number' => $this->accountNumber,
            'bic'    => $this->accountBic,
        ];
    }

    /**
     * @return int
     */
    public function getAccountId(): int
    {
        return $this->accountId;
    }

    /**
     * @return int
     */
    public function getBillId(): int
    {
        return $this->billId;
    }

    /**
     * @return null|string
     */
    public function getBillName(): ?string
    {
        return $this->billName;
    }

    /**
     * @return int
     */
    public function getBudgetId(): int
    {
        return $this->budgetId;
    }

    /**
     * @return string|null
     */
    public function getBudgetName(): ?string
    {
        return $this->budgetName;
    }

    /**
     * @return int
     */
    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    /**
     * @return string|null
     */
    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    /**
     * @return array
     */
    public function getCurrencyData(): array
    {
        return [
            'name'   => $this->currencyName,
            'code'   => $this->currencyCode,
            'symbol' => $this->currencySymbol,
        ];
    }

    /**
     * @return int
     */
    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
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
    public function getForeignCurrencyId(): int
    {
        return $this->foreignCurrencyId;
    }

    /**
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @return string
     */
    public function getNote(): string
    {
        return $this->note;
    }

    /**
     * @return array
     */
    public function getOpposingAccountData(): array
    {
        return [
            'iban'   => $this->opposingIban,
            'name'   => $this->opposingName,
            'number' => $this->opposingNumber,
            'bic'    => $this->opposingBic,
        ];
    }

    /**
     * @return int
     */
    public function getOpposingId(): int
    {
        return $this->opposingId;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return [];

        // todo make sure this is an array
        return $this->tags;
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

    /**
     * This methods decides which input value to use for the amount calculation.
     *
     * @return array
     */
    private function selectAmountInput(): array
    {
        $info           = [];
        $converterClass = '';
        if (null !== $this->amount) {
            Log::debug('Amount value is not NULL, assume this is the correct value.');
            $converterClass = Amount::class;
            $info['amount'] = $this->amount;
        }
        if (null !== $this->amountDebit) {
            Log::debug('Amount DEBIT value is not NULL, assume this is the correct value (overrules Amount).');
            $converterClass = AmountDebit::class;
            $info['amount'] = $this->amountDebit;
        }
        if (null !== $this->amountCredit) {
            Log::debug('Amount CREDIT value is not NULL, assume this is the correct value (overrules Amount and AmountDebit).');
            $converterClass = AmountCredit::class;
            $info['amount'] = $this->amountCredit;
        }
        $info['class'] = $converterClass;

        return $info;
    }

}