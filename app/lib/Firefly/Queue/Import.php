<?php

namespace Firefly\Queue;

use Carbon\Carbon;
use Illuminate\Queue\Jobs\Job;

/**
 * Class Import
 *
 * @package Firefly\Queue
 */
class Import
{
    /** @var \Firefly\Storage\Account\AccountRepositoryInterface */
    protected $_accounts;

    /** @var \Firefly\Storage\Import\ImportRepositoryInterface */
    protected $_repository;

    /** @var \Firefly\Storage\Budget\BudgetRepositoryInterface */
    protected $_budgets;

    /** @var \Firefly\Storage\Category\CategoryRepositoryInterface */
    protected $_categories;

    /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface */
    protected $_journals;

    /** @var \Firefly\Storage\Limit\LimitRepositoryInterface */
    protected $_limits;

    /** @var \Firefly\Storage\Piggybank\PiggybankRepositoryInterface */
    protected $_piggybanks;

    /** @var \Firefly\Storage\RecurringTransaction\RecurringTransactionRepositoryInterface */
    protected $_recurring;

    /**
     *
     */
    public function __construct()
    {
        $this->_accounts   = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $this->_repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');
        $this->_budgets    = \App::make('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $this->_categories = \App::make('Firefly\Storage\Category\CategoryRepositoryInterface');
        $this->_journals   = \App::make('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $this->_limits     = \App::make('Firefly\Storage\Limit\LimitRepositoryInterface');
        $this->_piggybanks = \App::make('Firefly\Storage\Piggybank\PiggybankRepositoryInterface');
        $this->_recurring  = \App::make('Firefly\Storage\RecurringTransaction\RecurringTransactionRepositoryInterface');


    }

    /**
     * @param Job   $job
     * @param array $payload
     */
    public function cleanImportAccount(Job $job, array $payload)
    {
        $importAccountType = $this->_accounts->findAccountType('Import account');
        $importAccounts    = $this->_accounts->getByAccountType($importAccountType);
        if (count($importAccounts) == 0) {
            $job->delete();
        } else if (count($importAccounts) == 1) {
            /** @var \Account $importAccount */
            $importAccount = $importAccounts[0];
            $transactions  = $importAccount->transactions()->get();
            /** @var \Transaction $transaction */
            foreach ($transactions as $transaction) {
                $transaction->account()->associate($importAccount);
                $transaction->save();
            }
            \Log::debug('Updated ' . count($transactions) . ' transactions from Import Account to cash.');
        }
        $job->delete();
    }

    /**
     * @param Job   $job
     * @param array $payload
     *
     * @throws \Firefly\Exception\FireflyException
     */
    public function importComponent(Job $job, array $payload)
    {

        \Log::debug('Going to import component "' . $payload['data']['name'] . '".');
        switch ($payload['data']['type']['type']) {
            case 'beneficiary':
                $payload['class']                = 'Account';
                $payload['data']['account_type'] = 'Expense account';
                $this->importAccount($job, $payload);
                break;
            case 'budget':
                $this->importBudget($job, $payload);
                break;
            case 'category':
                $this->importCategory($job, $payload);
                break;
            case 'payer':
                $job->delete();
                break;
            default:
                $job->delete();
                break;
        }

    }

    /**
     * Import a personal account or beneficiary as a new account.
     *
     * @param Job   $job
     * @param array $payload
     */
    public function importAccount(Job $job, array $payload)
    {

        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);


        // maybe we've already imported this account:
        $importEntry = $this->_repository->findImportEntry($importMap, 'Account', intval($payload['data']['id']));

        // if so, delete job and return:
        if (!is_null($importEntry)) {
            $job->delete();
            return;
        }

        // if Firefly tries to import a beneficiary, Firefly will "merge" already existing ones,
        // so we don't care:

        if (isset($payload['data']['account_type']) && $payload['data']['account_type'] == 'Expense account') {
            // unset some data to make firstOrCreate work:
            $oldPayloadId = $payload['data']['id'];
            unset($payload['data']['type_id'], $payload['data']['parent_component_id'],
            $payload['data']['reporting'], $payload['data']['type'], $payload['data']['id'], $payload['data']['account_type']);
            // set other data to make it work:
            $expenseAccountType                 = $this->_accounts->findAccountType('Expense account');
            $payload['data']['account_type_id'] = $expenseAccountType->id;

            $acct = $this->_accounts->firstOrCreate((array)$payload['data']);
            if (is_null($acct)) {
                echo '$acct (1) is null, exit!';
                var_dump($acct);
                exit();
            }
            \Log::debug('Imported ' . $payload['class'] . ' "' . $payload['data']['name'] . '".');
            $this->_repository->store($importMap, 'Account', $oldPayloadId, $acct->id);
            $job->delete();
            return;
        }

        // but Firefly cannot merge other types accounts, so we need to search first:
        $assetAccountType = $this->_accounts->findAccountType('Asset account');

        // we need to find it by name AND type.
        $acct = $this->_accounts->findByNameAndAccountType($payload['data']['name'], $assetAccountType);
        if (is_null($acct)) {
            // store new one!
            $acct = $this->_accounts->store((array)$payload['data']);
            \Log::debug('Imported ' . $payload['class'] . ' "' . $payload['data']['name'] . '".');
            $this->_repository->store($importMap, 'Account', $payload['data']['id'], $acct->id);
        } else {
            // use previous one!
            \Log::debug('Already imported ' . $payload['class'] . ' "' . $payload['data']['name'] . '".');
            $this->_repository->store($importMap, 'Account', $payload['data']['id'], $acct->id);
        }

        // and delete the job
        $job->delete();

    }

    /**
     * @param \User $user
     */
    protected function overruleUser(\User $user)
    {
        $this->_accounts->overruleUser($user);
        $this->_budgets->overruleUser($user);
        $this->_categories->overruleUser($user);
        $this->_journals->overruleUser($user);
        $this->_limits->overruleUser($user);
        $this->_repository->overruleUser($user);
        $this->_piggybanks->overruleUser($user);
        $this->_recurring->overruleUser($user);
    }

    /**
     * Import a budget into Firefly.
     *
     * @param Job   $job
     * @param array $payload
     */
    public function importBudget(Job $job, array $payload)
    {
        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        // maybe we've already imported this budget:
        $bdg = $this->_budgets->findByName($payload['data']['name']);

        if (is_null($bdg)) {
            // we have not!
            $bdg = $this->_budgets->store((array)$payload['data']);
            $this->_repository->store($importMap, 'Budget', $payload['data']['id'], $bdg->id);
            \Log::debug('Imported budget "' . $payload['data']['name'] . '".');
        } else {
            // we have!
            $this->_repository->store($importMap, 'Budget', $payload['data']['id'], $bdg->id);
            \Log::debug('Already had budget "' . $payload['data']['name'] . '".');
        }

        // delete job.
        $job->delete();
    }

    /**
     * Import a category into Firefly.
     *
     * @param Job   $job
     * @param array $payload
     */
    public function importCategory(Job $job, array $payload)
    {

        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        // try to find budget:
        $current = $this->_categories->findByName($payload['data']['name']);
        if (is_null($current)) {
            $cat = $this->_categories->store((array)$payload['data']);
            $this->_repository->store($importMap, 'Category', $payload['data']['id'], $cat->id);
            \Log::debug('Imported category "' . $payload['data']['name'] . '".');
        } else {
            $this->_repository->store($importMap, 'Category', $payload['data']['id'], $current->id);
            \Log::debug('Already had category "' . $payload['data']['name'] . '".');
        }

        $job->delete();
    }

    /**
     * @param Job   $job
     * @param array $payload
     */
    public function importComponentTransaction(Job $job, array $payload)
    {
        if ($job->attempts() > 1) {
            \Log::info('importComponentTransaction Job running for ' . $job->attempts() . 'th time!');
        }
        if ($job->attempts() > 30) {
            \Log::error('importComponentTransaction Job running for ' . $job->attempts() . 'th time, so KILL!');
            $job->delete();
            return;
        }

        $oldComponentId   = intval($payload['data']['component_id']);
        $oldTransactionId = intval($payload['data']['transaction_id']);

        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        $oldTransactionMap = $this->_repository->findImportEntry($importMap, 'Transaction', $oldTransactionId);

        // we don't know what the component is, so we need to search for it in a set
        // of possible types (Account / Beneficiary, Budget, Category)
        /** @var \Importentry $oldComponentMap */
        $oldComponentMap = $this->_repository->findImportComponentMap($importMap, $oldComponentId);

        if (is_null($oldComponentMap)) {
            \Log::debug('importComponentTransaction Could not run this one, waiting for five minutes...');
            $job->release(300);
            return;
        }

        $journal = $this->_journals->find($oldTransactionMap->new);
        \Log::debug('Going to update ' . $journal->description);

        // find the cash account:


        switch ($oldComponentMap->class) {
            case 'Budget':
                // budget thing link:
                $budget = $this->_budgets->find($oldComponentMap->new);

                \Log::debug('Updating transactions budget.');
                $journal->budgets()->save($budget);
                $journal->save();
                \Log::debug('Updated transactions budget.');

                break;
            case 'Category':
                $category = $this->_categories->find($oldComponentMap->new);
                $journal  = $this->_journals->find($oldTransactionMap->new);
                \Log::info('Updating transactions category (old id is #' . $oldComponentMap->old . ').');
                if (!is_null($category)) {
                    $journal->categories()->save($category);
                    $journal->save();
                    \Log::info('Updated transactions category.');
                } else {
                    \Log::error('No category mapping to old id #' . $oldComponentMap->old . ' found. Release for 5m!');
                    $job->release(300);
                    return;
                }
                break;
            case 'Account':
                \Log::info('Updating transactions Account.');
                $account = $this->_accounts->find($oldComponentMap->new);
                $journal = $this->_journals->find($oldTransactionMap->new);
                if (is_null($account)) {
                    \Log::debug('Cash account is needed.');
                    $account = $this->_accounts->getCashAccount();
                }
                // find

                foreach ($journal->transactions as $transaction) {
                    $accountType = $transaction->account->accounttype->type;
                    if ($accountType == 'Import account') {
                        $transaction->account()->associate($account);
                        $transaction->save();
                        \Log::debug(
                            'Updated transactions (#' . $journal->id . '), #' . $transaction->id . '\'s Account.'
                        );
                    } else {
                        \Log::error('Found account type: "' . $accountType . '" instead of expected "Import account"');
                    }
                }

                break;
        }
        $job->delete();


    }

    /**
     * @param Job   $job
     * @param array $payload
     */
    public function importLimit(Job $job, array $payload)
    {
        if ($job->attempts() > 30) {
            \Log::error('importLimit Job running for ' . $job->attempts() . 'th time, so KILL!');
            $job->delete();
            return;
        }

        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);


        // find the budget this limit is part of:
        $importEntry = $this->_repository->findImportEntry(
            $importMap, 'Budget',
            intval($payload['data']['component_id'])
        );

        // budget is not yet imported:
        if (is_null($importEntry)) {
            \Log::debug(
                'importLimit Cannot import limit #' . $payload['data']['id'] .
                ' because the budget is not here yet. #' . $job->attempts()
            );
            $job->release(300);
            return;
        }
        // find similar limit:
        \Log::debug('Trying to find budget with ID #' . $importEntry->new . ', based on entry #' . $importEntry->id);
        $budget = $this->_budgets->find($importEntry->new);
        if (!is_null($budget)) {
            $current = $this->_limits->findByBudgetAndDate($budget, new Carbon($payload['data']['date']));
            if (is_null($current)) {
                // create it!
                $payload['data']['budget_id'] = $budget->id;
                $payload['data']['startdate'] = $payload['data']['date'];
                $payload['data']['period']    = 'monthly';
                $lim                          = $this->_limits->store((array)$payload['data']);
                $this->_repository->store($importMap, 'Limit', $payload['data']['id'], $lim->id);
                \Event::fire('limits.store', [$lim]);
                \Log::debug('Imported ' . $payload['class'] . ', for ' . $budget->name . ' (' . $lim->startdate . ').');
            } else {
                // already has!
                $this->_repository->store($importMap, 'Budget', $payload['data']['id'], $current->id);
                \Log::debug(
                    'Already had ' . $payload['class'] . ', for ' . $budget->name . ' (' . $current->startdate . ').'
                );
            }
        } else {
            // cannot import component limit, no longer supported.
            \Log::error('Cannot import limit for other than budget!');
        }
        $job->delete();
    }

