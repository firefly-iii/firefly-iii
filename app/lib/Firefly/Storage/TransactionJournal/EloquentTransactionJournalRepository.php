<?php


namespace Firefly\Storage\TransactionJournal;

use Carbon\Carbon;
use Firefly\Exception\FireflyException;
use Illuminate\Queue\Jobs\Job;

/**
 * Class EloquentTransactionJournalRepository
 *
 * @package Firefly\Storage\TransactionJournal
 */
class EloquentTransactionJournalRepository implements TransactionJournalRepositoryInterface
{
    protected $_user = null;

    /**
     *
     */
    public function __construct()
    {
        $this->_user = \Auth::user();
    }

    /**
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importTransfer(Job $job, array $payload)
    {
        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);


        if ($job->attempts() > 10) {
            \Log::error('Never found accounts for transfer "' . $payload['data']['description'] . '". KILL!');

            $importMap->jobsdone++;
            $importMap->save();

            $job->delete(); // count fixed
            return;
        }


        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->overruleUser($user);

        /*
         * Prep some variables from the payload:
         */
        $fromAccountId = intval($payload['data']['accountfrom_id']);
        $toAccountId   = intval($payload['data']['accountto_id']);
        $description   = $payload['data']['description'];
        $transferId    = intval($payload['data']['id']);
        $amount        = floatval($payload['data']['amount']);
        $date          = new Carbon($payload['data']['date']);

        /*
         * maybe Journal is already imported:
         */
        $importEntry = $repository->findImportEntry($importMap, 'Transfer', $transferId);

        /*
         * if so, delete job and return:
         */
        if (!is_null($importEntry)) {
            \Log::debug('Already imported transfer ' . $description);

            $importMap->jobsdone++;
            $importMap->save();

            $job->delete(); // count fixed
            return;
        }

        /*
         * Find the 'from' account:
         */
        $oldFromAccountEntry = $repository->findImportEntry($importMap, 'Account', $fromAccountId);
        $accountFrom         = $accounts->find($oldFromAccountEntry->new);

        /*
         * Find the 'to' account:
         */
        $oldToAccountEntry = $repository->findImportEntry($importMap, 'Account', $toAccountId);
        $accountTo         = $accounts->find($oldToAccountEntry->new);

        /*
         * If either is NULL, wait a bit and then reschedule.
         */
        if (is_null($accountTo) || is_null($accountFrom)) {
            \Log::notice('No account to, or account from. Release transfer ' . $description);
            if (\Config::get('queue.default') == 'sync') {
                $importMap->jobsdone++;
                $importMap->save();
                $job->delete(); // count fixed
            } else {
                $job->release(300); // proper release.
            }
            return;
        }

        /*
         * Import transfer:
         */


        $journal = $this->createSimpleJournal($accountFrom, $accountTo, $description, $amount, $date);
        $repository->store($importMap, 'Transfer', $transferId, $journal->id);
        \Log::debug('Imported transfer "' . $description . '" (' . $amount . ') (' . $date->format('Y-m-d') . ')');

        // update map:
        $importMap->jobsdone++;
        $importMap->save();

