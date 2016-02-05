<?php
declare(strict_types = 1);
/**
 * Entry.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export;

use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionJournal;

/**
 * To extend the exported object, in case of new features in Firefly III for example,
 * do the following:
 *
 * - Add the field(s) to this class
 * - Make sure the "fromJournal"-routine fills these fields.
 * - Add them to the static function that returns its type (key=value. Remember that the only
 *   valid types can be found in config/csv.php (under "roles").
 *
 * These new entries should be should be strings and numbers as much as possible.
 *
 *
 *
 * Class Entry
 *
 * @package FireflyIII\Export
 */
class Entry
{
    /** @var  string */
    public $amount;
    /** @var  int */
    public $billId;
    /** @var  string */
    public $billName;
    /** @var  int */
    public $budgetId;
    /** @var  string */
    public $budgetName;
    /** @var  int */
    public $categoryId;
    /** @var  string */
    public $categoryName;
    /** @var  string */
    public $date;
    /** @var  string */
    public $description;
    /** @var  string */
    public $fromAccountIban;
    /** @var  int */
    public $fromAccountId;
    /** @var  string */
    public $fromAccountName;
    /** @var  string */
    public $fromAccountType;
    /** @var  string */
    public $toAccountIban;
    /** @var  int */
    public $toAccountId;
    /** @var  string */
    public $toAccountName;
    /** @var  string */
    public $toAccountType;

    /**
     * @param TransactionJournal $journal
     *
     * @return Entry
     */
    public static function fromJournal(TransactionJournal $journal)
    {

        $entry = new self;
        $entry->setDescription($journal->description);
        $entry->setDate($journal->date->format('Y-m-d'));
        $entry->setAmount($journal->amount);

        /** @var Budget $budget */
        $budget = $journal->budgets->first();
        if (!is_null($budget)) {
            $entry->setBudgetId($budget->id);
            $entry->setBudgetName($budget->name);
        }

        /** @var Category $category */
        $category = $journal->categories->first();
        if (!is_null($category)) {
            $entry->setCategoryId($category->id);
            $entry->setCategoryName($category->name);
        }

        if (!is_null($journal->bill_id)) {
            $entry->setBillId($journal->bill_id);
            $entry->setBillName($journal->bill->name);
        }

        /** @var Account $sourceAccount */
        $sourceAccount = $journal->source_account;
        $entry->setFromAccountId($sourceAccount->id);
        $entry->setFromAccountName($sourceAccount->name);
        $entry->setFromAccountType($sourceAccount->accountType->type);

        /** @var Account $destination */
        $destination = $journal->destination_account;
        $entry->setToAccountId($destination->id);
        $entry->setToAccountName($destination->name);
        $entry->setToAccountType($destination->accountType->type);

        return $entry;

    }

    /**
     * @return array
     */
    public static function getTypes()
    {
        // key = field name (see top of class)
        // value = field type (see csv.php under 'roles')
        return [
            'amount'          => 'amount',
            'date'            => 'date-transaction',
            'description'     => 'description',
            'billId'          => 'bill-id',
            'billName'        => 'bill-name',
            'budgetId'        => 'budget-id',
            'budgetName'      => 'budget-name',
            'categoryId'      => 'category-id',
            'categoryName'    => 'category-name',
            'fromAccountId'   => 'account-id',
            'fromAccountName' => 'account-name',
            'fromAccountIban' => 'account-iban',
            'fromAccountType' => '_ignore',
            'toAccountId'     => 'opposing-id',
            'toAccountName'   => 'opposing-name',
            'toAccountIban'   => 'opposing-iban',
            'toAccountType'   => '_ignore',
        ];
    }

    /**
     * @return string
     */
    public function getAmount()
    {
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
     * @return int
     */
    public function getBillId()
    {
        return $this->billId;
    }

    /**
     * @param int $billId
     */
    public function setBillId($billId)
    {
        $this->billId = $billId;
    }

    /**
     * @return string
     */
    public function getBillName()
    {
        return $this->billName;
    }

    /**
     * @param string $billName
     */
    public function setBillName($billName)
    {
        $this->billName = $billName;
    }

    /**
     * @return int
     */
    public function getBudgetId()
    {
        return $this->budgetId;
    }

    /**
     * @param int $budgetId
     */
    public function setBudgetId($budgetId)
    {
        $this->budgetId = $budgetId;
    }

    /**
     * @return string
     */
    public function getBudgetName()
    {
        return $this->budgetName;
    }

    /**
     * @param string $budgetName
     */
    public function setBudgetName($budgetName)
    {
        $this->budgetName = $budgetName;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
    }

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->categoryName;
    }

    /**
     * @param string $categoryName
     */
    public function setCategoryName($categoryName)
    {
        $this->categoryName = $categoryName;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param string $date
     */
    public function setDate(string $date)
    {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getFromAccountIban()
    {
        return $this->fromAccountIban;
    }

    /**
     * @param string $fromAccountIban
     */
    public function setFromAccountIban($fromAccountIban)
    {
        $this->fromAccountIban = $fromAccountIban;
    }

    /**
     * @return int
     */
    public function getFromAccountId()
    {
        return $this->fromAccountId;
    }

    /**
     * @param int $fromAccountId
     */
    public function setFromAccountId($fromAccountId)
    {
        $this->fromAccountId = $fromAccountId;
    }

    /**
     * @return string
     */
    public function getFromAccountName()
    {
        return $this->fromAccountName;
    }

    /**
     * @param string $fromAccountName
     */
    public function setFromAccountName($fromAccountName)
    {
        $this->fromAccountName = $fromAccountName;
    }

    /**
     * @return string
     */
    public function getFromAccountType()
    {
        return $this->fromAccountType;
    }

    /**
     * @param string $fromAccountType
     */
    public function setFromAccountType($fromAccountType)
    {
        $this->fromAccountType = $fromAccountType;
    }

    /**
     * @return string
     */
    public function getToAccountIban()
    {
        return $this->toAccountIban;
    }

    /**
     * @param string $toAccountIban
     */
    public function setToAccountIban($toAccountIban)
    {
        $this->toAccountIban = $toAccountIban;
    }

    /**
     * @return int
     */
    public function getToAccountId()
    {
        return $this->toAccountId;
    }

    /**
     * @param int $toAccountId
     */
    public function setToAccountId($toAccountId)
    {
        $this->toAccountId = $toAccountId;
    }

    /**
     * @return string
     */
    public function getToAccountName()
    {
        return $this->toAccountName;
    }

    /**
     * @param string $toAccountName
     */
    public function setToAccountName($toAccountName)
    {
        $this->toAccountName = $toAccountName;
    }

    /**
     * @return string
     */
    public function getToAccountType()
    {
        return $this->toAccountType;
    }

    /**
     * @param string $toAccountType
     */
    public function setToAccountType($toAccountType)
    {
        $this->toAccountType = $toAccountType;
    }


}