    /**
     * @param Job   $job
     * @param array $payload
     */
    public function importPiggybank(Job $job, array $payload)
    {
        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

//         try to find related piggybank:
        $current = $this->_piggybanks->findByName($payload['data']['name']);

        // we need an account to go with this piggy bank:
        $set = $this->_accounts->getActiveDefault();
        if (count($set) > 0) {
            $account                       = $set[0];
            $payload['data']['account_id'] = $account->id;
        } else {
            \Log::debug('Released job for work in five minutes...');
            $job->release(300);
            return;
        }

        if (is_null($current)) {
            $payload['data']['targetamount']  = floatval($payload['data']['target']);
            $payload['data']['repeats']       = 0;
            $payload['data']['rep_every']     = 1;
            $payload['data']['reminder_skip'] = 1;
            $payload['data']['rep_times']     = 1;
            $piggy                            = $this->_piggybanks->store((array)$payload['data']);
            $this->_repository->store($importMap, 'Piggybank', $payload['data']['id'], $piggy->id);
            \Log::debug('Imported ' . $payload['class'] . ' "' . $payload['data']['name'] . '".');
            \Event::fire('piggybanks.store', [$piggy]);
        } else {
            $this->_repository->store($importMap, 'Piggybank', $payload['data']['id'], $current->id);
            \Log::debug('Already had ' . $payload['class'] . ' "' . $payload['data']['name'] . '".');
        }
        $job->delete();
    }

