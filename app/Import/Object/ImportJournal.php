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
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class ImportJournal
 *
 * @package FireflyIII\Import\Object
 */
class ImportJournal
{
    /** @var  Collection */
    public $errors;
    /** @var string */
    private $amount = '0';
    /** @var ImportAccount */
    public $asset;
    /** @var  ImportBill */
    private $bill;
    /** @var ImportBudget */
    private $budget;
    /** @var ImportCategory */
    private $category;
    /** @var  ImportCurrency */
    private $currency;
    /** @var string */
    private $date = '';
    /** @var string */
    private $dateFormat = 'Ymd';
    /** @var  string */
    private $description;
    /** @var string */
    private $externalId = '';
    /** @var  string */
    private $hash;
    /** @var array */
    private $modifiers = [];
    /** @var ImportAccount */
    private $opposing;
    private $tags = [];
    /** @var string */
    private $transactionType = '';
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
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        // set user for related objects:
        $this->asset->setUser($user);
        $this->opposing->setUser($user);
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
                $this->description = $array['value'];
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
                $this->tags[] = $array;
                break;
        }
    }
}