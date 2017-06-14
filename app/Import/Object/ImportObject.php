<?php
/**
 * ImportObject.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Object;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\User;
use Illuminate\Support\Collection;

class ImportObject
{
    /** @var  Collection */
    public $errors;
    /** @var ImportAccount */
    private $asset;
    /** @var  ImportBill */
    private $bill;
    /** @var ImportBudget */
    private $budget;
    /** @var ImportCategory */
    private $category;
    /** @var  string */
    private $description;
    private $externalId = '';
    /** @var  string */
    private $hash;
    /** @var ImportAccount */
    private $opposing;
    private $tags = [];
    /** @var ImportTransaction */
    private $transaction;
    /** @var  User */
    private $user;

    /**
     * ImportEntry constructor.
     */
    public function __construct()
    {
        $this->errors      = new Collection;
        $this->transaction = new ImportTransaction;
        $this->asset       = new ImportAccount;
        $this->opposing    = new ImportAccount;
        $this->bill        = new ImportBill;
        $this->category    = new ImportCategory;
        $this->budget      = new ImportBudget;
    }

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
                throw new FireflyException(sprintf('ImportObject cannot handle "%s" with value "%s".', $array['role'], $array['value']));
            case 'account-id':
                $this->asset->setAccountId($array);
                break;
            case 'amount':
                $this->transaction->setAmount($array['value']);
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
                $this->transaction->getCurrency()->setCode($array);
                break;
            case 'currency-id':
                $this->transaction->getCurrency()->setId($array);
                break;
            case 'currency-name':
                $this->transaction->getCurrency()->setName($array);
                break;
            case 'currency-symbol':
                $this->transaction->getCurrency()->setSymbol($array);
                break;
            case 'date-transaction':
                $this->transaction->setDate($array['value']);
                break;
            case 'description':
                $this->description = $array['value'];
                $this->transaction->setDescription($array['value']);
                break;
            case 'external-id':
                $this->externalId = $array['value'];
                break;
            case '_ignore':
                break;
            case 'ing-debet-credit':
            case 'rabo-debet-credit':
                $this->transaction->addToModifier($array);
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