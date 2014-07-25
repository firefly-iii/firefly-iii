<?php

namespace Firefly\Helper\Migration;


use Carbon\Carbon;
use Firefly\Exception\FireflyException;

/**
 * Class MigrationHelper
 *
 * @package Firefly\Helper\Migration
 */
class MigrationHelper implements MigrationHelperInterface
{
    protected $path;
    protected $JSON;
    protected $map = [];

    /**
     * @param $path
     *
     * @return mixed|void
     */
    public function loadFile($path)
    {
        $this->path = $path;
    }

    /**
     * @return bool
     */
    public function validFile()
    {
        // file does not exist:
        if (!file_exists($this->path)) {
            \Log::error('Migration file ' . $this->path . ' does not exist!');
            return false;
        }

        // load the content:
        $content = file_get_contents($this->path);
        if ($content === false) {
            return false;
        }

        // parse the content
        $this->JSON = json_decode($content);
        if (is_null($this->JSON)) {
            return false;
        }
        \Log::info('Migration file ' . $this->path . ' is valid!');
        return true;
    }

    /**
     * @return bool
     */
    public function migrate()
    {
        \Log::info('Start of migration.');
        \DB::beginTransaction();

        try {
            // create cash account:
            $this->_createCashAccount();

            $this->_importAccounts();
            $this->_importComponents();
            //$this->_importPiggybanks();

            // create transactions:
            $this->_importTransactions();

            // create transfers:
            $this->_importTransfers();

            // create limits:
            $this->_importLimits();


        } catch (FireflyException $e) {
            \DB::rollBack();
            \Log::error('Rollback because of error!');
            \Log::error($e->getMessage());
            return false;
        }

        \DB::commit();
        \Log::info('Done!');
        return true;
    }

    /**
     *
     */
    protected function _createCashAccount()
    {
        $cashAT = \AccountType::where('description', 'Cash account')->first();
        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $cash = $accounts->store(['name' => 'Cash account', 'account_type' => $cashAT, 'active' => 0]);
        \Log::info('Created cash account (#' . $cash->id . ')');
        $this->map['cash'] = $cash;
    }

    /**
     *
     */
    protected function _importAccounts()
    {

        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        \Log::info('Going to import ' . count($this->JSON->accounts) . ' accounts.');
        foreach ($this->JSON->accounts as $entry) {
            // create account:
            if ($entry->openingbalance == 0) {
                $account = $accounts->store(['name' => $entry->name]);
            } else {
                $account = $accounts->storeWithInitialBalance(
                    ['name' => $entry->name],
                    new Carbon($entry->openingbalancedate),
                    floatval($entry->openingbalance)
                );
            }
            $this->map['accounts'][$entry->id] = $account;
            \Log::info('Imported account "' . $entry->name . '" with balance ' . $entry->openingbalance);
        }
    }

    /**
     *
     */
    protected function _importComponents()
    {
        $beneficiaryAT = \AccountType::where('description', 'Beneficiary account')->first();
        foreach ($this->JSON->components as $entry) {
            switch ($entry->type->type) {
                case 'beneficiary':
                    $beneficiary = $this->_importBeneficiary($entry, $beneficiaryAT);
                    $this->map['accounts'][$entry->id] = $beneficiary;
                    break;
                case 'category':
                    $component = $this->_importCategory($entry);
                    $this->map['categories'][$entry->id] = $component;
                    break;
                case 'budget':
                    $component = $this->_importBudget($entry);
                    $this->map['budgets'][$entry->id] = $component;
                    break;
            }

        }
    }

    /**
     * @param              $component
     * @param \AccountType $beneficiaryAT
     *
     * @return mixed
     */
    protected function _importBeneficiary($component, \AccountType $beneficiaryAT)
    {
        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        return $accounts->store(
            [
                'name'         => $component->name,
                'account_type' => $beneficiaryAT
            ]
        );
    }

    /**
     * @param $component
     *
     * @return mixed
     */
    protected function _importCategory($component)
    {
        /** @var \Firefly\Storage\Component\ComponentRepositoryInterface $components */
        $components = \App::make('Firefly\Storage\Component\ComponentRepositoryInterface');
        return $components->store(['name' => $component->name, 'class' => 'Category']);
    }

