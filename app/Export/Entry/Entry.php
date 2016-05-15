<?php
declare(strict_types = 1);
/**
 * Entry.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export\Entry;

use FireflyIII\Models\TransactionJournal;

/**
 * To extend the exported object, in case of new features in Firefly III for example,
 * do the following:
 *
 * - Add the field(s) to this class. If you add more than one related field, add a new object.
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
 * @package FireflyIII\Export\Entry
 */
class Entry
{
    /** @var  string */
    public $amount;
    /** @var  EntryBill */
    public $bill;
    /** @var  EntryBudget */
    public $budget;
    /** @var  EntryCategory */
    public $category;
    /** @var  string */
    public $date;
    /** @var  string */
    public $description;
    /** @var  EntryAccount */
    public $destinationAccount;
    /** @var  EntryAccount */
    public $sourceAccount;

    /**
     * @param TransactionJournal $journal
     *
     * @return Entry
     */
    public static function fromJournal(TransactionJournal $journal)
    {

        $entry              = new self;
        $entry->description = $journal->description;
        $entry->date        = $journal->date->format('Y-m-d');
        $entry->amount      = TransactionJournal::amount($journal);

        $entry->budget   = new EntryBudget($journal->budgets->first());
        $entry->category = new EntryCategory($journal->categories->first());
        $entry->bill     = new EntryBill($journal->bill);

        // TODO support split journals
        $sources                   = TransactionJournal::sourceAccountList($journal);
        $entry->sourceAccount      = new EntryAccount($sources->first());
        $destinations              = TransactionJournal::destinationAccountList($journal);
        $entry->destinationAccount = new EntryAccount($destinations->first());

        return $entry;

    }

    /**
     * @return array
     */
    public static function getFieldsAndTypes(): array
    {
        // key = field name (see top of class)
        // value = field type (see csv.php under 'roles')
        return [
            'description'                => 'description',
            'amount'                     => 'amount',
            'date'                       => 'date-transaction',
            'source_account_id'          => 'account-id',
            'source_account_name'        => 'account-name',
            'source_account_iban'        => 'account-iban',
            'source_account_type'        => '_ignore',
            'source_account_number'      => 'account-number',
            'destination_account_id'     => 'opposing-id',
            'destination_account_name'   => 'opposing-name',
            'destination_account_iban'   => 'opposing-iban',
            'destination_account_type'   => '_ignore',
            'destination_account_number' => 'account-number',
            'budget_id'                  => 'budget-id',
            'budget_name'                => 'budget-name',
            'category_id'                => 'category-id',
            'category_name'              => 'category-name',
            'bill_id'                    => 'bill-id',
            'bill_name'                  => 'bill-name',
        ];
    }

}