        $job->delete(); // count fixed.

    }

    /**
     * @param \User $user
     *
     * @return mixed|void
     */
    public function overruleUser(\User $user)
    {
        $this->_user = $user;
        return true;
    }

    public function store(array $data)
    {
        /*
         * Create the journal and fill relevant fields.
         */
        $journal              = new \TransactionJournal;
        $journal->description = trim($data['description']);
        $journal->date        = new Carbon($data['date']);
        $journal->user_id     = $this->_user->id;
        $journal->completed   = false;

        /*
         * Find the more complex fields and fill those:
         */
        $currency                         = \TransactionCurrency::where('code', 'EUR')->first();
        $journal->transaction_currency_id = $currency->id;
        $transactionType                  = \TransactionType::where('type', $data['what'])->first();
        $journal->transaction_type_id     = $transactionType->id;

        /*
         * Validatre & save journal
         */
        $journal->validate();
        $journal->save();

        /*
         * Return regardless.
         */
        return $journal;
    }

    /**
     * @param \TransactionJournal $journal
     * @param \Account            $account
     * @param                     $amount
     *
     * @return mixed
     */
    public function saveTransaction(\TransactionJournal $journal, \Account $account, $amount)
    {
        $transaction                         = new \Transaction;
        $transaction->account_id             = $account->id;
        $transaction->transaction_journal_id = $journal->id;
        $transaction->amount                 = $amount;
        if ($transaction->validate()) {
            $transaction->save();
        }
        return $transaction;

    }


    /**
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importTransaction(Job $job, array $payload)
    {
        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        if ($job->attempts() > 10) {
            \Log::error('Never found asset account for transaction "' . $payload['data']['description'] . '". KILL!');

            $importMap->jobsdone++;
            $importMap->save();

            $job->delete(); // count fixed
            return;
        }


        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->overruleUser($user);

        /*
         * Prep some vars coming out of the pay load:
         */
        $amount        = floatval($payload['data']['amount']);
        $date          = new Carbon($payload['data']['date']);
        $description   = $payload['data']['description'];
        $transactionId = intval($payload['data']['id']);
        $accountId     = intval($payload['data']['account_id']);

        /*
         * maybe Journal is already imported:
         */
        $importEntry = $repository->findImportEntry($importMap, 'Transaction', $transactionId);

        /*
         * if so, delete job and return:
         */
        if (!is_null($importEntry)) {
            \Log::debug('Already imported transaction ' . $description);

            $importMap->jobsdone++;
            $importMap->save();

            $job->delete(); // count fixed
            return;
        }


        /*
         * Find or create the "import account" which is used because at this point, Firefly
         * doesn't know which beneficiary (expense account) should be connected to this transaction.
         */
        $accountType   = $accounts->findAccountType('Import account');
        $importAccount = $accounts->firstOrCreate(
            [
                'account_type_id' => $accountType->id,
                'name'            => 'Import account',
                'user_id'         => $user->id,
                'active'          => 1,
            ]
        );
        unset($accountType);

        /*
         * Find the asset account this transaction is paid from / paid to:
         */
        $accountEntry = $repository->findImportEntry($importMap, 'Account', $accountId);
        $assetAccount = $accounts->find($accountEntry->new);
        unset($accountEntry);

        /*
         * If $assetAccount is null, we release this job and try later.
         */
        if (is_null($assetAccount)) {
            \Log::notice('No asset account for "' . $description . '", try again later.');
            if (\Config::get('queue.default') == 'sync') {
                $importMap->jobsdone++;
                $importMap->save();
                $job->delete(); // count fixed
            } else {
                $job->release(300); // proper release.
            }
            return;
        }

        /*
         * If the amount is less than zero, we move money to the $importAccount. Otherwise,
         * we move it from the $importAccount.
         */
        if ($amount < 0) {
            // if amount is less than zero, move to $importAccount
            $accountFrom = $assetAccount;
            $accountTo   = $importAccount;
        } else {
            $accountFrom = $importAccount;
            $accountTo   = $assetAccount;
        }

        /*
         * Modify the amount so it will work with or new transaction journal structure.
         */
        $amount = $amount < 0 ? $amount * -1 : $amount;

        /*
         * Import it:
         */
        $journal = $this->createSimpleJournal($accountFrom, $accountTo, $description, $amount, $date);
        $repository->store($importMap, 'Transaction', $transactionId, $journal->id);
        \Log::debug('Imported transaction "' . $description . '" (' . $amount . ') (' . $date->format('Y-m-d') . ')');

        // update map:
        $importMap->jobsdone++;
        $importMap->save();

        $job->delete(); // count fixed
        return;

    }

    /**
     * @param $journalId
     *
     * @return mixed
     */
    public function find($journalId)
    {
        return $this->_user->transactionjournals()->with(
            ['transactions' => function ($q) {
                return $q->orderBy('amount', 'ASC');
            }, 'transactioncurrency', 'transactiontype', 'components', 'transactions.account',
             'transactions.account.accounttype']
        )
            ->where('id', $journalId)->first();
    }

