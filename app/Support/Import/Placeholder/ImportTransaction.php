<?php
/**
 * ImportTransaction.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Import\Placeholder;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Converter\Amount;
use FireflyIII\Import\Converter\AmountCredit;
use FireflyIII\Import\Converter\AmountDebit;
use FireflyIII\Import\Converter\AmountNegated;
use FireflyIII\Import\Converter\ConverterInterface;
use Log;

/**
 * Class ImportTransaction
 * @codeCoverageIgnore
 */
class ImportTransaction
{
    /** @var string */
    public $accountBic;
    /** @var string */
    public $accountIban;
    /** @var int */
    public $accountId;
    /** @var string */
    public $accountName;
    /** @var string */
    public $accountNumber;
    /** @var string */
    public $amount;
    /** @var string */
    public $amountCredit;
    /** @var string */
    public $amountDebit;
    /** @var string */
    public $amountNegated;
    /** @var int */
    public $billId;
    /** @var string */
    public $billName;
    /** @var int */
    public $budgetId;
    /** @var string */
    public $budgetName;
    /** @var int */
    public $categoryId;
    /** @var string */
    public $categoryName;
    /** @var string */
    public $currencyCode;
    /** @var int */
    public $currencyId;
    /** @var string */
    public $currencyName;
    /** @var string */
    public $currencySymbol;
    /** @var string */
    public $date;
    /** @var string */
    public $description;
    /** @var string */
    public $externalId;
    /** @var string */
    public $foreignAmount;
    /** @var string */
    public $foreignCurrencyCode;
    /** @var int */
    public $foreignCurrencyId;
    /** @var array */
    public $meta;
    /** @var array */
    public $modifiers;
    /** @var string */
    public $note;
    /** @var string */
    public $opposingBic;
    /** @var string */
    public $opposingIban;
    /** @var int */
    public $opposingId;
    /** @var string */
    public $opposingName;
    /** @var string */
    public $opposingNumber;
    /** @var array */
    public $tags;

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
     *
     */
    public function addColumnValue(ColumnValue $columnValue): void
    {
        $role   = $columnValue->getRole();
        $basics = [
            'account-iban'          => 'accountIban',
            'account-name'          => 'accountName',
            'account-bic'           => 'accountBic',
            'account-number'        => 'accountNumber',
            'amount_debit'          => 'amountDebit',
            'amount_credit'         => 'amountCredit',
            'amount_negated'        => 'amountNegated',
            'amount'                => 'amount',
            'amount_foreign'        => 'foreignAmount',
            'bill-name'             => 'billName',
            'budget-name'           => 'budgetName',
            'category-name'         => 'categoryName',
            'currency-name'         => 'currencyName',
            'currency-code'         => 'currencyCode',
            'currency-symbol'       => 'currencySymbol',
            'external-id'           => 'externalId',
            'foreign-currency-code' => 'foreignCurrencyCode',
            'date-transaction'      => 'date',
            'opposing-iban'         => 'opposingIban',
            'opposing-name'         => 'opposingName',
            'opposing-bic'          => 'opposingBic',
            'opposing-number'       => 'opposingNumber',
        ];

        $replaceOldRoles = [
            'original-source'    => 'original_source',
            'sepa-cc'            => 'sepa_cc',
            'sepa-ct-op'         => 'sepa_ct_op',
            'sepa-ct-id'         => 'sepa_ct_id',
            'sepa-db'            => 'sepa_db',
            'sepa-country'       => 'sepa_country',
            'sepa-ep'            => 'sepa_ep',
            'sepa-ci'            => 'sepa_ci',
            'sepa-batch-id'      => 'sepa_batch_id',
            'internal-reference' => 'internal_reference',
            'date-interest'      => 'date_interest',
            'date-invoice'       => 'date_invoice',
            'date-book'          => 'date_book',
            'date-payment'       => 'date_payment',
            'date-process'       => 'date_process',
            'date-due'           => 'date_due',
        ];
        if (array_key_exists($role, $replaceOldRoles)) {
            $role = $replaceOldRoles[$role];
        }

        if (isset($basics[$role])) {
            $field        = $basics[$role];
            $this->$field = $columnValue->getValue();

            return;
        }

        $mapped = [
            'account-id'          => 'accountId',
            'bill-id'             => 'billId',
            'budget-id'           => 'budgetId',
            'category-id'         => 'categoryId',
            'currency-id'         => 'currencyId',
            'foreign-currency-id' => 'foreignCurrencyId',
            'opposing-id'         => 'opposingId',
        ];
        if (isset($mapped[$role])) {
            $field        = $mapped[$role];
            $mappedValue  = $this->getMappedValue($columnValue);
            $this->$field = $mappedValue;
            Log::debug(sprintf('Going to set the %s. Original value is "%s", mapped value is "%s".', $role, $columnValue->getValue(), $mappedValue));

            return;
        }

        $meta = ['sepa_ct_id', 'sepa_ct_op', 'sepa_db', 'sepa_cc', 'sepa_country', 'sepa_batch_id', 'sepa_ep', 'sepa_ci', 'internal_reference', 'date_interest',
                 'date_invoice', 'date_book', 'date_payment', 'date_process', 'date_due', 'original_source'];
        Log::debug(sprintf('Now going to check role "%s".', $role));
        if (in_array($role, $meta, true)) {
            Log::debug(sprintf('Role "%s" is in allowed meta roles, so store its value "%s".', $role, $columnValue->getValue()));
            $this->meta[$role] = $columnValue->getValue();

            return;
        }

        $modifiers = ['generic-debit-credit', 'ing-debit-credit', 'rabo-debit-credit'];
        if (in_array($role, $modifiers, true)) {
            $this->modifiers[$role] = $columnValue->getValue();

            return;
        }

        switch ($role) {
            default:
                // @codeCoverageIgnoreStart
                throw new FireflyException(
                    sprintf('ImportTransaction cannot handle role "%s" with value "%s"', $role, $columnValue->getValue())
                );
            // @codeCoverageIgnoreEnd
            case 'description':
                $this->description = trim($this->description . ' ' . $columnValue->getValue());
                break;
            case 'note':
                $this->note = trim($this->note . ' ' . $columnValue->getValue());
                break;
            case 'tags-comma':
                $tags       = explode(',', $columnValue->getValue());
                $this->tags = array_unique(array_merge($this->tags, $tags));
                break;
            case 'tags-space':
                $tags       = explode(' ', $columnValue->getValue());
                $this->tags = array_unique(array_merge($this->tags, $tags));
                break;
            case '_ignore':
                break;

        }
    }

