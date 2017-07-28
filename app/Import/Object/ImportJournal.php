<?php
/**
 * ImportJournal.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Object;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Converter\Amount;
use FireflyIII\Import\Converter\ConverterInterface;
use FireflyIII\Import\MapperPreProcess\PreProcessorInterface;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Log;
use Steam;

/**
 * Class ImportJournal
 *
 * @package FireflyIII\Import\Object
 */
class ImportJournal
{
    /** @var ImportAccount */
    public $asset;
    /** @var  ImportBill */
    public $bill;
    /** @var ImportBudget */
    public $budget;
    /** @var ImportCategory */
    public $category;
    /** @var  string */
    public $description = '';
    /** @var  Collection */
    public $errors;
    /** @var  string */
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
    private $amount = '0';
    /** @var  ImportCurrency */
    private $currency;
    /** @var string */
    private $date = '';
    /** @var string */
    private $externalId = '';
    /** @var array */
    private $modifiers = [];
    /** @var  User */
    private $user;

    /**
     * ImportEntry constructor.
     */
    public function __construct()
    {
        $this->errors   = new Collection;
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
     * @return TransactionJournal
     * @throws FireflyException
     */
    public function createTransactionJournal(): TransactionJournal
    {
        exit('does not work yet');
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {

        /** @var ConverterInterface $amountConverter */
        $amountConverter = app(Amount::class);
        $this->amount    = $amountConverter->convert($this->amount);
        // modify
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
     * @return ImportCurrency
     */
    public function getCurrency(): ImportCurrency
    {
        return $this->currency;
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
        if ($this->description === '') {
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
