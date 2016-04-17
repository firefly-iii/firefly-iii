<?php
declare(strict_types = 1);
/**
 * Entry.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
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
    public $fromAccountNumber;
    /** @var  string */
    public $fromAccountType;
    /** @var  string */
    public $toAccountIban;
    /** @var  int */
    public $toAccountId;
    /** @var  string */
    public $toAccountName;
    public $toAccountNumber;
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
        $entry->setAmount(TransactionJournal::amount($journal));

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
        $sourceAccount = TransactionJournal::sourceAccount($journal);
        $entry->setFromAccountId($sourceAccount->id);
        $entry->setFromAccountName($sourceAccount->name);
        $entry->setFromAccountIban($sourceAccount->iban);
        $entry->setFromAccountType($sourceAccount->accountType->type);
        $entry->setFromAccountNumber($sourceAccount->getMeta('accountNumber'));


        /** @var Account $destination */
        $destination = TransactionJournal::destinationAccount($journal);
        $entry->setToAccountId($destination->id);
        $entry->setToAccountName($destination->name);
        $entry->setToAccountIban($destination->iban);
        $entry->setToAccountType($destination->accountType->type);
        $entry->setToAccountNumber($destination->getMeta('accountNumber'));

        return $entry;

    }

    /**
     * @return array
     */
    public static function getTypes(): array
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
            'fromAccountType' => '_ignore', // no, Firefly cannot import what it exports. I know :D
            'toAccountId'     => 'opposing-id',
            'toAccountName'   => 'opposing-name',
            'toAccountIban'   => 'opposing-iban',
            'toAccountType'   => '_ignore',
        ];
    }

    /**
     * @return string
     */
    public function getAmount(): string
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
    public function getBillId(): int
    {
        return $this->billId;
    }

    /**
     * @param int $billId
     */
    public function setBillId(int $billId)
    {
        $this->billId = $billId;
    }

    /**
     * @return string
     */
    public function getBillName(): string
    {
        return $this->billName;
    }

    /**
     * @param string $billName
     */
    public function setBillName(string $billName)
    {
        $this->billName = $billName;
    }

    /**
     * @return int
     */
    public function getBudgetId(): int
    {
        return $this->budgetId;
    }

    /**
     * @param int $budgetId
     */
    public function setBudgetId(int $budgetId)
    {
        $this->budgetId = $budgetId;
    }

    /**
     * @return string
     */
    public function getBudgetName(): string
    {
        return $this->budgetName;
    }

    /**
     * @param string $budgetName
     */
    public function setBudgetName(string $budgetName)
    {
        $this->budgetName = $budgetName;
    }

    /**
     * @return int
     */
    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     */
    public function setCategoryId(int $categoryId)
    {
        $this->categoryId = $categoryId;
    }

    /**
     * @return string
     */
    public function getCategoryName(): string
    {
        return $this->categoryName;
    }

    /**
     * @param string $categoryName
     */
    public function setCategoryName(string $categoryName)
    {
        $this->categoryName = $categoryName;
    }

    /**
     * @return string
     */
    public function getDate(): string
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
    public function getDescription(): string
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
    public function getFromAccountIban(): string
    {
        return $this->fromAccountIban;
    }

    /**
     * @param string $fromAccountIban
     */
    public function setFromAccountIban(string $fromAccountIban)
    {
        $this->fromAccountIban = $fromAccountIban;
    }

    /**
     * @return int
     */
    public function getFromAccountId():int
    {
        return $this->fromAccountId;
    }

    /**
     * @param int $fromAccountId
     */
    public function setFromAccountId(int $fromAccountId)
    {
        $this->fromAccountId = $fromAccountId;
    }

    /**
     * @return string
     */
    public function getFromAccountName(): string
    {
        return $this->fromAccountName;
    }

    /**
     * @param string $fromAccountName
     */
    public function setFromAccountName(string $fromAccountName)
    {
        $this->fromAccountName = $fromAccountName;
    }

    /**
     * @return string
     */
    public function getFromAccountNumber(): string
    {
        return $this->fromAccountNumber;
    }

    /**
     * @param string $fromAccountNumber
     */
    public function setFromAccountNumber(string $fromAccountNumber)
    {
        $this->fromAccountNumber = $fromAccountNumber;
    }

    /**
     * @return string
     */
    public function getFromAccountType(): string
    {
        return $this->fromAccountType;
    }

    /**
     * @param string $fromAccountType
     */
    public function setFromAccountType(string $fromAccountType)
    {
        $this->fromAccountType = $fromAccountType;
    }

    /**
     * @return string
     */
    public function getToAccountIban(): string
    {
        return $this->toAccountIban;
    }

    /**
     * @param string $toAccountIban
     */
    public function setToAccountIban(string $toAccountIban)
    {
        $this->toAccountIban = $toAccountIban;
    }

    /**
     * @return int
     */
    public function getToAccountId(): int
    {
        return $this->toAccountId;
    }

    /**
     * @param int $toAccountId
     */
    public function setToAccountId(int $toAccountId)
    {
        $this->toAccountId = $toAccountId;
    }

    /**
     * @return string
     */
    public function getToAccountName(): string
    {
        return $this->toAccountName;
    }

    /**
     * @param string $toAccountName
     */
    public function setToAccountName(string $toAccountName)
    {
        $this->toAccountName = $toAccountName;
    }

    /**
     * @return string
     */
    public function getToAccountNumber(): string
    {
        return $this->toAccountNumber;
    }

    /**
     * @param string $toAccountNumber
     */
    public function setToAccountNumber(string $toAccountNumber)
    {
        $this->toAccountNumber = $toAccountNumber;
    }

    /**
     * @return string
     */
    public function getToAccountType(): string
    {
        return $this->toAccountType;
    }

    /**
     * @param string $toAccountType
     */
    public function setToAccountType(string $toAccountType)
    {
        $this->toAccountType = $toAccountType;
    }


}
