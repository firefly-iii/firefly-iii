<?php
/**
 * ImportStorage.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Storage;

use Amount;
use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Object\ImportAccount;
use FireflyIII\Import\Object\ImportJournal;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Rule;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Rules\Processor;
use Illuminate\Support\Collection;
use Log;
use Steam;

/**
 * Is capable of storing individual ImportJournal objects.
 * Class ImportStorage
 *
 * @package FireflyIII\Import\Storage
 */
class ImportStorage
{
    /** @var  Collection */
    public $errors;
    public $journals;
    /** @var  CurrencyRepositoryInterface */
    private $currencyRepository;
    /** @var string */
    private $dateFormat = 'Ymd';
    /** @var  TransactionCurrency */
    private $defaultCurrency;
    /** @var  ImportJob */
    private $job;
    /** @var Collection */
    private $objects;
    /** @var Collection */
    private $rules;

    /**
     * ImportStorage constructor.
     */
    public function __construct()
    {
        $this->objects  = new Collection;
        $this->journals = new Collection;
        $this->errors   = new Collection;
    }

    /**
     * @param string $dateFormat
     */
    public function setDateFormat(string $dateFormat)
    {
        $this->dateFormat = $dateFormat;
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job)
    {
        $this->job  = $job;
        $repository = app(CurrencyRepositoryInterface::class);
        $repository->setUser($job->user);
        $this->currencyRepository = $repository;
        $this->rules              = $this->getUserRules();
    }

    /**
     * @param Collection $objects
     */
    public function setObjects(Collection $objects)
    {
        $this->objects = $objects;
    }

    /**
     * Do storage of import objects
     */
    public function store()
    {
        $this->defaultCurrency = Amount::getDefaultCurrencyByUser($this->job->user);

        // routine below consists of 3 steps.
        /**
         * @var int           $index
         * @var ImportJournal $object
         */
        foreach ($this->objects as $index => $object) {
            try {
                $this->storeImportJournal($index, $object);
            } catch (FireflyException $e) {
                $this->errors->push($e->getMessage());
                Log::error(sprintf('Cannot import row #%d because: %s', $index, $e->getMessage()));
            }
        }


    }

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    protected function applyRules(TransactionJournal $journal): bool
    {
        if ($this->rules->count() > 0) {

            /** @var Rule $rule */
            foreach ($this->rules as $rule) {
                Log::debug(sprintf('Going to apply rule #%d to journal %d.', $rule->id, $journal->id));
                $processor = Processor::make($rule);
                $processor->handleTransactionJournal($journal);

                if ($rule->stop_processing) {
                    return true;
                }
            }
        }

        return true;
    }

    /**
     * @param int    $journalId
     * @param int    $accountId
     * @param int    $currencyId
     * @param string $amount
     *
     * @return bool
     * @throws FireflyException
     */
    private function createTransaction(int $journalId, int $accountId, int $currencyId, string $amount): bool
    {
        $transaction                          = new Transaction;
        $transaction->account_id              = $accountId;
        $transaction->transaction_journal_id  = $journalId;
        $transaction->transaction_currency_id = $currencyId;
        $transaction->amount                  = $amount;
        $transaction->save();
        if (is_null($transaction->id)) {
            $errorText = join(', ', $transaction->getErrors()->all());
            throw new FireflyException($errorText);
        }
        Log::debug(sprintf('Created transaction with ID #%d and account #%d', $transaction->id, $accountId));

        return true;
    }

    /**
     * @param ImportJournal $importJournal
     *
     * @return TransactionCurrency
     */
    private function getCurrency(ImportJournal $importJournal, Account $account): TransactionCurrency
    {
        // start with currency pref of account, if any:
        $currency = $this->currencyRepository->find(intval($account->getMeta('currency_id')));
        if (!is_null($currency->id)) {
            return $currency;
        }

        // use given currency
        $currency = $importJournal->getCurrency()->getTransactionCurrency();
        if (!is_null($currency->id)) {
            return $currency;
        }

        // backup to default
        $currency = $this->defaultCurrency;

        return $currency;
    }

    /**
     * @param ImportAccount $account
     * @param               $amount
     *
     * @return Account
     */
    private function getOpposingAccount(ImportAccount $account, $amount): Account
    {
        if (bccomp($amount, '0') === -1) {
            Log::debug(sprintf('%s is negative, create opposing expense account.', $amount));
            $account->setExpectedType(AccountType::EXPENSE);

            return $account->getAccount();
        }
        Log::debug(sprintf('%s is positive, create opposing revenue account.', $amount));
        // amount is positive, it's a deposit, opposing is an revenue:
        $account->setExpectedType(AccountType::REVENUE);

        $databaseAccount = $account->getAccount();

        return $databaseAccount;

    }

    /**
     * @param string $amount
     *
     * @return TransactionType
     */
    private function getTransactionType(string $amount): TransactionType
    {
        $transactionType = new TransactionType();
        // amount is negative, it's a withdrawal, opposing is an expense:
        if (bccomp($amount, '0') === -1) {
            $transactionType = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        }
        if (bccomp($amount, '0') === 1) {
            $transactionType = TransactionType::whereType(TransactionType::DEPOSIT)->first();
        }

        return $transactionType;
    }

