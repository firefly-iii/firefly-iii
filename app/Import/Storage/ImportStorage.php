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
use FireflyIII\Import\Object\ImportJournal;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Rule;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Rules\Processor;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
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
        $this->job   = $job;
        $this->rules = $this->getUserRules();
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
            sleep(4);
            Log::debug(sprintf('Going to store object #%d with description "%s"', $index, $object->description));

            $errors = new MessageBag;

            // create the asset account
            $asset           = $object->asset->getAccount();
            $opposing        = new Account;
            $amount          = $object->getAmount();
            $currency        = $object->getCurrency()->getTransactionCurrency();
            $date            = $object->getDate($this->dateFormat);
            $transactionType = new TransactionType;

            if (is_null($currency->id)) {
                $currency = $this->defaultCurrency;
            }

            if (bccomp($amount, '0') === -1) {
                // amount is negative, it's a withdrawal, opposing is an expense:
                Log::debug(sprintf('%s is negative, create opposing expense account.', $amount));
                $object->opposing->setExpectedType(AccountType::EXPENSE);
                $opposing        = $object->opposing->getAccount();
                $transactionType = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
            }
            if (bccomp($amount, '0') === 1) {
                Log::debug(sprintf('%s is positive, create opposing revenue account.', $amount));
                // amount is positive, it's a deposit, opposing is an revenue:
                $object->opposing->setExpectedType(AccountType::REVENUE);
                $opposing        = $object->opposing->getAccount();
                $transactionType = TransactionType::whereType(TransactionType::DEPOSIT)->first();
            }

            // if opposing is an asset account, it's a transfer:
            if ($opposing->accountType->type === AccountType::ASSET) {
                Log::debug(sprintf('Opposing account #%d %s is an asset account, make transfer.', $opposing->id, $opposing->name));
                $transactionType = TransactionType::whereType(TransactionType::TRANSFER)->first();
            }

            $this->job->addStepsDone(1);

            $journal                          = new TransactionJournal;
            $journal->user_id                 = $this->job->user_id;
            $journal->transaction_type_id     = $transactionType->id;
            $journal->transaction_currency_id = $currency->id;
            $journal->description             = $object->description;
            $journal->date                    = $date->format('Y-m-d');
            $journal->order                   = 0;
            $journal->tag_count               = 0;
            $journal->encrypted               = 0;
            $journal->completed               = false;

            if (rand(1, 10) === 3) {
                $journal->date        = null;
                $journal->description = null;
            }

            if (!$journal->save()) {
                $errorText = join(', ', $journal->getErrors()->all());
                $this->addErrorToJob($index, sprintf('Error storing line #%d: %s', $index, $errorText));
                Log::error(sprintf('Could not store line #%d: %s', $index, $errorText));
                // add the rest of the steps:
                $this->job->addStepsDone(3);

                continue;
            }
            $journal->setMeta('importHash', $object->hash);
            Log::debug(sprintf('Created journal with ID #%d', $journal->id));

            // create transactions:
            $one                          = new Transaction;
            $one->account_id              = $asset->id;
            $one->transaction_journal_id  = $journal->id;
            $one->transaction_currency_id = $currency->id;
            $one->amount                  = $amount;
            $one->save();
            if (is_null($one->id)) {
                $errorText = join(', ', $one->getErrors()->all());
                $errors->add('no-key', sprintf('Error storing transaction one for journal %d: %s', $journal->id, $errorText));
            }
            Log::debug(sprintf('Created transaction with ID #%d and account #%d', $one->id, $asset->id));

            $two                          = new Transaction;
            $two->account_id              = $opposing->id;
            $two->transaction_journal_id  = $journal->id;
            $two->transaction_currency_id = $currency->id;
            $two->amount                  = Steam::opposite($amount);
            $two->save();
            if (is_null($two->id)) {
                $errorText = join(', ', $two->getErrors()->all());
                $errors->add('no-key', sprintf('Error storing transaction one for journal %d: %s', $journal->id, $errorText));
            }
            Log::debug(sprintf('Created transaction with ID #%d and account #%d', $two->id, $opposing->id));

            $this->job->addStepsDone(1);

            // category
            $category = $object->category->getCategory();
            if (!is_null($category->id)) {
                Log::debug(sprintf('Linked category #%d to journal #%d', $category->id, $journal->id));
                $journal->categories()->save($category);
            }

            // budget
            $budget = $object->budget->getBudget();
            if (!is_null($budget->id)) {
                Log::debug(sprintf('Linked budget #%d to journal #%d', $budget->id, $journal->id));
                $journal->budgets()->save($budget);
            }
            // bill
            $bill = $object->bill->getBill();
            if (!is_null($bill->id)) {
                Log::debug(sprintf('Linked bill #%d to journal #%d', $bill->id, $journal->id));
                $journal->bill()->associate($bill);
                $journal->save();
            }

            // all other date fields as meta thing:
            foreach ($object->metaDates as $name => $value) {
                try {
                    $date = new Carbon($value);
                    $journal->setMeta($name, $date);
                } catch (\Exception $e) {
                    // don't care, ignore:
                    Log::warning(sprintf('Could not parse "%s" into a valid Date object for field %s', $value, $name));
                }
            }

            // sepa thing as note:
            if (strlen($object->notes) > 0) {
                $journal->setMeta('notes', $object->notes);
            }

            // set journal completed:
            $journal->completed = true;
            $journal->save();

            $this->job->addStepsDone(1);

            // run rules:
            $this->applyRules($journal);
            $this->job->addStepsDone(1);

            $this->journals->push($journal);
            $this->errors->push($errors);
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
     * @param int    $index
     * @param string $error
     */
    private function addErrorToJob(int $index, string $error)
    {
        $extended                     = $this->job->extended_status;
        $extended['errors'][$index][] = $error;
        $this->job->extended_status   = $extended;
        $this->job->save();
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


}