//    /**
//     *
//     */
//    public function get()
//    {
//
//    }

    /**
     * @param $type
     *
     * @return \TransactionType
     */
    public function getTransactionType($type)
    {
        return \TransactionType::whereType($type)->first();
    }

    /**
     * @param \Account $account
     * @param Carbon   $date
     *
     * @return mixed
     */
    public function getByAccountAndDate(\Account $account, Carbon $date)
    {
        $accountID = $account->id;
        $query     = $this->_user->transactionjournals()->with(
            [
                'transactions',
                'transactions.account',
                'transactioncurrency',
                'transactiontype'
            ]
        )
            ->distinct()
            ->leftJoin(
                'transactions', 'transactions.transaction_journal_id', '=',
                'transaction_journals.id'
            )
            ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->where('transactions.account_id', $accountID)
            ->where('transaction_journals.date', $date->format('Y-m-d'))
            ->orderBy('transaction_journals.date', 'DESC')
            ->orderBy('transaction_journals.id', 'DESC')
            ->get(['transaction_journals.*']);

        return $query;
    }

    /**
     * @param \Account $account
     * @param int      $count
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return mixed
     */
    public function getByAccountInDateRange(\Account $account, $count = 25, Carbon $start, Carbon $end)
    {
        $accountID = $account->id;
        $query     = $this->_user->transactionjournals()->with(
            [
                'transactions',
                'transactioncurrency',
                'transactiontype'
            ]
        )
            ->leftJoin(
                'transactions', 'transactions.transaction_journal_id', '=',
                'transaction_journals.id'
            )
            ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->where('accounts.id', $accountID)
            ->where('date', '>=', $start->format('Y-m-d'))
            ->where('date', '<=', $end->format('Y-m-d'))
            ->orderBy('transaction_journals.date', 'DESC')
            ->orderBy('transaction_journals.id', 'DESC')
            ->take($count)
            ->get(['transaction_journals.*']);

        return $query;
    }

    /**
     * @param \TransactionType $type
     * @param int              $count
     * @param Carbon           $start
     * @param Carbon           $end
     *
     * @return mixed
     */
    public function paginate(\TransactionType $type, $count = 25, Carbon $start = null, Carbon $end = null)
    {
        $query = $this->_user->transactionjournals()->WithRelevantData()
            ->transactionTypes([$type->type])
            ->orderBy('transaction_journals.date', 'DESC')
            ->orderBy('transaction_journals.id', 'DESC');
        if (!is_null($start)) {
            $query->where('transaction_journals.date', '>=', $start->format('Y-m-d'));
        }
        if (!is_null($end)) {
            $query->where('transaction_journals.date', '<=', $end->format('Y-m-d'));
        }

        $result = $query->select(['transaction_journals.*'])->paginate($count);

        return $result;
    }

    /**
     * @param \TransactionJournal $journal
     * @param                     $data
     *
     * @return mixed|\TransactionJournal
     * @throws \Firefly\Exception\FireflyException
     */
    public function update(\TransactionJournal $journal, $data)
    {
        /** @var \Firefly\Storage\Category\CategoryRepositoryInterface $catRepository */
        $catRepository = \App::make('Firefly\Storage\Category\CategoryRepositoryInterface');
        $catRepository->overruleUser($this->_user);

        /** @var \Firefly\Storage\Budget\BudgetRepositoryInterface $budgetRepository */
        $budRepository = \App::make('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budRepository->overruleUser($this->_user);

        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accountRepository */
        $accountRepository = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $accountRepository->overruleUser($this->_user);


        // update basics first:
        $journal->description = $data['description'];
        $journal->date        = $data['date'];
        $amount               = floatval($data['amount']);

        // remove previous category, if any:
        if (!is_null($journal->categories()->first())) {
            $journal->categories()->detach($journal->categories()->first()->id);
        }
        // remove previous budget, if any:
        if (!is_null($journal->budgets()->first())) {
            $journal->budgets()->detach($journal->budgets()->first()->id);
        }
        // remove previous piggy bank, if any:


        $category = isset($data['category']) ? $catRepository->findByName($data['category']) : null;
        if (!is_null($category)) {
            $journal->categories()->attach($category);
        }
        // update the amounts:
        $transactions = $journal->transactions()->orderBy('amount', 'ASC')->get();

        // remove previous piggy bank, if any:
        /** @var \Transaction $transaction */
        foreach ($transactions as $transaction) {
            if (!is_null($transaction->piggybank()->first())) {
                $transaction->piggybank_id = null;
                $transaction->save();
            }
        }
        unset($transaction);

        $transactions[0]->amount = $amount * -1;
        $transactions[1]->amount = $amount;

        // switch on type to properly change things:
        $fireEvent = false;
        switch ($journal->transactiontype->type) {
            case 'Withdrawal':
                // means transaction[0] is the users account.
                $account     = $accountRepository->find($data['account_id']);
                $beneficiary = $accountRepository->createOrFindBeneficiary($data['beneficiary']);
                $transactions[0]->account()->associate($account);
                $transactions[1]->account()->associate($beneficiary);

                // do budget:
                $budget = $budRepository->find($data['budget_id']);
                if (!is_null($budget)) {
                    $journal->budgets()->attach($budget);
                }

                break;
            case 'Deposit':
                // means transaction[0] is the beneficiary.
                $account     = $accountRepository->find($data['account_id']);
                $beneficiary = $accountRepository->createOrFindBeneficiary($data['beneficiary']);
                $journal->transactions[0]->account()->associate($beneficiary);
                $journal->transactions[1]->account()->associate($account);
                break;
            case 'Transfer':
                // means transaction[0] is account that sent the money (from).
                /** @var \Account $fromAccount */
                $fromAccount = $accountRepository->find($data['account_from_id']);
                /** @var \Account $toAccount */
                $toAccount = $accountRepository->find($data['account_to_id']);
                $journal->transactions[0]->account()->associate($fromAccount);
                $journal->transactions[1]->account()->associate($toAccount);

                // attach the new piggy bank, if valid:
                /** @var \Firefly\Storage\Piggybank\PiggybankRepositoryInterface $piggyRepository */
                $piggyRepository = \App::make('Firefly\Storage\Piggybank\PiggybankRepositoryInterface');
                $piggyRepository->overruleUser($this->_user);

                if (isset($data['piggybank_id'])) {
                    /** @var \Piggybank $piggyBank */
                    $piggyBank = $piggyRepository->find(intval($data['piggybank_id']));

                    // loop transactions and re-attach the piggy bank:

                    if ($piggyBank) {

                        $connected = false;
                        foreach ($journal->transactions()->get() as $transaction) {
                            if ($transaction->account_id == $piggyBank->account_id) {
                                $connected = true;
                                $transaction->piggybank()->associate($piggyBank);
                                $transaction->save();
                                $fireEvent = true;
                                break;
                            }
                        }
                        if ($connected === false) {
                            \Session::flash(
                                'warning', 'Piggy bank "' . e($piggyBank->name)
                                . '" is not set to draw money from any of the accounts in this transfer'
                            );
                        }
                    }
                }


                break;
            default:
                throw new FireflyException('Cannot edit this!');
                break;
        }

        $transactions[0]->save();
        $transactions[1]->save();
        if ($journal->validate()) {
            $journal->save();
        }
        if ($fireEvent) {
            \Event::fire('piggybanks.updateRelatedTransfer', [$piggyBank]);
        }

        return $journal;


    }


}