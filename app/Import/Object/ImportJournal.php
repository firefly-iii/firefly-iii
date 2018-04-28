<?php
/**
 * ImportJournal.php
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

namespace FireflyIII\Import\Object;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Converter\Amount;
use FireflyIII\Import\Converter\ConverterInterface;
use FireflyIII\Import\MapperPreProcess\PreProcessorInterface;
use FireflyIII\User;
use InvalidArgumentException;
use Log;
use Steam;

/**
 * Class ImportJournal.
 */
class ImportJournal
{

    /** @var ImportAccount */
    public $asset;
    /** @var ImportBill */
    public $bill;
    /** @var ImportBudget */
    public $budget;
    /** @var ImportCategory */
    public $category;
    /** @var ImportCurrency */
    public $currency;
    /** @var string */
    public $description = '';
    /** @var ImportCurrency */
    public $foreignCurrency;
    /** @var string */
    public $hash;
    /** @var array */
    public $metaDates = [];
    /** @var array */
    public $metaFields = [];
    /** @var string */
    public $notes = '';
    /** @var ImportAccount */
    public $opposing;
    /** @var array */
    public $tags = [];
    /** @var array */
    private $amount;
    /** @var array */
    private $amountCredit;
    /** @var array */
    private $amountDebit;
    /** @var string */
    private $convertedAmount;
    /** @var string */
    private $date = '';
    /** @var string */
    private $externalId = '';
    /** @var array */
    private $foreignAmount;
    /** @var array */
    private $modifiers = [];
    /** @var User */
    private $user;

    /**
     * ImportEntry constructor.
     */
    public function __construct()
    {
        $this->asset           = new ImportAccount;
        $this->opposing        = new ImportAccount;
        $this->bill            = new ImportBill;
        $this->category        = new ImportCategory;
        $this->budget          = new ImportBudget;
        $this->currency        = new ImportCurrency;
        $this->foreignCurrency = new ImportCurrency;
    }

    /**
     * @param array $modifier
     */
    public function addToModifier(array $modifier)
    {
        $this->modifiers[] = $modifier;
    }

    /**
     * @return string
     *
     * @throws FireflyException
     */
    public function getAmount(): string
    {
        Log::debug('Now in getAmount()');
        Log::debug(sprintf('amount is %s', var_export($this->amount, true)));
        Log::debug(sprintf('debit amount is %s', var_export($this->amountDebit, true)));
        Log::debug(sprintf('credit amount is %s', var_export($this->amountCredit, true)));

        if (null === $this->convertedAmount) {
            $this->calculateAmount();
        }
        Log::debug(sprintf('convertedAmount is: "%s"', $this->convertedAmount));
        if (0 === bccomp($this->convertedAmount, '0')) {
            throw new FireflyException('Amount is zero.');
        }

        return $this->convertedAmount;
    }

    /**
     * @param string $format
     *
     * @return Carbon
     */
    public function getDate(string $format): Carbon
    {
        $date = new Carbon;
        try {
            $date = Carbon::createFromFormat($format, $this->date);
        } catch (InvalidArgumentException $e) {
            // don't care, just log.
            Log::error(sprintf('Import journal cannot parse date "%s" from value "%s" so will return current date instead.', $format, $this->date));
        }

        return $date;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        if ('' === $this->description) {
            return '(no description)';
        }

        return $this->description;
    }

    /**
     * @return string|null
     */
    public function getForeignAmount(): ?string
    {
        Log::debug('Now in getForeignAmount()');
        Log::debug(sprintf('foreign amount is %s', var_export($this->foreignAmount, true)));

        // no foreign amount? return null
        if (null === $this->foreignAmount) {
            Log::debug('Return NULL for foreign amount');

            return null;
        }
        // converter is default amount converter: no special stuff
        $converter = app(Amount::class);
        $amount    = $converter->convert($this->foreignAmount['value']);
        Log::debug(sprintf('First attempt to convert foreign gives "%s"', $amount));
        // modify
        foreach ($this->modifiers as $modifier) {
            $class = sprintf('FireflyIII\Import\Converter\%s', config(sprintf('csv.import_roles.%s.converter', $modifier['role'])));
            /** @var ConverterInterface $converter */
            $converter = app($class);
            Log::debug(sprintf('Now launching converter %s', $class));
            if ($converter->convert($modifier['value']) === -1) {
                $amount = Steam::negative($amount);
            }
            Log::debug(sprintf('Foreign amount after conversion is  %s', $amount));
        }

        Log::debug(sprintf('After modifiers the result is: "%s"', $amount));


        Log::debug(sprintf('converted foreign amount is: "%s"', $amount));
        if (0 === bccomp($amount, '0')) {
            return null;
        }

        return $amount;
    }

