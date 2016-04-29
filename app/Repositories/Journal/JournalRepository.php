<?php
declare(strict_types = 1);

namespace FireflyIII\Repositories\Journal;

use Carbon\Carbon;
use DB;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;

/**
 * Class JournalRepository
 *
 * @package FireflyIII\Repositories\Journal
 */
class JournalRepository implements JournalRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * JournalRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
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
    public function find(int $journalId) : TransactionJournal
    {
        $journal = $this->user->transactionjournals()->where('id', $journalId)->first();
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
        $entry = $this->user->transactionjournals()->orderBy('date', 'ASC')->first(['transaction_journals.*']);
        if (is_null($entry)) {
            return new TransactionJournal;
        }

        return $entry;
    }

    /**
     * @param TransactionJournal $journal
     * @param Transaction        $transaction
     *
     * @return string
     */
    public function getAmountBefore(TransactionJournal $journal, Transaction $transaction): string
    {
        $set = $transaction->account->transactions()->leftJoin(
            'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
        )
                                    ->where('transaction_journals.date', '<=', $journal->date->format('Y-m-d'))
                                    ->where('transaction_journals.order', '>=', $journal->order)
                                    ->where('transaction_journals.id', '!=', $journal->id)
                                    ->get(['transactions.*']);
        $sum = '0';
        foreach ($set as $entry) {
            $sum = bcadd($entry->amount, $sum);
        }

        return $sum;

    }

    /**
     * @param array $types
     * @param int   $offset
     * @param int   $count
     *
     * @return Collection
     */
    public function getCollectionOfTypes(array $types, int $offset, int $count): Collection
    {
        $set = $this->user->transactionJournals()
                          ->expanded()
                          ->transactionTypes($types)
                          ->take($count)->offset($offset)
                          ->orderBy('date', 'DESC')
                          ->orderBy('order', 'ASC')
                          ->orderBy('id', 'DESC')
                          ->get(TransactionJournal::QUERYFIELDS);

        return $set;
    }

    /**
     * @param TransactionType $dbType
     *
     * @return Collection
     */
    public function getJournalsOfType(TransactionType $dbType): Collection
    {
        return $this->user->transactionjournals()->where('transaction_type_id', $dbType->id)->orderBy('id', 'DESC')->take(50)->get();
    }

    /**
     * @param array $types
     * @param int   $page
     * @param int   $pageSize
     *
     * @return LengthAwarePaginator
     */
    public function getJournalsOfTypes(array $types, int $page, int $pageSize = 50): LengthAwarePaginator
    {
        $offset = ($page - 1) * $pageSize;
        $query  = $this->user
            ->transactionJournals()
            ->expanded()
            ->transactionTypes($types);


        $count    = $query->count();
        $set      = $query->take($pageSize)->offset($offset)->get(TransactionJournal::QUERYFIELDS);
        $journals = new LengthAwarePaginator($set, $count, $pageSize, $page);

        return $journals;
    }

    /**
     * @param string $type
     *
     * @return TransactionType
     */
    public function getTransactionType(string $type): TransactionType
    {
        return TransactionType::whereType($type)->first();
    }

    /**
     * @param int    $journalId
     * @param Carbon $date
     *
     * @return TransactionJournal
     */
    public function getWithDate(int $journalId, Carbon $date): TransactionJournal
    {
        return $this->user->transactionjournals()->where('id', $journalId)->where('date', $date->format('Y-m-d 00:00:00'))->first();
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
    public function saveTags(TransactionJournal $journal, array $array): bool
    {
        /** @var \FireflyIII\Repositories\Tag\TagRepositoryInterface $tagRepository */
        $tagRepository = app('FireflyIII\Repositories\Tag\TagRepositoryInterface');

        foreach ($array as $name) {
            if (strlen(trim($name)) > 0) {
                $tag = Tag::firstOrCreateEncrypted(['tag' => $name, 'user_id' => $journal->user_id]);
                if (!is_null($tag)) {
                    $tagRepository->connect($journal, $tag);
                }
            }
        }

        return true;
    }

    /**
     * @param array $data
     *
     * @return TransactionJournal
     */
    public function store(array $data): TransactionJournal
    {
        // find transaction type.
        $transactionType = TransactionType::where('type', ucfirst($data['what']))->first();

        // store actual journal.
        $journal = new TransactionJournal(
            [
                'user_id'                 => $data['user'],
                'transaction_type_id'     => $transactionType->id,
                'transaction_currency_id' => $data['amount_currency_id_amount'],
                'description'             => $data['description'],
                'completed'               => 0,
                'date'                    => $data['date'],
                'interest_date'           => $data['interest_date'],
                'book_date'               => $data['book_date'],
                'process_date'            => $data['process_date'],
            ]
        );
        $journal->save();


        // store or get category
        if (strlen($data['category']) > 0) {
            $category = Category::firstOrCreateEncrypted(['name' => $data['category'], 'user_id' => $data['user']]);
            $journal->categories()->save($category);
        }

        // store or get budget
        if (intval($data['budget_id']) > 0) {
            /** @var \FireflyIII\Models\Budget $budget */
            $budget = Budget::find($data['budget_id']);
            $journal->budgets()->save($budget);
        }

        // store accounts (depends on type)
        list($sourceAccount, $destinationAccount) = $this->storeAccounts($transactionType, $data);

        // store accompanying transactions.
        Transaction::create( // first transaction.
            [
                'account_id'             => $sourceAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $data['amount'] * -1,
            ]
        );
        Transaction::create( // second transaction.
            [
                'account_id'             => $destinationAccount->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $data['amount'],
            ]
        );
        $journal->completed = 1;
        $journal->save();

        // store tags
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->saveTags($journal, $data['tags']);
        }

        return $journal;


    }

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     */
    public function update(TransactionJournal $journal, array $data): TransactionJournal
    {
        // update actual journal.
        $journal->transaction_currency_id = $data['amount_currency_id_amount'];
        $journal->description             = $data['description'];
        $journal->date                    = $data['date'];
        $journal->interest_date           = $data['interest_date'];
        $journal->book_date               = $data['book_date'];
        $journal->process_date            = $data['process_date'];


        // unlink all categories, recreate them:
        $journal->categories()->detach();
        if (strlen($data['category']) > 0) {
            $category = Category::firstOrCreateEncrypted(['name' => $data['category'], 'user_id' => $data['user']]);
            $journal->categories()->save($category);
        }

        // unlink all budgets and recreate them:
        $journal->budgets()->detach();
        if (intval($data['budget_id']) > 0) {
            /** @var \FireflyIII\Models\Budget $budget */
            $budget = Budget::find($data['budget_id']);
            $journal->budgets()->save($budget);
        }

        // store accounts (depends on type)
        list($fromAccount, $toAccount) = $this->storeAccounts($journal->transactionType, $data);

        // update the from and to transaction.
        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            if ($transaction->amount < 0) {
                // this is the from transaction, negative amount:
                $transaction->amount     = $data['amount'] * -1;
                $transaction->account_id = $fromAccount->id;
                $transaction->save();
            }
            if ($transaction->amount > 0) {
                $transaction->amount     = $data['amount'];
                $transaction->account_id = $toAccount->id;
                $transaction->save();
            }
        }


        $journal->save();

        // update tags:
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->updateTags($journal, $data['tags']);
        }

        return $journal;
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $array
     *
     * @return bool
     */
    public function updateTags(TransactionJournal $journal, array $array): bool
    {
        // create tag repository
        /** @var \FireflyIII\Repositories\Tag\TagRepositoryInterface $tagRepository */
        $tagRepository = app('FireflyIII\Repositories\Tag\TagRepositoryInterface');


        // find or create all tags:
        $tags = [];
        $ids  = [];
        foreach ($array as $name) {
            if (strlen(trim($name)) > 0) {
                $tag    = Tag::firstOrCreateEncrypted(['tag' => $name, 'user_id' => $journal->user_id]);
                $tags[] = $tag;
                $ids[]  = $tag->id;
            }
        }

        // delete all tags connected to journal not in this array:
        if (count($ids) > 0) {
            DB::table('tag_transaction_journal')->where('transaction_journal_id', $journal->id)->whereNotIn('tag_id', $ids)->delete();
        }
        // if count is zero, delete them all:
        if (count($ids) == 0) {
            DB::table('tag_transaction_journal')->where('transaction_journal_id', $journal->id)->delete();
        }

        // connect each tag to journal (if not yet connected):
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $tagRepository->connect($journal, $tag);
        }

        return true;
    }

    /**
     * @param TransactionType $type
     * @param array           $data
     *
     * @return array
     * @throws FireflyException
     */
    protected function storeAccounts(TransactionType $type, array $data): array
    {
        $sourceAccount      = null;
        $destinationAccount = null;
        switch ($type->type) {
            case TransactionType::WITHDRAWAL:
                list($sourceAccount, $destinationAccount) = $this->storeWithdrawalAccounts($data);
                break;

            case TransactionType::DEPOSIT:
                list($sourceAccount, $destinationAccount) = $this->storeDepositAccounts($data);

                break;
            case TransactionType::TRANSFER:
                $sourceAccount      = Account::find($data['account_from_id']);
                $destinationAccount = Account::find($data['account_to_id']);
                break;
            default:
                throw new FireflyException('Did not recognise transaction type.');
        }

        if (is_null($destinationAccount)) {
            Log::error('"to"-account is null, so we cannot continue!', ['data' => $data]);
            throw new FireflyException('"to"-account is null, so we cannot continue!');
        }

        if (is_null($sourceAccount)) {
            Log::error('"from"-account is null, so we cannot continue!', ['data' => $data]);
            throw new FireflyException('"from"-account is null, so we cannot continue!');

        }


        return [$sourceAccount, $destinationAccount];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function storeDepositAccounts(array $data): array
    {
        $destinationAccount = Account::where('user_id', $this->user->id)->where('id', $data['account_destination_id'])->first(['accounts.*']);

        if (strlen($data['source_account_name']) > 0) {
            $fromType    = AccountType::where('type', 'Revenue account')->first();
            $fromAccount = Account::firstOrCreateEncrypted(
                ['user_id' => $data['user'], 'account_type_id' => $fromType->id, 'name' => $data['source_account_name'], 'active' => 1]
            );
            return [$fromAccount, $destinationAccount];
        } else {
            $fromType      = AccountType::where('type', 'Cash account')->first();
            $fromAccount = Account::firstOrCreateEncrypted(
                ['user_id' => $data['user'], 'account_type_id' => $fromType->id, 'name' => 'Cash account', 'active' => 1]
            );
        }

        return [$fromAccount, $destinationAccount];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function storeWithdrawalAccounts(array $data): array
    {
        $sourceAccount = Account::where('user_id', $this->user->id)->where('id', $data['source_account_id'])->first(['accounts.*']);

        if (strlen($data['destination_account_name']) > 0) {
            $destinationType    = AccountType::where('type', 'Expense account')->first();
            $destinationAccount = Account::firstOrCreateEncrypted(
                [
                    'user_id'         => $data['user'],
                    'account_type_id' => $destinationType->id,
                    'name'            => $data['destination_account_name'],
                    'active'          => 1,
                ]
            );

            return [$sourceAccount, $destinationAccount];
        }
        $destinationType    = AccountType::where('type', 'Cash account')->first();
        $destinationAccount = Account::firstOrCreateEncrypted(
            ['user_id' => $data['user'], 'account_type_id' => $destinationType->id, 'name' => 'Cash account', 'active' => 1]
        );

        return [$sourceAccount, $destinationAccount];


    }
}
