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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
    /** @var string */
    public $hash;
    /** @var array */
    public $metaDates = [];
    /** @var string */
    public $notes = '';
    /** @var ImportAccount */
    public $opposing;
    /** @var array */
    public $tags = [];
    /** @var string */
    private $amount;
    /** @var string */
    private $convertedAmount = null;
    /** @var string */
    private $date = '';
    /** @var string */
    private $externalId = '';
    /** @var array */
    private $modifiers = [];
    /** @var User */
    private $user;

    /**
     * ImportEntry constructor.
     */
    public function __construct()
    {
        $this->asset    = new ImportAccount;
        $this->opposing = new ImportAccount;
        $this->bill     = new ImportBill;
        $this->category = new ImportCategory;
        $this->budget   = new ImportBudget;
        $this->currency = new ImportCurrency;
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
        if (null === $this->convertedAmount) {
            Log::debug('convertedAmount is NULL');
            /** @var ConverterInterface $amountConverter */
            $amountConverter       = app(Amount::class);
            $this->convertedAmount = $amountConverter->convert($this->amount);
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
        switch ($array['role']) {
            default:
                throw new FireflyException(sprintf('ImportJournal cannot handle "%s" with value "%s".', $array['role'], $array['value']));
            case 'account-id':
                $this->asset->setAccountId($array);
                break;
            case 'amount':
                $this->amount = $array['value'];
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
            case 'sepa-ct-op':
            case 'sepa-ct-id':
            case 'sepa-db':
                $this->notes .= ' ' . $array['value'];
                $this->notes = trim($this->notes);
                break;
            case 'external-id':
                $this->externalId = $array['value'];
                break;
            case '_ignore':
                break;
            case 'ing-debet-credit':
            case 'rabo-debet-credit':
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
        }
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

        return;
    }
}