    /**
     * Calculate the amount of this transaction.
     *
     * @return string
     */
    public function calculateAmount(): string
    {
        Log::debug('Now in importTransaction->calculateAmount()');
        $info  = $this->selectAmountInput();
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
            $class = sprintf('FireflyIII\\Import\\Converter\\%s', config(sprintf('csv.import_roles.%s.converter', $role)));
            /** @var ConverterInterface $converter */
            $converter = app($class);
            Log::debug(sprintf('Now launching converter %s', $class));
            $conversion = $converter->convert($modifier);
            if ($conversion === -1) {
                $result = app('steam')->negative($result);
            }
            if (1 === $conversion) {
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
            $class = sprintf('FireflyIII\\Import\\Converter\\%s', config(sprintf('csv.import_roles.%s.converter', $role)));
            /** @var ConverterInterface $converter */
            $converter = app($class);
            Log::debug(sprintf('Now launching converter %s', $class));
            $conversion = $converter->convert($modifier);
            if ($conversion === -1) {
                $result = app('steam')->negative($result);
            }
            if (1 === $conversion) {
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
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
     * @return array
     */
    public function getForeignCurrencyData(): array
    {
        return [
            'code' => $this->foreignCurrencyCode,
        ];
    }

    /**
     * @codeCoverageIgnore
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
        if (null !== $this->amountNegated) {
            Log::debug('Amount NEGATED value is not NULL, assume this is the correct value (overrules Amount and AmountDebit and AmountCredit).');
            $converterClass = AmountNegated::class;
            $info['amount'] = $this->amountNegated;
        }
        $info['class'] = $converterClass;

        return $info;
    }

}
