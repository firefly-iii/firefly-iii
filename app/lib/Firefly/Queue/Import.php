<?php

namespace Firefly\Queue;

use Carbon\Carbon;
use Firefly\Exception\FireflyException;
use Illuminate\Queue\Jobs\SyncJob;

/**
 * Class Import
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

    /**
     *
     */
    public function __construct()
    {
        $this->_accounts   = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $this->_repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');
        $this->_budgets    = \App::make('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $this->_categories = \App::make('Firefly\Storage\Category\CategoryRepositoryInterface');
    }

    /**
     * Import a personal account or beneficiary as a new account.
     *
     * @param SyncJob $job
     * @param array $payload
     */
    public function importAccount(SyncJob $job, array $payload)
    {

        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);

        // maybe we've already imported this account:
        $importEntry = $this->_repository->findImportEntry($importMap, 'Account', intval($payload['data']['id']));

        // if so, delete job and return:
        if (!is_null($importEntry)) {
            $job->delete();
            return;
        }

        // if we try to import a beneficiary, Firefly will "merge" already,
        // so we don't care:
        if (isset($payload['data']['account_type']) && $payload['data']['account_type'] == 'Beneficiary account') {
            // store beneficiary
            $acct = $this->_accounts->createOrFindBeneficiary($payload['data']['name']);
            \Log::debug('Imported ' . $payload['class'] . ' "' . $payload['data']['name'] . '".');
            $this->_repository->store($importMap, 'Account', $payload['data']['id'], $acct->id);
            $job->delete();
            return;
        }

        // but we cannot merge accounts, so we need to search first:
        $acct = $this->_accounts->findByName($payload['data']['name']);
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
     * Import a budget into Firefly.
     *
     * @param SyncJob $job
     * @param array $payload
     */
    public function importBudget(SyncJob $job, array $payload)
    {
        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);

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
     * @param SyncJob $job
     * @param array $payload
     */
    public function importCategory(SyncJob $job, array $payload)
    {

        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);

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
     * @param SyncJob $job
     * @param array $payload
     * @throws \Firefly\Exception\FireflyException
     */
    public function importComponent(SyncJob $job, array $payload)
    {

        \Log::debug('Going to import component "' . $payload['data']['name'] . '".');
        switch ($payload['data']['type']['type']) {
            case 'beneficiary':
                $jobFunction                     = 'Firefly\Queue\Import@importAccount';
                $payload['class']                = 'Account';
                $payload['data']['account_type'] = 'Beneficiary account';
                \Log::debug('It is a beneficiary.');
                break;
            case 'budget':
                $jobFunction = 'Firefly\Queue\Import@importBudget';
                \Log::debug('It is a budget.');
                break;
            case 'category':
                $jobFunction = 'Firefly\Queue\Import@importCategory';
                \Log::debug('It is a category.');
                break;
            case 'payer':
                // ignored!
                break;
            default:
                throw new FireflyException('No import case for "' . $payload['data']['type']['type'] . '"!');
        }
        if (isset($jobFunction)) {
            \Queue::push($jobFunction, $payload);
        }
        $job->delete();
    }

    /**
     * @param SyncJob $job
     * @param array $payload
     */
    public function importComponentTransaction(SyncJob $job, array $payload)
    {
        if ($job->attempts() > 1) {
            \Log::info('Job running for ' . $job->attempts() . 'th time!');
        }

        $oldComponentId   = intval($payload['data']['component_id']);
        $oldTransactionId = intval($payload['data']['transaction_id']);

        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');


        /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface $journals */
        $journals = \App::make('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');

        /** @var \Firefly\Storage\Category\CategoryRepositoryInterface $categories */
        $categories = \App::make('Firefly\Storage\Category\CategoryRepositoryInterface');

        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');


        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);

        $oldTransactionMap = $repository->findImportEntry($importMap, 'Transaction', $oldTransactionId);

        // we don't know what the component is, so we need to search for it in a set
        // of possible types (Account / Beneficiary, Budget, Category)
        /** @var \Importentry $oldComponentMap */
        $oldComponentMap = $repository->findImportComponentMap($importMap, $oldComponentId);

        if (is_null($oldComponentMap)) {
            \Log::debug('Could not run this one, waiting for five seconds...');
            $job->release(5);
            return;
        }

        $journal = $journals->find($oldTransactionMap->new);
        \Log::debug('Going to update ' . $journal->description);

        // find the cash account:


        switch ($oldComponentMap->class) {
            case 'Budget':
                // budget thing link:
                $budget = $this->_budgets->find($oldComponentMap->new);

                \Log::debug('Updating transactions Budget.');
                $journal->budgets()->save($budget);
                $journal->save();
                \Log::debug('Updated transactions Budget.');

                break;
            case 'Category':
                $category = $categories->find($oldComponentMap->new);
                $journal  = $journals->find($oldTransactionMap->new);
                \Log::info('Updating transactions Category.');
                $journal->categories()->save($category);
                $journal->save();
                \Log::info('Updated transactions Category.');
                break;
            case 'Account':
                \Log::info('Updating transactions Account.');
                $account = $accounts->find($oldComponentMap->new);
                $journal = $journals->find($oldTransactionMap->new);
                if (is_null($account)) {
                    \Log::debug('Cash account is needed.');
                    $account = $accounts->getCashAccount();
                    \Log::info($account);
                }

                foreach ($journal->transactions as $transaction) {
                    if ($transaction->account()->first()->account_type_id == 5) {
                        $transaction->account()->associate($account);
                        $transaction->save();
                        \Log::debug('Updated transactions (#' . $journal->id . '), #' . $transaction->id . '\'s Account.');
                    }
                }

                break;
        }


    }

    /**
     * @param SyncJob $job
     * @param array $payload
     */
    public function importLimit(SyncJob $job, array $payload)
    {

        /** @var \Firefly\Storage\Limit\LimitRepositoryInterface $limits */
        $limits = \App::make('Firefly\Storage\Limit\LimitRepositoryInterface');

        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Firefly\Storage\Budget\BudgetRepositoryInterface $budgets */
        $budgets = \App::make('Firefly\Storage\Budget\BudgetRepositoryInterface');

        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);


        // find the budget this limit is part of:
        $importEntry = $repository->findImportEntry($importMap, 'Budget', intval($payload['data']['component_id']));

        // budget is not yet imported:
        if (is_null($importEntry)) {
            \Log::debug('Released job for work in five seconds...');
            $job->release(5);
            return;
        }
        // find similar limit:
        \Log::debug('Trying to find budget with ID #' . $importEntry->new . ', based on entry #' . $importEntry->id);
        $budget = $budgets->find($importEntry->new);
        if (!is_null($budget)) {
            $current = $limits->findByBudgetAndDate($budget, new Carbon($payload['data']['date']));
            if (is_null($current)) {
                // create it!
                $payload['data']['budget_id'] = $budget->id;
                $payload['data']['startdate'] = $payload['data']['date'];
                $payload['data']['period']    = 'monthly';
                $lim                          = $limits->store((array)$payload['data']);
                $repository->store($importMap, 'Limit', $payload['data']['id'], $lim->id);
                \Event::fire('limits.store', [$lim]);
                \Log::debug('Imported ' . $payload['class'] . ', for ' . $budget->name . ' (' . $lim->startdate . ').');
            } else {
                // already has!
                $repository->store($importMap, 'Budget', $payload['data']['id'], $current->id);
                \Log::debug('Already had ' . $payload['class'] . ', for ' . $budget->name . ' (' . $current->startdate . ').');
            }
        } else {
            // cannot import component limit, no longer supported.
            \Log::error('Cannot import limit for other than budget!');
        }
        $job->delete();
    }

    /**
     * @param SyncJob $job
     * @param array $payload
     */
    public function importPiggybank(SyncJob $job, array $payload)
    {
        /** @var \Firefly\Storage\Piggybank\PiggybankRepositoryInterface $piggybanks */
        $piggybanks = \App::make('Firefly\Storage\Piggybank\PiggybankRepositoryInterface');

        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);

        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');

        // try to find related piggybank:
        $current = $piggybanks->findByName($payload['data']['name']);

        // we need an account to go with this piggy bank:
        $set = $accounts->getActiveDefault();
        if (count($set) > 0) {
            $account                       = $set[0];
            $payload['data']['account_id'] = $account->id;
        } else {
            \Log::debug('Released job for work in five seconds...');
            $job->release(5);
            return;
        }

        if (is_null($current)) {
            $payload['data']['targetamount']  = floatval($payload['data']['target']);
            $payload['data']['repeats']       = 0;
            $payload['data']['rep_every']     = 1;
            $payload['data']['reminder_skip'] = 1;
            $payload['data']['rep_times']     = 1;
            $piggy                            = $piggybanks->store((array)$payload['data']);
            $repository->store($importMap, 'Piggybank', $payload['data']['id'], $piggy->id);
            \Log::debug('Imported ' . $payload['class'] . ' "' . $payload['data']['name'] . '".');
            \Event::fire('piggybanks.store', [$piggy]);
        } else {
            $repository->store($importMap, 'Piggybank', $payload['data']['id'], $current->id);
            \Log::debug('Already had ' . $payload['class'] . ' "' . $payload['data']['name'] . '".');
        }
        $job->delete();
    }

    /**
     * @param SyncJob $job
     * @param array $payload
     */
    public function importPredictable(SyncJob $job, array $payload)
    {
        /** @var \Firefly\Storage\RecurringTransaction\RecurringTransactionRepositoryInterface $piggybanks */
        $recurring = \App::make('Firefly\Storage\RecurringTransaction\RecurringTransactionRepositoryInterface');

        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);


        // try to find related recurring transaction:
        $current = $recurring->findByName($payload['data']['description']);
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

            $recur = $recurring->store((array)$payload['data']);

            $repository->store($importMap, 'RecurringTransaction', $payload['data']['id'], $recur->id);
            \Log::debug('Imported ' . $payload['class'] . ' "' . $payload['data']['name'] . '".');
        } else {
            $repository->store($importMap, 'RecurringTransaction', $payload['data']['id'], $current->id);
            \Log::debug('Already had ' . $payload['class'] . ' "' . $payload['data']['description'] . '".');
        }
        $job->delete();

    }

    /**
     * @param SyncJob $job
     * @param array $payload
     */
    public function importSetting(SyncJob $job, array $payload)
    {
        switch ($payload['data']['name']) {
            default:
                $job->delete();
                return;
                break;
            case 'piggyAccount':
                // if we have this account, update all piggy banks:
                $accountID = intval($payload['data']['value']);

                /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
                $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

                /** @var \Importmap $importMap */
                $importMap = $repository->findImportmap($payload['mapID']);


                $importEntry = $repository->findImportEntry($importMap, 'Account', $accountID);
                if ($importEntry) {
                    /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
                    $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');

                    /** @var \Firefly\Storage\Piggybank\PiggybankRepositoryInterface $piggybanks */
                    $piggybanks = \App::make('Firefly\Storage\Piggybank\PiggybankRepositoryInterface');

                    $all     = $piggybanks->get();
                    $account = $accounts->find($importEntry->new);

                    \Log::debug('Updating all piggybanks, found the right setting.');
                    foreach ($all as $piggy) {
                        $piggy->account()->associate($account);
                        unset($piggy->leftInAccount); //??
                        $piggy->save();
                    }
                } else {
                    $job->release(5);
                }
                break;
        }
        $job->delete();

    }

    /**
     * @param SyncJob $job
     * @param array $payload
     */
    public function importTransaction(SyncJob $job, array $payload)
    {
        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');

        /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface $journals */
        $journals = \App::make('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');


        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);

        // find or create the account type for the import account.
        // find or create the account for the import account.
        $accountType   = $accounts->findAccountType('Import account');
        $importAccount = $accounts->createOrFind('Import account', $accountType);


        // if amount is more than zero, move from $importAccount
        $amount = floatval($payload['data']['amount']);

        $accountEntry    = $repository->findImportEntry($importMap, 'Account', intval($payload['data']['account_id']));
        $personalAccount = $accounts->find($accountEntry->new);

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
        $current = $repository->findImportEntry($importMap, 'Transaction', intval($payload['data']['id']));


        if (is_null($current)) {
            $journal = $journals->createSimpleJournal($accountFrom, $accountTo,
                $payload['data']['description'], $amount, $date);
            $repository->store($importMap, 'Transaction', $payload['data']['id'], $journal->id);
            \Log::debug('Imported transaction "' . $payload['data']['description'] . '" (' . $journal->date->format('Y-m-d') . ').');
        } else {
            // do nothing.
            \Log::debug('ALREADY imported transaction "' . $payload['data']['description'] . '".');
        }


    }

    /**
     * @param SyncJob $job
     * @param array $payload
     */
    public function importTransfer(SyncJob $job, array $payload)
    {
        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');

        /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface $journals */
        $journals = \App::make('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');


        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);

        // from account:
        $oldFromAccountID    = intval($payload['data']['accountfrom_id']);
        $oldFromAccountEntry = $repository->findImportEntry($importMap, 'Account', $oldFromAccountID);
        $accountFrom         = $accounts->find($oldFromAccountEntry->new);

        // to account:
        $oldToAccountID    = intval($payload['data']['accountto_id']);
        $oldToAccountEntry = $repository->findImportEntry($importMap, 'Account', $oldToAccountID);
        $accountTo         = $accounts->find($oldToAccountEntry->new);
        if (!is_null($accountFrom) && !is_null($accountTo)) {
            $amount  = floatval($payload['data']['amount']);
            $date    = new Carbon($payload['data']['date']);
            $journal = $journals->createSimpleJournal($accountFrom, $accountTo, $payload['data']['description'],
                $amount, $date);
            \Log::debug('Imported transfer "' . $payload['data']['description'] . '".');
            $job->delete();
        } else {
            $job->release(5);
        }

    }

    /**
     * @param SyncJob $job
     * @param $payload
     */
    public function start(SyncJob $job, array $payload)
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
                    //\Log::debug('Create job to import single ' . $class);
                    $fn          = 'import' . $class;
                    $jobFunction = 'Firefly\Queue\Import@' . $fn;
                    \Queue::push($jobFunction, ['data' => $entry, 'class' => $class, 'mapID' => $importMap->id]);
                }
            }

            // accounts, components, limits, piggybanks, predictables, settings, transactions, transfers
            // component_predictables, component_transactions, component_transfers

            $count = count($JSON->component_transaction);
            foreach ($JSON->component_transaction as $index => $entry) {
                //\Log::debug('Create job to import components_transaction! Yay! (' . $index . '/' . $count . ') ');
                $fn          = 'importComponentTransaction';
                $jobFunction = 'Firefly\Queue\Import@' . $fn;
                \Queue::push($jobFunction, ['data' => $entry, 'mapID' => $importMap->id]);
            }


        }


        echo 'Done';
        \Log::debug('Done with job "start"');
        exit;

        // this is it, close the job:
        $job->delete();
    }
} 