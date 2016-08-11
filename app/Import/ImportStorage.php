<?php
/**
 * ImportStorage.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class ImportStorage
 *
 * @package FireflyIII\Import
 */
class ImportStorage
{

    /** @var  Collection */
    public $entries;

    /** @var  User */
    public $user;

    /**
     * ImportStorage constructor.
     *
     * @param Collection $entries
     */
    public function __construct(Collection $entries)
    {
        $this->entries = $entries;

    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     *
     */
    public function store()
    {
        foreach ($this->entries as $entry) {
            Log::debug('--- import store start ---');
            $this->storeSingle($entry);
        }

    }

    /**
     * @param float $amount
     *
     * @return string
     */
    private function makePositive(float $amount): string
    {
        $amount = strval($amount);
        if (bccomp($amount, '0', 4) === -1) { // left is larger than right
            $amount = bcmul($amount, '-1');
        }

        return $amount;
    }

    /**
     * @param ImportEntry $entry
     *
     * @throws FireflyException
     */
    private function storeSingle(ImportEntry $entry)
    {
        if ($entry->valid === false) {
            Log::error('Cannot import entry, because valid=false');

            return;
        }

        Log::debug('Going to store entry!');
        $billId      = is_null($entry->fields['bill']) ? null : $entry->fields['bill']->id;
        $journalData = [
            'user_id'                 => $entry->user->id,
            'transaction_type_id'     => $entry->fields['transaction-type']->id,
            'bill_id'                 => $billId,
            'transaction_currency_id' => $entry->fields['currency']->id,
            'description'             => $entry->fields['description'],
            'date'                    => $entry->fields['date-transaction'],
            'interest_date'           => $entry->fields['date-interest'],
            'book_date'               => $entry->fields['date-book'],
            'process_date'            => $entry->fields['date-process'],
            'completed'               => 0,
        ];
        /** @var TransactionJournal $journal */
        $journal = TransactionJournal::create($journalData);
        $amount  = $this->makePositive($entry->fields['amount']);

        Log::debug('Created journal', ['id' => $journal->id]);

        // then create transactions. Single ones, unfortunately.
        switch ($entry->fields['transaction-type']->type) {
            default:
                throw new FireflyException('ImportStorage cannot handle ' . $entry->fields['transaction-type']->type);
            case TransactionType::WITHDRAWAL:
                $source      = $entry->fields['asset-account'];
                $destination = $entry->fields['opposing-account'];
                // make amount positive, if it is not.
                break;
            case TransactionType::DEPOSIT:
                $source      = $entry->fields['opposing-account'];
                $destination = $entry->fields['asset-account'];
                break;
            case TransactionType::TRANSFER:
                // depends on amount:
                if ($entry->fields['amount'] < 0) {
                    $source      = $entry->fields['asset-account'];
                    $destination = $entry->fields['opposing-account'];
                    break;
                }
                $destination = $entry->fields['asset-account'];
                $source      = $entry->fields['opposing-account'];
                break;
        }

        // create new transactions. This is something that needs a rewrite for multiple/split transactions.
        $sourceData = [
            'account_id'             => $source->id,
            'transaction_journal_id' => $journal->id,
            'description'            => $journalData['description'],
            'amount'                 => bcmul($amount, '-1'),
        ];

        $destinationData = [
            'account_id'             => $destination->id,
            'transaction_journal_id' => $journal->id,
            'description'            => $journalData['description'],
            'amount'                 => $amount,
        ];

        $one = Transaction::create($sourceData);
        $two = Transaction::create($destinationData);
        Log::debug('Created transaction 1', ['id' => $one->id, 'account' => $one->account_id,'account_name' => $source->name]);
        Log::debug('Created transaction 2', ['id' => $two->id, 'account' => $two->account_id,'account_name' => $destination->name]);

        $journal->completed = 1;
        $journal->save();

        // now attach budget and so on.


    }
}