<?php

namespace Firefly\Queue;

use Illuminate\Queue\Jobs\Job;

/**
 * Class Import
 *
 * @package Firefly\Queue
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Import
{
    /** @var \Firefly\Storage\Account\AccountRepositoryInterface */
    protected $_accounts;

    /** @var \Firefly\Storage\Import\ImportRepositoryInterface */
    protected $_repository;

    /** @var \Firefly\Storage\Piggybank\PiggybankRepositoryInterface */
    protected $_piggybanks;

    /**
     * This constructs the import handler and initiates all the relevant interfaces / classes.
     */
    public function __construct()
    {
        $this->_accounts   = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $this->_repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');
        $this->_piggybanks = \App::make('Firefly\Storage\Piggybank\PiggybankRepositoryInterface');


    }

    /**
     * The final step in the import routine is to get all transactions which have one of their accounts
     * still set to "import", which means it is a cash transaction. This routine will set them all to cash instead.
     *
     * If there was no account present for these accounts in the import routine (no beneficiary set), Firefly
     * II would fall back to the import account.
     *
     * @param Job   $job
     * @param array $payload
     */
    public function cleanImportAccount(Job $job, array $payload)
    {

        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        // two import account types.
        $importAccountType = $this->_accounts->findAccountType('Import account');
        $cashAccountType   = $this->_accounts->findAccountType('Cash account');

        // find or create import account:
        $importAccount = $this->_accounts->firstOrCreate(
            [
                'name'            => 'Import account',
                'account_type_id' => $importAccountType->id,
                'active'          => 1,
                'user_id'         => $user->id,
            ]
        );

        // find or create cash account:
        $cashAccount = $this->_accounts->firstOrCreate(
            [
                'name'            => 'Cash account',
                'account_type_id' => $cashAccountType->id,
                'active'          => 1,
                'user_id'         => $user->id,
            ]
        );

        // update all users transactions:
        $count = \DB::table('transactions')
            ->where('account_id', $importAccount->id)->count();

        \DB::table('transactions')
            ->where('account_id', $importAccount->id)
            ->update(['account_id' => $cashAccount->id]);

        \Log::debug('Updated ' . $count . ' transactions from Import Account to cash.');
        $job->delete(); // no count fix
    }

    /**
     * @param \User $user
     */
    protected function overruleUser(\User $user)
    {
        $this->_accounts->overruleUser($user);
        $this->_repository->overruleUser($user);
        $this->_piggybanks->overruleUser($user);
    }

    /**
     * This job queues new jobs that will connect components to their proper transactions and updates the
     * expense account: categories, budgets an beneficiaries used to be components.
     *
     * @param Job   $job
     * @param array $payload
     */
    public function importComponentTransaction(Job $job, array $payload)
    {
        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        /*
         * Took too long to fix this:
         */
        if ($job->attempts() > 10) {
            \Log::error('Could not map transaction to component after 10 tries. KILL');
            $importMap->jobsdone++;
            $importMap->save();
            $job->delete(); // count fixed
            return;
        }


        /*
         * Prep some vars from the payload
         */
        $transactionId = intval($payload['data']['transaction_id']);
        $componentId   = intval($payload['data']['component_id']);

        /*
         * We don't know what kind of component we have. So we search for it. We have a specific function
         * for this:
         */
        $oldComponentMap = $this->_repository->findImportComponentMap($importMap, $componentId);

        /*
         * If the map is null, the component (whatever it is) is not imported yet, and we release the job.
         */
        if (is_null($oldComponentMap)) {
            \Log::notice('No map for this component, release transaction/component import.');

            /*
             * When in sync, its pointless to release jobs. Simply remove them.
             */
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
         * Switch on the class found in the map, and push a new job to update the transaction journal:
         */
        switch ($oldComponentMap->class) {
            default:
                \Log::error('Cannot handle "' . $oldComponentMap->class . '" in component<>transaction routine!');
                $job->delete();
                break;
            case 'Budget':
                \Log::debug('Push job to connect budget to transaction #' . $transactionId);
                \Queue::push( // count fixed
                    'Firefly\Storage\Budget\BudgetRepositoryInterface@importUpdateTransaction', $payload
                );
                $importMap->totaljobs++;
                $importMap->jobsdone++;
                $importMap->save();

                $job->delete(); // count fixed
                break;
            case 'Category':
                \Log::debug('Push job to connect category to transaction #' . $transactionId);
                \Queue::push( // count fixed
                    'Firefly\Storage\Category\CategoryRepositoryInterface@importUpdateTransaction', $payload
                );

                $importMap->totaljobs++;
                $importMap->jobsdone++;
                $importMap->save();

                $job->delete(); // count fixed
                break;
            case 'Account':
                \Log::debug('Push job to connect account to transaction #' . $transactionId);
                \Queue::push( // count fixed
                    'Firefly\Storage\Account\AccountRepositoryInterface@importUpdateTransaction', $payload
                );

                $importMap->totaljobs++;
                $importMap->jobsdone++;
                $importMap->save();

                $job->delete(); // count fixed
                break;
        }
        return;


    }

    /**
     * This job queues new jobs that will connect components to their proper transfers and updates the
     * expense account: categories, budgets an beneficiaries used to be components. Even though not all
     * of the transfers used to have these components, we check for them all.
     *
     * @param Job   $job
     * @param array $payload
     */
    public function importComponentTransfer(Job $job, array $payload)
    {
        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        /*
         * Took too long to fix this:
         */
        if ($job->attempts() > 10) {
            \Log::error('Could not map transaction to component after 10 tries. KILL');
            $importMap->jobsdone++;
            $importMap->save();
            $job->delete(); // count fixed
            return;
        }

        /*
         * Prep some vars from the payload
         */
        $transferId  = intval($payload['data']['transfer_id']);
        $componentId = intval($payload['data']['component_id']);

        /*
         * We don't know what kind of component we have. So we search for it. We have a specific function
         * for this:
         */
        $oldComponentMap = $this->_repository->findImportComponentMap($importMap, $componentId);

        /*
         * If the map is null, the component (whatever it is) is not imported yet, and we release the job.
         */
        if (is_null($oldComponentMap)) {
            \Log::notice('No map for this component, release transfer/component import.');
            /*
             * When in sync, its pointless to release jobs. Simply remove them.
            */
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
         * Switch on the class found in the map, and push a new job to update the transaction journal:
         */
        switch ($oldComponentMap->class) {
            default:
                \Log::error('Cannot handle "' . $oldComponentMap->class . '" in component<>transfer routine!');
                $job->delete();
                break;
            case 'Category':
                \Log::debug('Push job to connect category to transfer #' . $transferId);
                \Queue::push( // count fixed
                    'Firefly\Storage\Category\CategoryRepositoryInterface@importUpdateTransfer', $payload
                );

                $importMap->totaljobs++;
                $importMap->jobsdone++;
                $importMap->save();

                $job->delete(); // count fixed
                break;
            case 'Budget':
                \Log::debug('Push job to connect budget to transfer #' . $transferId);
                \Queue::push( // count fixed
                    'Firefly\Storage\Budget\BudgetRepositoryInterface@importUpdateTransfer', $payload
                );
                $importMap->totaljobs++;
                $importMap->jobsdone++;
                $importMap->save();

                $job->delete(); // count fixed
                break;
        }


    }

    /**
     * This job will see if the particular setting is a 'piggyAccount' setting,
     * one we need to fix all imported piggy banks.
     *
     * @param Job   $job
     * @param array $payload
     */
    public function importSetting(Job $job, array $payload)
    {
        /** @var \Importmap $importMap */
        $importMap = $this->_repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        if ($job->attempts() > 10) {
            \Log::error('No account found for piggyAccount setting after 10 tries. KILL!');

            $importMap->jobsdone++;
            $importMap->save();

            $job->delete(); // count fixed
            return;
        }
        $name = $payload['data']['name'];
        switch ($name) {
            default:
                $importMap->jobsdone++;
                $importMap->save();
                $job->delete(); // count fixed.
                return;
                break;
            case 'piggyAccount':

                /*
                 * If user has this account, update all piggy banks:
                 */
                $accountID = intval($payload['data']['value']);

                /*
                 * Is account imported already?
                 */
                $importEntry = $this->_repository->findImportEntry($importMap, 'Account', $accountID);

                /*
                 * We imported this account already.
                 */
                if ($importEntry) {
                    $all     = $this->_piggybanks->get();
                    $account = $this->_accounts->find($importEntry->new);
                    /*
                     * Update all piggy banks.
                     */
                    if (!is_null($account)) {
                        \Log::debug('Updating all piggybanks, found the right setting.');
                        foreach ($all as $piggy) {
                            $piggy->account()->associate($account);
                            unset($piggy->leftInAccount);
                            $piggy->save();
                        }
                    }
                } else {
                    \Log::notice('Account not yet imported, hold or 5 minutes.');
                    /*
                     * When in sync, its pointless to release jobs. Simply remove them.
                    */
                    if (\Config::get('queue.default') == 'sync') {
                        $importMap->jobsdone++;
                        $importMap->save();
                        $job->delete(); // count fixed
                    } else {
                        $job->release(300); // proper release.
                    }
                }
                break;
        }

        // update map:
        $importMap->jobsdone++;
        $importMap->save();

        $job->delete(); // count fixed.

    }

    /**
     * This job will loop and queue jobs for the import file; almost every set of records will be imported.
     *
     * @param Job $job
     * @param     $payload
     *
     * @SuppressWarnings(PHPMD.CamelCasePropertyName)
     */
    public function start(Job $job, array $payload)
    {
        \Log::debug('Start with job "start"');
        $user     = \User::find($payload['user']);
        $filename = $payload['file'];
        if (file_exists($filename) && !is_null($user)) {
            /*
             * Make an import map. Need it to refer back to import.
             */
            $importMap = new \Importmap;
            $importMap->user()->associate($user);
            $importMap->file      = $filename;
            $importMap->totaljobs = 0;
            $importMap->jobsdone  = 0;
            $importMap->save();

            $totalJobs = 0;

            /*
             * Loop over all data in the JSON file, then create jobs.
             */
            $raw  = file_get_contents($filename);
            $JSON = json_decode($raw);

            // first import all asset accounts:
            foreach ($JSON->accounts as $entry) {
                \Log::debug('Create job to import asset account');
                \Queue::push( // count fixed
                    'Firefly\Storage\Account\AccountRepositoryInterface@importAccount', [
                        'data'         => $entry,
                        'class'        => 'Account',
                        'account_type' => 'Asset account',
                        'mapID'        => $importMap->id
                    ]
                );
                $totalJobs++;
            }

            // then import all beneficiaries:
            foreach ($JSON->components as $entry) {
                if ($entry->type->type == 'beneficiary') {
                    \Log::debug('Create job to import expense account');
                    \Queue::push( // count fixed
                        'Firefly\Storage\Account\AccountRepositoryInterface@importAccount', [
                            'data'         => $entry,
                            'class'        => 'Account',
                            'account_type' => 'Expense account',
                            'mapID'        => $importMap->id
                        ]
                    );
                    $totalJobs++;
                }
            }

            // then import all categories.
            foreach ($JSON->components as $entry) {
                if ($entry->type->type == 'category') {
                    \Log::debug('Create job to import category');
                    \Queue::push( // count fixed
                        'Firefly\Storage\Category\CategoryRepositoryInterface@importCategory', [
                            'data'  => $entry,
                            'class' => 'Category',
                            'mapID' => $importMap->id
                        ]
                    );
                    $totalJobs++;
                }
            }

            // then import all budgets:
            foreach ($JSON->components as $entry) {
                if ($entry->type->type == 'budget') {
                    \Log::debug('Create job to import budget');
                    \Queue::push( // count fixed
                        'Firefly\Storage\Budget\BudgetRepositoryInterface@importBudget', [
                            'data'  => $entry,
                            'class' => 'Budget',
                            'mapID' => $importMap->id
                        ]
                    );
                    $totalJobs++;
                }
            }

            // then import all limits.
            foreach ($JSON->limits as $entry) {
                \Log::debug('Create job to import limit');
                \Queue::push( // count fixed
                    'Firefly\Storage\Limit\LimitRepositoryInterface@importLimit', [
                        'data'  => $entry,
                        'class' => 'Limit',
                        'mapID' => $importMap->id
                    ]
                );
                $totalJobs++;
            }

            // all piggy banks
            foreach ($JSON->piggybanks as $entry) {
                \Log::debug('Create job to import piggy bank');
                \Queue::push( // count fixed
                    'Firefly\Storage\Piggybank\PiggybankRepositoryInterface@importPiggybank', [
                        'data'  => $entry,
                        'class' => 'Piggybank',
                        'mapID' => $importMap->id
                    ]
                );
                $totalJobs++;
            }

            // all predictables.
            foreach ($JSON->predictables as $entry) {
                \Log::debug('Create job to import predictable');
                \Queue::push( // count fixed
                    'Firefly\Storage\RecurringTransaction\RecurringTransactionRepositoryInterface@importPredictable', [
                        'data'  => $entry,
                        'class' => 'Predictable',
                        'mapID' => $importMap->id
                    ]
                );
                $totalJobs++;
            }

            // all settings (to fix the piggy banks)
            foreach ($JSON->settings as $entry) {
                \Log::debug('Create job to import setting');
                \Queue::push( // count fixed
                    'Firefly\Queue\Import@importSetting', [
                        'data'  => $entry,
                        'class' => 'Setting',
                        'mapID' => $importMap->id
                    ]
                );
                $totalJobs++;
            }

            // all transactions
            foreach ($JSON->transactions as $entry) {

                \Log::debug('Create job to import transaction');
                \Queue::push( // count fixed
                    'Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface@importTransaction', [
                        'data'  => $entry,
                        'class' => 'Transaction',
                        'mapID' => $importMap->id
                    ]
                );

                $totalJobs++;
            }

            // all transfers
            foreach ($JSON->transfers as $entry) {
                \Log::debug('Create job to import transfer');
                \Queue::push( // count fixed
                    'Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface@importTransfer', [
                        'data'  => $entry,
                        'class' => 'Transfer',
                        'mapID' => $importMap->id
                    ]
                );
                $totalJobs++;
            }

            // then, fix all component <> transaction links
            foreach ($JSON->component_transaction as $entry) {
                \Log::debug('Create job to import components_transaction');
                \Queue::push( // count fixed
                    'Firefly\Queue\Import@importComponentTransaction',
                    [
                        'data'  => $entry,
                        'mapID' => $importMap->id
                    ]
                );
                $totalJobs++;
            }


            // then, fix all component <> transfer links
            foreach ($JSON->component_transfer as $entry) {
                \Log::debug('Create job to import components_transfer');
                \Queue::push( // count fixed
                    'Firefly\Queue\Import@importComponentTransfer',
                    [
                        'data'  => $entry,
                        'mapID' => $importMap->id
                    ]
                );
                $totalJobs++;
            }

            $importMap->totaljobs = $totalJobs;
            $importMap->save();
            /*
             * We save the import map which now holds the number of jobs we've got planned.
             */

            \Queue::push('Firefly\Queue\Import@cleanImportAccount', ['mapID' => $importMap->id]);

            $job->delete(); // count fixed

            \Log::debug('Done with job "start"');
        }
    }
} 