    /**
     * Get date field or NULL
     *
     * @param string $field
     *
     * @return Carbon|null
     */
    public function getMetaDate(string $field): ?Carbon
    {
        if (isset($this->metaDates[$field])) {
            return new Carbon($this->metaDates[$field]);
        }

        return null;
    }

    /**
     * Get string field or NULL
     *
     * @param string $field
     *
     * @return string|null
     */
    public function getMetaString(string $field): ?string
    {
        if (isset($this->metaFields[$field]) && \strlen($this->metaFields[$field]) > 0) {
            return (string)$this->metaFields[$field];
        }

        return null;
    }


    /**
     * @param string $hash
     */
    public function setHash(string $hash)
    {
        $this->hash = $hash;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        // set user for related objects:
        $this->asset->setUser($user);
        $this->opposing->setUser($user);
        $this->budget->setUser($user);
        $this->category->setUser($user);
        $this->bill->setUser($user);
    }

    /**
     * @param array $array
     *
     * @throws FireflyException
     */
    public function setValue(array $array)
    {
        $array['mapped'] = $array['mapped'] ?? null;
        $array['value']  = $array['value'] ?? null;
        switch ($array['role']) {
            default:
                throw new FireflyException(sprintf('ImportJournal cannot handle "%s" with value "%s".', $array['role'], $array['value']));
            case 'account-id':
                $this->asset->setAccountId($array);
                break;
            case 'sepa-cc':
            case 'sepa-ct-op':
            case 'sepa-ct-id':
            case 'sepa-db':
            case 'sepa-country':
            case 'sepa-ep':
            case 'sepa-ci':
                $value = trim((string)$array['value']);
                if (\strlen($value) > 0) {
                    $this->metaFields[$array['role']] = $value;
                }
                break;
            case 'amount':
                $this->amount = $array;
                break;
            case 'amount_foreign':
                $this->foreignAmount = $array;
                break;
            case 'foreign-currency-code':
                $this->foreignCurrency->setCode($array);
                break;
            case 'amount_debit':
                $this->amountDebit = $array;
                break;
            case 'amount_credit':
                $this->amountCredit = $array;
                break;
            case 'account-iban':
                $this->asset->setAccountIban($array);
                break;
            case 'account-name':
                $this->asset->setAccountName($array);
                break;
            case 'account-number':
                $this->asset->setAccountNumber($array);
                break;
            case 'bill-id':
                $this->bill->setId($array);
                break;
            case 'bill-name':
                $this->bill->setName($array);
                break;
            case 'budget-id':
                $this->budget->setId($array);
                break;
            case 'budget-name':
                $this->budget->setName($array);
                break;
            case 'category-id':
                $this->category->setId($array);
                break;
            case 'category-name':
                $this->category->setName($array);
                break;
            case 'currency-code':
                $this->currency->setCode($array);
                break;
            case 'currency-id':
                $this->currency->setId($array);
                break;
            case 'currency-name':
                $this->currency->setName($array);
                break;
            case 'currency-symbol':
                $this->currency->setSymbol($array);
                break;
            case 'date-transaction':
                $this->date = $array['value'];
                break;
            case 'description':
                $this->description .= $array['value'];
                break;
            case 'note':
                $this->notes .= ' ' . $array['value'];
                $this->notes = trim($this->notes);
                break;
            case 'external-id':
                $this->externalId = $array['value'];
                break;
            case 'internal-reference':
                $this->metaFields['internal_reference'] = $array['value'];
                break;
            case '_ignore':
                break;
            case 'ing-debit-credit':
            case 'rabo-debit-credit':
                $this->addToModifier($array);
                break;
            case 'opposing-iban':
                $this->opposing->setAccountIban($array);
                break;
            case 'opposing-name':
                $this->opposing->setAccountName($array);
                break;
            case 'opposing-number':
                $this->opposing->setAccountNumber($array);
                break;
            case 'opposing-id':
                $this->opposing->setAccountId($array);
                break;
            case 'opposing-bic':
                $this->opposing->setAccountBic($array);
                break;
            case 'tags-comma':
            case 'tags-space':
                $this->setTags($array);
                break;
            case 'date-interest':
                $this->metaDates['interest_date'] = $array['value'];
                break;
            case 'date-book':
                $this->metaDates['book_date'] = $array['value'];
                break;
            case 'date-process':
                $this->metaDates['process_date'] = $array['value'];
                break;
            case 'date-due':
                $this->metaDates['due_date'] = $array['value'];
                break;
            case 'date-payment':
                $this->metaDates['payment_date'] = $array['value'];
                break;
            case 'date-invoice':
                $this->metaDates['invoice_date'] = $array['value'];
                break;
        }
    }