    /**
     * @param Job   $job
     * @param array $payload
     */
    public function importPredictable(Job $job, array $payload)
    {
        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);


        // try to find related recurring transaction:
        $current = $this->_recurring->findByName($payload['data']['description']);
        if (is_null($current)) {

            $payload['data']['name']        = $payload['data']['description'];
            $payload['data']['match']       = join(',', explode(' ', $payload['data']['description']));
            $pct                            = intval($payload['data']['pct']);
            $payload['data']['amount_min']  = floatval($payload['data']['amount']) * ($pct / 100) * -1;
            $payload['data']['amount_max']  = floatval($payload['data']['amount']) * (1 + ($pct / 100)) * -1;
            $payload['data']['date']        = date('Y-m-') . $payload['data']['dom'];
            $payload['data']['repeat_freq'] = 'monthly';
            $payload['data']['active']      = intval($payload['data']['inactive']) == 1 ? 0 : 1;
            $payload['data']['automatch']   = 1;

            $recur = $this->_recurring->store((array)$payload['data']);

            $this->_repository->store($importMap, 'RecurringTransaction', $payload['data']['id'], $recur->id);
            \Log::debug('Imported ' . $payload['class'] . ' "' . $payload['data']['name'] . '".');
        } else {
            $this->_repository->store($importMap, 'RecurringTransaction', $payload['data']['id'], $current->id);
            \Log::debug('Already had ' . $payload['class'] . ' "' . $payload['data']['description'] . '".');
        }
        $job->delete();

    }

    /**
     * @param Job   $job
     * @param array $payload
     */
    public function importSetting(Job $job, array $payload)
    {
        switch ($payload['data']['name']) {
            default:
                $job->delete();
                return;
                break;
            case 'piggyAccount':
                // if we have this account, update all piggy banks:
                $accountID = intval($payload['data']['value']);

                /** @var \Importmap $importMap */
                $importMap = $this->_repository->findImportmap($payload['mapID']);
                $user      = $importMap->user;
                $this->overruleUser($user);


                $importEntry = $this->_repository->findImportEntry($importMap, 'Account', $accountID);
                if ($importEntry) {

                    $all     = $this->_piggybanks->get();
                    $account = $this->_accounts->find($importEntry->new);

                    \Log::debug('Updating all piggybanks, found the right setting.');
                    foreach ($all as $piggy) {
                        $piggy->account()->associate($account);
                        unset($piggy->leftInAccount); //??
                        $piggy->save();
                    }
                } else {
                    \Log::debug('importSetting wait five minutes and try again...');
                    $job->release(300);
                }
                break;
        }
        $job->delete();

    }

    /**
     * @param Job   $job
     * @param array $payload
     */
    public function importTransaction(Job $job, array $payload)
    {

        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        // find or create the account type for the import account.
        // find or create the account for the import account.
        $accountType   = $this->_accounts->findAccountType('Import account');
        $importAccount = $this->_accounts->firstOrCreate(
            [
                'account_type_id' => $accountType->id,
                'name'            => 'Import account',
                'user_id'         => $user->id,
                'active'          => 1,
            ]
        );

        // if amount is more than zero, move from $importAccount
        $amount = floatval($payload['data']['amount']);

        $accountEntry    = $this->_repository->findImportEntry(
            $importMap, 'Account',
            intval($payload['data']['account_id'])
        );
        $personalAccount = $this->_accounts->find($accountEntry->new);

        if ($amount < 0) {
            // if amount is less than zero, move to $importAccount
            $accountFrom = $personalAccount;
            $accountTo   = $importAccount;
        } else {
            $accountFrom = $importAccount;
            $accountTo   = $personalAccount;
        }
        $amount = $amount < 0 ? $amount * -1 : $amount;
        $date   = new Carbon($payload['data']['date']);

        // find a journal?
        $current = $this->_repository->findImportEntry($importMap, 'Transaction', intval($payload['data']['id']));


        if (is_null($current)) {

            $journal = $this->_journals->createSimpleJournal(
                $accountFrom, $accountTo,
                $payload['data']['description'], $amount, $date
            );
            $this->_repository->store($importMap, 'Transaction', $payload['data']['id'], $journal->id);
            \Log::debug(
                'Imported transaction "' . $payload['data']['description'] . '" (' . $journal->date->format('Y-m-d')
                . ').'
            );
        } else {
            // do nothing.
            \Log::debug('ALREADY imported transaction "' . $payload['data']['description'] . '".');
        }

        $job->delete();

    }

    /**
     * @param Job   $job
     * @param array $payload
     */
    public function importTransfer(Job $job, array $payload)
    {


        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        // from account:
        $oldFromAccountID    = intval($payload['data']['accountfrom_id']);
        $oldFromAccountEntry = $this->_repository->findImportEntry($importMap, 'Account', $oldFromAccountID);
        $accountFrom         = $this->_accounts->find($oldFromAccountEntry->new);

        // to account:
        $oldToAccountID    = intval($payload['data']['accountto_id']);
        $oldToAccountEntry = $this->_repository->findImportEntry($importMap, 'Account', $oldToAccountID);
        $accountTo         = $this->_accounts->find($oldToAccountEntry->new);
        if (!is_null($accountFrom) && !is_null($accountTo)) {
            $amount  = floatval($payload['data']['amount']);
            $date    = new Carbon($payload['data']['date']);
            $journal = $this->_journals->createSimpleJournal(
                $accountFrom, $accountTo, $payload['data']['description'],
                $amount, $date
            );
            \Log::debug('Imported transfer "' . $payload['data']['description'] . '".');
            $job->delete();
        } else {
            $job->release(5);
        }

    }

    /**
     * @param Job $job
     * @param     $payload
     */
    public function start(Job $job, array $payload)
    {
        \Log::debug('Start with job "start"');
        $user     = \User::find($payload['user']);
        $filename = $payload['file'];
        if (file_exists($filename)) {
            // we are able to process the file!

            // make an import map. Which is some kind of object because we use queues.
            $importMap = new \Importmap;
            $importMap->user()->associate($user);
            $importMap->file = $filename;
            $importMap->save();

            // we can now launch a billion jobs importing every little thing into Firefly III
            $raw  = file_get_contents($filename);
            $JSON = json_decode($raw);

            $classes = ['accounts', 'components', 'limits', 'piggybanks',
                        'predictables', 'settings', 'transactions', 'transfers'];

            foreach ($classes as $classes_plural) {
                $class = ucfirst(\Str::singular($classes_plural));
                \Log::debug('Create job to import all ' . $classes_plural);
                foreach ($JSON->$classes_plural as $entry) {
                    \Log::debug('Create job to import single ' . $class);
                    $fn          = 'import' . $class;
                    $jobFunction = 'Firefly\Queue\Import@' . $fn;
                    \Queue::push($jobFunction, ['data' => $entry, 'class' => $class, 'mapID' => $importMap->id]);

                }
            }

            // , components, limits, piggybanks, predictables, settings, transactions, transfers
            // component_predictables, component_transactions, component_transfers

            $count = count($JSON->component_transaction);
            foreach ($JSON->component_transaction as $index => $entry) {
                \Log::debug('Create job to import components_transaction! Yay! (' . $index . '/' . $count . ') ');
                $fn          = 'importComponentTransaction';
                $jobFunction = 'Firefly\Queue\Import@' . $fn;
                \Queue::push($jobFunction, ['data' => $entry, 'mapID' => $importMap->id]);
            }

            // queue a job to clean up the "import account", it should properly fall back
            // to the cash account (which it doesn't always do for some reason).
            \Queue::push('Firefly\Queue\Import@cleanImportAccount', ['mapID' => $importMap->id]);

        }


        \Log::debug('Done with job "start"');
        // this is it, close the job:
        $job->delete();
    }
} 