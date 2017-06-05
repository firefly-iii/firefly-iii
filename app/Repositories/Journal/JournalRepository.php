<?php
/**
 * JournalRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\Journal;

use FireflyIII\Models\Account;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Log;
use Preferences;

/**
 * Class JournalRepository
 *
 * @package FireflyIII\Repositories\Journal
 */
class JournalRepository implements JournalRepositoryInterface
{
    /** @var User */
    private $user;
    /** @var array */
    private $validMetaFields = ['interest_date', 'book_date', 'process_date', 'due_date', 'payment_date', 'invoice_date', 'internal_reference', 'notes'];

    /**
     * @param TransactionJournal $journal
     * @param TransactionType    $type
     * @param Account            $source
     * @param Account            $destination
     *
     * @return MessageBag
     */
    public function convert(TransactionJournal $journal, TransactionType $type, Account $source, Account $destination): MessageBag
    {
        // default message bag that shows errors for everything.
        $messages = new MessageBag;
        $messages->add('source_account_revenue', trans('firefly.invalid_convert_selection'));
        $messages->add('destination_account_asset', trans('firefly.invalid_convert_selection'));
        $messages->add('destination_account_expense', trans('firefly.invalid_convert_selection'));
        $messages->add('source_account_asset', trans('firefly.invalid_convert_selection'));

        if ($source->id === $destination->id || is_null($source->id) || is_null($destination->id)) {
            return $messages;
        }

        $sourceTransaction             = $journal->transactions()->where('amount', '<', 0)->first();
        $destinationTransaction        = $journal->transactions()->where('amount', '>', 0)->first();
        $sourceTransaction->account_id = $source->id;
        $sourceTransaction->save();
        $destinationTransaction->account_id = $destination->id;
        $destinationTransaction->save();
        $journal->transaction_type_id = $type->id;
        $journal->save();

        // if journal is a transfer now, remove budget:
        if ($type->type === TransactionType::TRANSFER) {
            $journal->budgets()->detach();
        }

        Preferences::mark();

        return new MessageBag;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function delete(TransactionJournal $journal): bool
    {
        $journal->delete();

        return true;
    }

    /**
     * @param int $journalId
     *
     * @return TransactionJournal
     */
    public function find(int $journalId): TransactionJournal
    {
        $journal = $this->user->transactionJournals()->where('id', $journalId)->first();
        if (is_null($journal)) {
            return new TransactionJournal;
        }

        return $journal;
    }

    /**
     * Get users first transaction journal
     *
     * @return TransactionJournal
     */
    public function first(): TransactionJournal
    {
        $entry = $this->user->transactionJournals()->orderBy('date', 'ASC')->first(['transaction_journals.*']);

        if (is_null($entry)) {

            return new TransactionJournal;
        }

        return $entry;
    }

    /**
     * @return Collection
     */
    public function getTransactionTypes(): Collection
    {
        return TransactionType::orderBy('type', 'ASC')->get();
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function isTransfer(TransactionJournal $journal): bool
    {
        return $journal->transactionType->type === TransactionType::TRANSFER;
    }

    /**
     * @param TransactionJournal $journal
     * @param int                $order
     *
     * @return bool
     */
    public function setOrder(TransactionJournal $journal, int $order): bool
    {
        $journal->order = $order;
        $journal->save();

        return true;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return TransactionJournal
     */
    public function store(array $data): TransactionJournal
    {
        // find transaction type.
        /** @var TransactionType $transactionType */
        $transactionType = TransactionType::where('type', ucfirst($data['what']))->first();
        $accounts        = JournalSupport::storeAccounts($this->user, $transactionType, $data);
        $data            = JournalSupport::verifyNativeAmount($data, $accounts);
        $amount          = strval($data['amount']);
        $journal         = new TransactionJournal(
            [
                'user_id'                 => $this->user->id,
                'transaction_type_id'     => $transactionType->id,
                'transaction_currency_id' => $data['currency_id'], // no longer used.
                'description'             => $data['description'],
                'completed'               => 0,
                'date'                    => $data['date'],
            ]
        );
        $journal->save();

        // store stuff:
        JournalSupport::storeCategoryWithJournal($journal, $data['category']);
        JournalSupport::storeBudgetWithJournal($journal, $data['budget_id']);

        // store two transactions:
        $one = [
            'journal'                 => $journal,
            'account'                 => $accounts['source'],
            'amount'                  => bcmul($amount, '-1'),
            'transaction_currency_id' => $data['currency_id'],
            'foreign_amount'          => is_null($data['foreign_amount']) ? null : bcmul(strval($data['foreign_amount']), '-1'),
            'foreign_currency_id'     => $data['foreign_currency_id'],
            'description'             => null,
            'category'                => null,
            'budget'                  => null,
            'identifier'              => 0,
        ];
        JournalSupport::storeTransaction($one);

        $two = [
            'journal'                 => $journal,
            'account'                 => $accounts['destination'],
            'amount'                  => $amount,
            'transaction_currency_id' => $data['currency_id'],
            'foreign_amount'          => $data['foreign_amount'],
            'foreign_currency_id'     => $data['foreign_currency_id'],
            'description'             => null,
            'category'                => null,
            'budget'                  => null,
            'identifier'              => 0,
        ];

        JournalSupport::storeTransaction($two);


        // store tags
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->saveTags($journal, $data['tags']);
        }

        foreach ($data as $key => $value) {
            if (in_array($key, $this->validMetaFields)) {
                $journal->setMeta($key, $value);
                continue;
            }
            Log::debug(sprintf('Could not store meta field "%s" with value "%s" for journal #%d', json_encode($key), json_encode($value), $journal->id));
        }

        $journal->completed = 1;
        $journal->save();

        return $journal;

    }

    /**
     *
     * * Remember: a balancingAct takes at most one expense and one transfer.
     *            an advancePayment takes at most one expense, infinite deposits and NO transfers.
     *
     * @param TransactionJournal $journal
     * @param array              $array
     *
     * @return bool
     */
    private function saveTags(TransactionJournal $journal, array $array): bool
    {
        /** @var TagRepositoryInterface $tagRepository */
        $tagRepository = app(TagRepositoryInterface::class);

        foreach ($array as $name) {
            if (strlen(trim($name)) > 0) {
                $tag = Tag::firstOrCreateEncrypted(['tag' => $name, 'user_id' => $journal->user_id]);
                if (!is_null($tag)) {
                    Log::debug(sprintf('Will try to connect tag #%d to journal #%d.', $tag->id, $journal->id));
                    $tagRepository->connect($journal, $tag);
                }
            }
        }

        return true;
    }
}