    /**
     * If convertedAmount is NULL, this method will try to calculate the correct amount.
     * It starts with amount, but can be overruled by debit and credit amounts.
     *
     * @throws FireflyException
     */
    private function calculateAmount()
    {
        // first check if the amount is set:
        Log::debug('convertedAmount is NULL');

        $info = $this->selectAmountInput();

        if (0 === \count($info)) {
            throw new FireflyException('No amount information for this row.');
        }
        $class = $info['class'] ?? '';
        if (0 === \strlen($class)) {
            throw new FireflyException('No amount information (conversion class) for this row.');
        }

        Log::debug(sprintf('Converter class is %s', $info['class']));
        /** @var ConverterInterface $amountConverter */
        $amountConverter       = app($info['class']);
        $this->convertedAmount = $amountConverter->convert($info['value']);
        Log::debug(sprintf('First attempt to convert gives "%s"', $this->convertedAmount));
        // modify
        foreach ($this->modifiers as $modifier) {
            $class = sprintf('FireflyIII\Import\Converter\%s', config(sprintf('csv.import_roles.%s.converter', $modifier['role'])));
            /** @var ConverterInterface $converter */
            $converter = app($class);
            Log::debug(sprintf('Now launching converter %s', $class));
            if ($converter->convert($modifier['value']) === -1) {
                $this->convertedAmount = Steam::negative($this->convertedAmount);
            }
            Log::debug(sprintf('convertedAmount after conversion is  %s', $this->convertedAmount));
        }

        Log::debug(sprintf('After modifiers the result is: "%s"', $this->convertedAmount));
    }

    /**
     * This methods decides which input to use for the amount calculation.
     *
     * @return array
     */
    private function selectAmountInput()
    {
        $info           = [];
        $converterClass = '';
        if (null !== $this->amount) {
            Log::debug('Amount value is not NULL, assume this is the correct value.');
            $converterClass = sprintf('FireflyIII\Import\Converter\%s', config(sprintf('csv.import_roles.%s.converter', $this->amount['role'])));
            $info           = $this->amount;
        }
        if (null !== $this->amountDebit) {
            Log::debug('Amount DEBIT value is not NULL, assume this is the correct value (overrules Amount).');
            $converterClass = sprintf('FireflyIII\Import\Converter\%s', config(sprintf('csv.import_roles.%s.converter', $this->amountDebit['role'])));
            $info           = $this->amountDebit;
        }
        if (null !== $this->amountCredit) {
            Log::debug('Amount CREDIT value is not NULL, assume this is the correct value (overrules Amount and AmountDebit).');
            $converterClass = sprintf('FireflyIII\Import\Converter\%s', config(sprintf('csv.import_roles.%s.converter', $this->amountCredit['role'])));
            $info           = $this->amountCredit;
        }
        $info['class'] = $converterClass;

        return $info;
    }

    /**
     * @param array $array
     */
    private function setTags(array $array): void
    {
        $preProcessorClass = config(sprintf('csv.import_roles.%s.pre-process-mapper', $array['role']));
        /** @var PreProcessorInterface $preProcessor */
        $preProcessor = app(sprintf('\FireflyIII\Import\MapperPreProcess\%s', $preProcessorClass));
        $tags         = $preProcessor->run($array['value']);
        $this->tags   = array_merge($this->tags, $tags);
    }
}