    /**
     * @param $component
     *
     * @return mixed
     */
    protected function _importBudget($component)
    {
        /** @var \Firefly\Storage\Component\ComponentRepositoryInterface $components */
        $components = \App::make('Firefly\Storage\Component\ComponentRepositoryInterface');
        return $components->store(['name' => $component->name, 'class' => 'Budget']);
    }

    /**
     *
     */
    protected function _importTransactions()
    {

        /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface $journals */
        $journals = \App::make('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');

        // loop component_transaction to find beneficiaries, categories and budgets:
        $beneficiaries = [];
        $categories = [];
        $budgets = [];
        foreach ($this->JSON->component_transaction as $entry) {
            // beneficiaries
            if (isset($this->map['accounts'][$entry->component_id])) {
                $beneficiaries[$entry->transaction_id] = $this->map['accounts'][$entry->component_id];
            }

            // categories
            if (isset($this->map['categories'][$entry->component_id])) {
                $categories[$entry->transaction_id] = $this->map['categories'][$entry->component_id];
            }

            // budgets:
            if (isset($this->map['budgets'][$entry->component_id])) {
                $budgets[$entry->transaction_id] = $this->map['budgets'][$entry->component_id];
            }
        }

        foreach ($this->JSON->transactions as $entry) {

            // to properly save the amount, do it times -1:
            $amount = $entry->amount * -1;

            /** @var \Account $fromAccount */
            $fromAccount = isset($this->map['accounts'][$entry->account_id])
                ? $this->map['accounts'][$entry->account_id] : false;

            /** @var \Account $toAccount */
            $toAccount = isset($beneficiaries[$entry->id]) ? $beneficiaries[$entry->id] : $this->map['cash'];

            $date = new Carbon($entry->date);
            $journal = $journals->createSimpleJournal($fromAccount, $toAccount, $entry->description, $amount, $date);

            // save budgets and categories, on the journal
            if (isset($budgets[$entry->id])) {
                $budget = $budgets[$entry->id];
                $journal->budgets()->save($budget);
            }
            if (isset($categories[$entry->id])) {
                $category = $categories[$entry->id];
                $journal->categories()->save($category);
            }

        }
    }

    /**
     *
     */
    protected function _importTransfers()
    {
        /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface $journals */
        $journals = \App::make('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');

        foreach ($this->JSON->transfers as $entry) {

            // to properly save the amount, do it times 1 (?):
            $amount = $entry->amount * -1;

            /** @var \Account $fromAccount */
            $fromAccount = isset($this->map['accounts'][$entry->accountfrom_id])
                ? $this->map['accounts'][$entry->accountto_id] : false;

            /** @var \Account $toAccount */
            $toAccount = isset($this->map['accounts'][$entry->accountto_id])
                ? $this->map['accounts'][$entry->accountfrom_id] : false;

            $date = new Carbon($entry->date);
            $journals->createSimpleJournal($fromAccount, $toAccount, $entry->description, $amount, $date);

        }
    }

    /**
     *
     */
    protected function _importLimits()
    {
        \Log::info('Importing limits');
        foreach ($this->JSON->limits as $entry) {
            \Log::debug(
                'Now at #' . $entry->id . ': EUR ' . $entry->amount . ' for month ' . $entry->date
                . ' and componentID: ' . $entry->component_id
            );
            $budget = isset($this->map['budgets'][$entry->component_id]) ? $this->map['budgets'][$entry->component_id]
                : null;
            if (!is_null($budget)) {
                \Log::debug('Found budget for this limit: #' . $budget->id . ', ' . $budget->name);

                $limit = new \Limit;
                $limit->budget()->associate($budget);
                $limit->startdate = new Carbon($entry->date);
                $limit->amount = floatval($entry->amount);
                $limit->repeats = 0;
                $limit->repeat_freq = 'monthly';
                try {
                    $limit->save();
                } catch (\Exception $e) {
                }
            } else {
                \Log::warning('No budget for this limit!');
            }


            // create repeat thing should not be necessary.

        }
    }
}