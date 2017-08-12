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
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Rules\Processor;
use Illuminate\Database\Query\JoinClause;
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
    /** @var Collection */
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
    /** @var  TagRepositoryInterface */
    private $tagRepository;

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
        $this->job = $job;

        $repository = app(CurrencyRepositoryInterface::class);
        $repository->setUser($job->user);
        $this->currencyRepository = $repository;

        $repository = app(TagRepositoryInterface::class);
        $repository->setUser($job->user);
        $this->tagRepository = $repository;

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
     * Do storage of import objects.
     */
    public function store()
    {
        $this->defaultCurrency = Amount::getDefaultCurrencyByUser($this->job->user);

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
        Log::info('ImportStorage has finished.');

        return true;
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
     * @param array $parameters
     *
     * @return bool
     * @throws FireflyException
     */
    private function createTransaction(array $parameters): bool
    {
        $transaction                          = new Transaction;
        $transaction->account_id              = $parameters['account'];
        $transaction->transaction_journal_id  = $parameters['id'];
        $transaction->transaction_currency_id = $parameters['currency'];
        $transaction->amount                  = $parameters['amount'];
        $transaction->foreign_currency_id     = $parameters['foreign_currency'];
        $transaction->foreign_amount          = $parameters['foreign_amount'];
        $transaction->save();
        if (is_null($transaction->id)) {
            $errorText = join(', ', $transaction->getErrors()->all());
            throw new FireflyException($errorText);
        }
        Log::debug(sprintf('Created transaction with ID #%d, account #%d, amount %s', $transaction->id, $parameters['account'], $parameters['amount']));

        return true;
    }

    /**
     * @param Collection    $set
     * @param ImportJournal $importJournal
     *
     * @return bool
     */
    private function filterTransferSet(Collection $set, ImportJournal $importJournal): bool
    {
        $amount      = Steam::positive($importJournal->getAmount());
        $asset       = $importJournal->asset->getAccount();
        $opposing    = $this->getOpposingAccount($importJournal->opposing,$asset->id, $amount);
        $description = $importJournal->getDescription();

        $filtered = $set->filter(
            function (TransactionJournal $journal) use ($asset, $opposing, $description) {
                $match    = true;
                $original = [app('steam')->tryDecrypt($journal->source_name), app('steam')->tryDecrypt($journal->destination_name)];
                $compare  = [$asset->name, $opposing->name];
                sort($original);
                sort($compare);

                // description does not match? Then cannot be duplicate.
                if ($journal->description !== $description) {
                    $match = false;
                }
                // not both accounts in journal? Then cannot be duplicate.
                if ($original !== $compare) {
                    $match = false;
                }

                if ($match) {
                    return $journal;
                }

                return null;
            }
        );
        if (count($filtered) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param ImportJournal $importJournal
     *
     * @param Account       $account
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
     * @param ImportJournal       $importJournal
     * @param TransactionCurrency $localCurrency
     *
     * @return int|null
     */
    private function getForeignCurrencyId(ImportJournal $importJournal, TransactionCurrency $localCurrency): ?int
    {
        // get journal currency, if any:
        $currency = $importJournal->getCurrency()->getTransactionCurrency();
        if (is_null($currency->id)) {
            Log::debug('getForeignCurrencyId: Journal has no currency, so can\'t be foreign either way.');

            // journal has no currency, so can't be foreign either way:
            return null;
        }

        if ($currency->id !== $localCurrency->id) {
            Log::debug(
                sprintf('getForeignCurrencyId: journal is %s, but account is %s. Return id of journal currency.', $currency->code, $localCurrency->code)
            );

            // journal has different currency than account does, return its ID:
            return $currency->id;
        }

        Log::debug('getForeignCurrencyId: journal has no foreign currency.');

        // return null in other cases.
        return null;
    }

    /**
     * @param ImportAccount $account
     * @param               $amount
     *
     * @return Account
     */
    private function getOpposingAccount(ImportAccount $account, int $forbiddenAccount, string $amount): Account
    {
        $account->setForbiddenAccountId($forbiddenAccount);
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
        Log::debug(sprintf('Going to store object #%d with description "%s"', $index, $importJournal->getDescription()));
        $importJournal->asset->setDefaultAccountId($this->job->configuration['import-account']);
        $asset             = $importJournal->asset->getAccount();
        $amount            = $importJournal->getAmount();
        $currency          = $this->getCurrency($importJournal, $asset);
        $foreignCurrencyId = $this->getForeignCurrencyId($importJournal, $currency);
        $date              = $importJournal->getDate($this->dateFormat);
        $transactionType   = $this->getTransactionType($amount);
        $opposing          = $this->getOpposingAccount($importJournal->opposing, $asset->id, $amount);

        // if opposing is an asset account, it's a transfer:
        if ($opposing->accountType->type === AccountType::ASSET) {
            Log::debug(sprintf('Opposing account #%d %s is an asset account, make transfer.', $opposing->id, $opposing->name));
            $transactionType = TransactionType::whereType(TransactionType::TRANSFER)->first();
        }

        // verify that opposing account is of the correct type:
        if ($opposing->accountType->type === AccountType::EXPENSE && $transactionType->type !== TransactionType::WITHDRAWAL) {
            $message = sprintf('Row #%d is imported as a %s but opposing is an expense account. This cannot be!', $index, $transactionType->type);
            Log::error($message);
            throw new FireflyException($message);

        }

        /*** First step done! */
        $this->job->addStepsDone(1);

        // could be that transfer is double: verify this.
        if ($this->verifyDoubleTransfer($transactionType, $importJournal)) {
            // add three steps:
            $this->job->addStepsDone(3);
            // throw error
            throw new FireflyException('Detected a possible duplicate, skip this one.');

        }

        // create a journal:
        $journal                          = new TransactionJournal;
        $journal->user_id                 = $this->job->user_id;
        $journal->transaction_type_id     = $transactionType->id;
        $journal->transaction_currency_id = $currency->id;// always currency of account
        $journal->description             = $importJournal->getDescription();
        $journal->date                    = $date->format('Y-m-d');
        $journal->order                   = 0;
        $journal->tag_count               = 0;
        $journal->completed               = false;

        if (!$journal->save()) {
            $errorText = join(', ', $journal->getErrors()->all());
            // add three steps:
            $this->job->addStepsDone(3);
            // throw error
            throw new FireflyException($errorText);
        }

        // save meta data:
        $journal->setMeta('importHash', $importJournal->hash);
        Log::debug(sprintf('Created journal with ID #%d', $journal->id));

        // create transactions:
        $one = [
            'id'               => $journal->id,
            'account'          => $asset->id,
            'currency'         => $currency->id,
            'amount'           => $amount,
            'foreign_currency' => $foreignCurrencyId,
            'foreign_amount'   => is_null($foreignCurrencyId) ? null : $amount,
        ];
        $two = [
            'id'               => $journal->id,
            'account'          => $opposing->id,
            'currency'         => $currency->id,
            'amount'           => Steam::opposite($amount),
            'foreign_currency' => $foreignCurrencyId,
            'foreign_amount'   => is_null($foreignCurrencyId) ? null : Steam::opposite($amount),
        ];
        $this->createTransaction($one);
        $this->createTransaction($two);

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

        // store tags
        $this->storeTags($importJournal->tags, $journal);

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

    /**
     * @param array              $tags
     * @param TransactionJournal $journal
     */
    private function storeTags(array $tags, TransactionJournal $journal): void
    {
        foreach ($tags as $tag) {
            $dbTag = $this->tagRepository->findByTag($tag);
            if (is_null($dbTag->id)) {
                $dbTag = $this->tagRepository->store(
                    ['tag'       => $tag, 'date' => null, 'description' => null, 'latitude' => null, 'longitude' => null,
                     'zoomLevel' => null, 'tagMode' => 'nothing']
                );
            }
            $journal->tags()->save($dbTag);
            Log::debug(sprintf('Linked tag %d ("%s") to journal #%d', $dbTag->id, $dbTag->tag, $journal->id));
        }

        return;
    }

    /**
     * This method checks if the given transaction is a transfer and if so, if it might be a duplicate of an already imported transfer.
     * This is important for import files that cover multiple accounts (and include both A<>B and B<>A transactions).
     *
     * @param TransactionType $transactionType
     * @param ImportJournal   $importJournal
     *
     * @return bool
     */
    private function verifyDoubleTransfer(TransactionType $transactionType, ImportJournal $importJournal): bool
    {
        if ($transactionType->type === TransactionType::TRANSFER) {
            $amount = Steam::positive($importJournal->getAmount());
            $date   = $importJournal->getDate($this->dateFormat);
            $set    = TransactionJournal::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                        ->leftJoin(
                                            'transactions AS source', function (JoinClause $join) {
                                            $join->on('transaction_journals.id', '=', 'source.transaction_journal_id')->where('source.amount', '<', 0);
                                        }
                                        )
                                        ->leftJoin(
                                            'transactions AS destination', function (JoinClause $join) {
                                            $join->on('transaction_journals.id', '=', 'destination.transaction_journal_id')->where(
                                                'destination.amount', '>', 0
                                            );
                                        }
                                        )
                                        ->leftJoin('accounts as source_accounts', 'source.account_id', '=', 'source_accounts.id')
                                        ->leftJoin('accounts as destination_accounts', 'destination.account_id', '=', 'destination_accounts.id')
                                        ->where('transaction_journals.user_id', $this->job->user_id)
                                        ->where('transaction_types.type', TransactionType::TRANSFER)
                                        ->where('transaction_journals.date', $date->format('Y-m-d'))
                                        ->where('destination.amount', $amount)
                                        ->get(
                                            ['transaction_journals.id', 'transaction_journals.encrypted', 'transaction_journals.description',
                                             'source_accounts.name as source_name', 'destination_accounts.name as destination_name']
                                        );

            return $this->filterTransferSet($set, $importJournal);
        }


        return false;
    }

}