    /**
     * @return Collection
     */
    private function getUserRules(): Collection
    {
        $set = Rule::distinct()
                   ->where('rules.user_id', $this->job->user->id)
                   ->leftJoin('rule_groups', 'rule_groups.id', '=', 'rules.rule_group_id')
                   ->leftJoin('rule_triggers', 'rules.id', '=', 'rule_triggers.rule_id')
                   ->where('rule_groups.active', 1)
                   ->where('rule_triggers.trigger_type', 'user_action')
                   ->where('rule_triggers.trigger_value', 'store-journal')
                   ->where('rules.active', 1)
                   ->orderBy('rule_groups.order', 'ASC')
                   ->orderBy('rules.order', 'ASC')
                   ->get(['rules.*', 'rule_groups.order']);
        Log::debug(sprintf('Found %d user rules.', $set->count()));

        return $set;

    }

    /**
     * @param TransactionJournal $journal
     * @param Bill               $bill
     */
    private function storeBill(TransactionJournal $journal, Bill $bill)
    {
        if (!is_null($bill->id)) {
            Log::debug(sprintf('Linked bill #%d to journal #%d', $bill->id, $journal->id));
            $journal->bill()->associate($bill);
            $journal->save();
        }
    }

    /**
     * @param TransactionJournal $journal
     * @param Budget             $budget
     */
    private function storeBudget(TransactionJournal $journal, Budget $budget)
    {
        if (!is_null($budget->id)) {
            Log::debug(sprintf('Linked budget #%d to journal #%d', $budget->id, $journal->id));
            $journal->budgets()->save($budget);
        }
    }

    /**
     * @param TransactionJournal $journal
     * @param Category           $category
     */
    private function storeCategory(TransactionJournal $journal, Category $category)
    {

        if (!is_null($category->id)) {
            Log::debug(sprintf('Linked category #%d to journal #%d', $category->id, $journal->id));
            $journal->categories()->save($category);
        }

    }

    private function storeImportJournal(int $index, ImportJournal $importJournal): bool
    {
        Log::debug(sprintf('Going to store object #%d with description "%s"', $index, $importJournal->description));

        $asset           = $importJournal->asset->getAccount();
        $amount          = $importJournal->getAmount();
        $currency        = $this->getCurrency($importJournal, $asset);
        $date            = $importJournal->getDate($this->dateFormat);
        $transactionType = $this->getTransactionType($amount);
        $opposing        = $this->getOpposingAccount($importJournal->opposing, $amount);

        // if opposing is an asset account, it's a transfer:
        if ($opposing->accountType->type === AccountType::ASSET) {
            Log::debug(sprintf('Opposing account #%d %s is an asset account, make transfer.', $opposing->id, $opposing->name));
            $transactionType = TransactionType::whereType(TransactionType::TRANSFER)->first();
        }

        // verify that opposing account is of the correct type:
        if ($opposing->accountType->type === AccountType::EXPENSE && $transactionType->type !== TransactionType::WITHDRAWAL) {
            Log::error(sprintf('Row #%d is imported as a %s but opposing is an expense account. This cannot be!', $index, $transactionType->type));
        }

        /*** First step done! */
        $this->job->addStepsDone(1);

        // create a journal:
        $journal                          = new TransactionJournal;
        $journal->user_id                 = $this->job->user_id;
        $journal->transaction_type_id     = $transactionType->id;
        $journal->transaction_currency_id = $currency->id;
        $journal->description             = $importJournal->description;
        $journal->date                    = $date->format('Y-m-d');
        $journal->order                   = 0;
        $journal->tag_count               = 0;
        $journal->completed               = false;

        if (!$journal->save()) {
            $errorText = join(', ', $journal->getErrors()->all());
            $this->job->addStepsDone(3);
            throw new FireflyException($errorText);
        }

        // save meta data:
        $journal->setMeta('importHash', $importJournal->hash);
        Log::debug(sprintf('Created journal with ID #%d', $journal->id));

        // create transactions:
        $this->createTransaction($journal->id, $asset->id, $currency->id, $amount);
        $this->createTransaction($journal->id, $opposing->id, $currency->id, Steam::opposite($amount));

        /*** Another step done! */
        $this->job->addStepsDone(1);

        // store meta object things:
        $this->storeCategory($journal, $importJournal->category->getCategory());
        $this->storeBudget($journal, $importJournal->budget->getBudget());
        $this->storeBill($journal, $importJournal->bill->getBill());
        $this->storeMeta($journal, $importJournal->metaDates);

        // sepa thing as note:
        if (strlen($importJournal->notes) > 0) {
            $journal->setMeta('notes', $importJournal->notes);
        }

        // set journal completed:
        $journal->completed = true;
        $journal->save();

        $this->job->addStepsDone(1);

        // run rules:
        $this->applyRules($journal);
        $this->job->addStepsDone(1);

        $this->journals->push($journal);

        Log::info(
            sprintf(
                'Imported new journal #%d with description "%s" and amount %s %s.', $journal->id, $journal->description, $journal->transactionCurrency->code,
                $amount
            )
        );

        return true;
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $dates
     */
    private function storeMeta(TransactionJournal $journal, array $dates)
    {
        // all other date fields as meta thing:
        foreach ($dates as $name => $value) {
            try {
                $date = new Carbon($value);
                $journal->setMeta($name, $date);
            } catch (\Exception $e) {
                // don't care, ignore:
                Log::warning(sprintf('Could not parse "%s" into a valid Date object for field %s', $value, $name));
            }
        }
    }

}
