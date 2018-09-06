<?php

/**
 * ImportArrayStorage.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Storage;

use Carbon\Carbon;
use DB;
use FireflyIII\Events\RequestedReportOnJournals;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Helpers\Filter\NegativeAmountFilter;
use FireflyIII\Helpers\Filter\PositiveAmountFilter;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Rule;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\TransactionRules\Processor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Log;

/**
 * Creates new transactions based upon arrays. Will first check the array for duplicates.
 *
 * Class ImportArrayStorage
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportArrayStorage
{
    /** @var bool Check for transfers during import. */
    private $checkForTransfers = false;
    /** @var ImportJob The import job */
    private $importJob;
    /** @var JournalRepositoryInterface */
    private $journalRepos;
    /** @var ImportJobRepositoryInterface Import job repository */
    private $repository;
    /** @var Collection The transfers. */
    private $transfers;

    /**
     * Set job, count transfers in the array and create the repository.
     *
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob = $importJob;
        $this->countTransfers();

        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);

        $this->journalRepos = app(JournalRepositoryInterface::class);
        $this->journalRepos->setUser($importJob->user);

        Log::debug('Constructed ImportArrayStorage()');
    }

    /**
     * Actually does the storing. Does three things.
     * - Store journals
     * - Link to tag
     * - Run rules (if set to)
     *
     * @return Collection
     * @throws FireflyException
     */
    public function store(): Collection
    {
        // store transactions
        $this->setStatus('storing_data');
        $collection = $this->storeArray();
        $this->setStatus('stored_data');

        // link tag:
        $this->setStatus('linking_to_tag');
        $this->linkToTag($collection);
        $this->setStatus('linked_to_tag');

        // run rules, if configured to.
        $config = $this->importJob->configuration;
        if (isset($config['apply-rules']) && true === $config['apply-rules']) {
            $this->setStatus('applying_rules');
            $this->applyRules($collection);
            $this->setStatus('rules_applied');
        }

        app('preferences')->mark();

        // email about this:
        event(new RequestedReportOnJournals($this->importJob->user_id, $collection));

        return $collection;
    }

    /**
     * Applies the users rules to the created journals.
     *
     * @param Collection $collection
     *
     */
    private function applyRules(Collection $collection): void
    {
        $rules = $this->getRules();
        if ($rules->count() > 0) {
            foreach ($collection as $journal) {
                $rules->each(
                    function (Rule $rule) use ($journal) {
                        Log::debug(sprintf('Going to apply rule #%d to journal %d.', $rule->id, $journal->id));
                        /** @var Processor $processor */
                        $processor = app(Processor::class);
                        $processor->make($rule);
                        $processor->handleTransactionJournal($journal);
                        if ($rule->stop_processing) {
                            return false;
                        }

                        return true;
                    }
                );
            }
        }
    }

    /**
     * Count the number of transfers in the array. If this is zero, don't bother checking for double transfers.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function countTransfers(): void
    {
        Log::debug('Now in count transfers.');
        /** @var array $array */
        $array = $this->importJob->transactions;
        $count = 0;
        foreach ($array as $index => $transaction) {
            if (strtolower(TransactionType::TRANSFER) === strtolower($transaction['type'])) {
                $count++;
                Log::debug(sprintf('Row #%d is a transfer, increase count to %d', $index + 1, $count));
            }
        }
        if (0 === $count) {
            Log::debug('Count is zero, will not check for duplicate transfers.');
        }
        if ($count > 0) {
            Log::debug(sprintf('Count is %d, will check for duplicate transfers.', $count));
            $this->checkForTransfers = true;

            // get users transfers. Needed for comparison.
            $this->getTransfers();
        }

    }

    /**
     * Get hash of transaction.
     *
     * @param array $transaction
     *
     * @throws FireflyException
     * @return string
     */
    private function getHash(array $transaction): string
    {
        unset($transaction['importHashV2'], $transaction['original-source']);
        $json = json_encode($transaction);
        if (false === $json) {
            /** @noinspection ForgottenDebugOutputInspection */
            Log::error('Could not encode import array.', print_r($transaction, true));
            throw new FireflyException('Could not encode import array. Please see the logs.'); // @codeCoverageIgnore
        }
        $hash = hash('sha256', $json, false);
        Log::debug(sprintf('The hash is: %s', $hash));

        return $hash;
    }

    /**
     * Gets the users rules.
     *
     * @return Collection
     */
    private function getRules(): Collection
    {
        /** @var RuleRepositoryInterface $repository */
        $repository = app(RuleRepositoryInterface::class);
        $repository->setUser($this->importJob->user);
        $set = $repository->getForImport();

        Log::debug(sprintf('Found %d user rules.', $set->count()));

        return $set;
    }

    /**
     * @param $journal
     *
     * @return Transaction
     */
    private function getTransactionFromJournal($journal): Transaction
    {
        // collect transactions using the journal collector
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->importJob->user);
        $collector->withOpposingAccount();
        // filter on specific journals.
        $collector->setJournals(new Collection([$journal]));

        // add filter to remove transactions:
        $transactionType = $journal->transactionType->type;
        if ($transactionType === TransactionType::WITHDRAWAL) {
            $collector->addFilter(PositiveAmountFilter::class);
        }
        if (!($transactionType === TransactionType::WITHDRAWAL)) {
            $collector->addFilter(NegativeAmountFilter::class);
        }
        /** @var Transaction $result */
        $result = $collector->getTransactions()->first();
        Log::debug(sprintf('Return transaction #%d with journal id #%d based on ID #%d', $result->id, $result->journal_id, $journal->id));

        return $result;
    }

    /**
     * Get the users transfers, so they can be compared to whatever the user is trying to import.
     */
    private function getTransfers(): void
    {
        Log::debug('Now in getTransfers()');
        app('preferences')->mark();

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($this->importJob->user);
        $collector->setAllAssetAccounts()
                  ->ignoreCache()
                  ->setTypes([TransactionType::TRANSFER])
                  ->withOpposingAccount();
        $collector->removeFilter(InternalTransferFilter::class);
        $this->transfers = $collector->getTransactions();
        Log::debug(sprintf('Count of getTransfers() is %d', $this->transfers->count()));
    }

    /**
     * Check if the hash exists for the array the user wants to import.
     *
     * @param string $hash
     *
     * @return int|null
     */
    private function hashExists(string $hash): ?int
    {
        $entry = $this->journalRepos->findByHash($hash);
        if (null === $entry) {
            Log::debug(sprintf('Found no transactions with hash %s.', $hash));

            return null;
        }
        Log::info(sprintf('Found a transaction journal with an existing hash: %s', $hash));

        return (int)$entry->transaction_journal_id;
    }

    /**
     * Link all imported journals to a tag.
     *
     * @param Collection $collection
     */
    private function linkToTag(Collection $collection): void
    {
        if (0 === $collection->count()) {
            return;
        }
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $repository->setUser($this->importJob->user);
        $data = [
            'tag'         => (string)trans('import.import_with_key', ['key' => $this->importJob->key]),
            'date'        => new Carbon,
            'description' => null,
            'latitude'    => null,
            'longitude'   => null,
            'zoomLevel'   => null,
            'tagMode'     => 'nothing',
        ];
        $tag  = $repository->store($data);

        Log::debug(sprintf('Created tag #%d ("%s")', $tag->id, $tag->tag));
        Log::debug('Looping journals...');
        $journalIds = $collection->pluck('id')->toArray();
        $tagId      = $tag->id;
        foreach ($journalIds as $journalId) {
            Log::debug(sprintf('Linking journal #%d to tag #%d...', $journalId, $tagId));
            try {
                DB::table('tag_transaction_journal')->insert(['transaction_journal_id' => $journalId, 'tag_id' => $tagId]);
            } catch (QueryException $e) {
                Log::error(sprintf('Could not link journal #%d to tag #%d because: %s', $journalId, $tagId, $e->getMessage()));
                Log::error($e->getTraceAsString());
            }
        }
        Log::info(sprintf('Linked %d journals to tag #%d ("%s")', $collection->count(), $tag->id, $tag->tag));

        $this->repository->setTag($this->importJob, $tag);

    }

    /**
     * Log about a duplicate object (double hash).
     *
     * @param array $transaction
     * @param int   $existingId
     */
    private function logDuplicateObject(array $transaction, int $existingId): void
    {
        Log::info(
            'Transaction is a duplicate, and will not be imported (the hash exists).',
            [
                'existing'    => $existingId,
                'description' => $transaction['description'] ?? '',
                'amount'      => $transaction['transactions'][0]['amount'] ?? 0,
                'date'        => $transaction['date'] ?? '',
            ]
        );

    }

    /**
     * Log about a duplicate transfer.
     *
     * @param array $transaction
     */
    private function logDuplicateTransfer(array $transaction): void
    {
        Log::info(
            'Transaction is a duplicate transfer, and will not be imported (such a transfer exists already).',
            [
                'description' => $transaction['description'] ?? '',
                'amount'      => $transaction['transactions'][0]['amount'] ?? 0,
                'date'        => $transaction['date'] ?? '',
            ]
        );
    }

    /**
     * Shorthand method to quickly set job status
     *
     * @param string $status
     */
    private function setStatus(string $status): void
    {
        $this->repository->setStatus($this->importJob, $status);
    }

    /**
     * Store array as journals.
     *
     * @return Collection
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function storeArray(): Collection
    {
        /** @var array $array */
        $array   = $this->importJob->transactions;
        $count   = \count($array);
        $toStore = [];

        Log::debug(sprintf('Now in store(). Count of items is %d', $count));

        foreach ($array as $index => $transaction) {
            Log::debug(sprintf('Now at item %d out of %d', $index + 1, $count));
            $hash       = $this->getHash($transaction);
            $existingId = $this->hashExists($hash);
            if (null !== $existingId) {
                $this->logDuplicateObject($transaction, $existingId);
                $this->repository->addErrorMessage(
                    $this->importJob, sprintf(
                                        'Row #%d ("%s") could not be imported. It already exists.',
                                        $index, $transaction['description']
                                    )
                );
                continue;
            }
            if ($this->checkForTransfers && $this->transferExists($transaction)) {
                $this->logDuplicateTransfer($transaction);
                $this->repository->addErrorMessage(
                    $this->importJob, sprintf(
                                        'Row #%d ("%s") could not be imported. Such a transfer already exists.',
                                        $index,
                                        $transaction['description']
                                    )
                );
                continue;
            }
            $transaction['importHashV2'] = $hash;
            $toStore[]                   = $transaction;
        }
        $count = \count($toStore);
        if (0 === $count) {
            Log::info('No transactions to store left!');

            return new Collection;
        }

        Log::debug('Going to store...');
        // now actually store them:
        $collection = new Collection;
        foreach ($toStore as $index => $store) {

            // do duplicate detection again!
            $hash       = $this->getHash($store);
            $existingId = $this->hashExists($hash);
            if (null !== $existingId) {
                $this->logDuplicateObject($store, $existingId);
                $this->repository->addErrorMessage(
                    $this->importJob, sprintf(
                                        'Row #%d ("%s") could not be imported. It already exists.',
                                        $index, $store['description']
                                    )
                );
                continue;
            }

            // do transfer detection again!
            if ($this->checkForTransfers && $this->transferExists($store)) {
                $this->logDuplicateTransfer($store);
                $this->repository->addErrorMessage(
                    $this->importJob, sprintf(
                                        'Row #%d ("%s") could not be imported. Such a transfer already exists.',
                                        $index,
                                        $store['description']
                                    )
                );
                continue;
            }

            Log::debug(sprintf('Going to store entry %d of %d', $index + 1, $count));
            // convert the date to an object:
            $store['date']        = Carbon::createFromFormat('Y-m-d', $store['date']);
            $store['description'] = '' === $store['description'] ? '(empty description)' : $store['description'];
            // store the journal.
            try {
                $journal = $this->journalRepos->store($store);
            } catch (FireflyException $e) {
                Log::error($e->getMessage());
                Log::error($e->getTraceAsString());
                $this->repository->addErrorMessage($this->importJob, sprintf('Row #%d could not be imported. %s', $index, $e->getMessage()));
                continue;
            }
            Log::debug(sprintf('Stored as journal #%d', $journal->id));
            $collection->push($journal);

            // add to collection of transfers, if necessary:
            if ('transfer' === strtolower($store['type'])) {
                $transaction = $this->getTransactionFromJournal($journal);
                Log::debug('We just stored a transfer, so add the journal to the list of transfers.');
                $this->transfers->push($transaction);
                Log::debug(sprintf('List length is now %d', $this->transfers->count()));
            }
        }
        Log::debug('DONE storing!');

        return $collection;
    }

    /**
     * Check if a transfer exists.
     *
     * @param $transaction
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function transferExists(array $transaction): bool
    {
        Log::debug('Check if is a double transfer.');
        if (strtolower(TransactionType::TRANSFER) !== strtolower($transaction['type'])) {
            Log::debug(sprintf('Is a %s, not a transfer so no.', $transaction['type']));

            return false;
        }
        // how many hits do we need?
        $requiredHits = \count($transaction['transactions']) * 4;
        $totalHits    = 0;
        Log::debug(sprintf('Required hits for transfer comparison is %d', $requiredHits));
        Log::debug(sprintf('Array has %d transactions.', \count($transaction['transactions'])));
        Log::debug(sprintf('System has %d existing transfers', \count($this->transfers)));
        // loop over each split:
        foreach ($transaction['transactions'] as $current) {

            // get the amount:
            /** @noinspection UnnecessaryCastingInspection */
            $amount = (string)($current['amount'] ?? '0');
            if (bccomp($amount, '0') === -1) {
                $amount = bcmul($amount, '-1'); // @codeCoverageIgnore
            }

            // get the description:
            $description = '' === (string)$current['description'] ? $transaction['description'] : $current['description'];

            // get the source and destination ID's:
            $currentSourceIDs = [(int)$current['source_id'], (int)$current['destination_id']];
            sort($currentSourceIDs);

            // get the source and destination names:
            $currentSourceNames = [(string)$current['source_name'], (string)$current['destination_name']];
            sort($currentSourceNames);

            // then loop all transfers:
            /** @var Transaction $transfer */
            foreach ($this->transfers as $transfer) {
                // number of hits for this split-transfer combination:
                $hits = 0;
                Log::debug(sprintf('Now looking at transaction journal #%d', $transfer->journal_id));
                // compare amount:
                Log::debug(sprintf('Amount %s compared to %s', $amount, $transfer->transaction_amount));
                if (0 !== bccomp($amount, $transfer->transaction_amount)) {
                    continue;
                }
                ++$hits;
                Log::debug(sprintf('Comparison is a hit! (%s)', $hits));

                // compare description:
                Log::debug(sprintf('Comparing "%s" to "%s"', $description, $transfer->description));
                if ($description !== $transfer->description) {
                    continue; // @codeCoverageIgnore
                }
                ++$hits;
                Log::debug(sprintf('Comparison is a hit! (%s)', $hits));

                // compare date:
                $transferDate = $transfer->date->format('Y-m-d');
                Log::debug(sprintf('Comparing dates "%s" to "%s"', $transaction['date'], $transferDate));
                if ($transaction['date'] !== $transferDate) {
                    continue; // @codeCoverageIgnore
                }
                ++$hits;
                Log::debug(sprintf('Comparison is a hit! (%s)', $hits));

                // compare source and destination id's
                $transferSourceIDs = [(int)$transfer->account_id, (int)$transfer->opposing_account_id];
                sort($transferSourceIDs);
                /** @noinspection DisconnectedForeachInstructionInspection */
                Log::debug('Comparing current transaction source+dest IDs', $currentSourceIDs);
                Log::debug('.. with current transfer source+dest IDs', $transferSourceIDs);
                if ($currentSourceIDs === $transferSourceIDs) {
                    ++$hits;
                    Log::debug(sprintf('Source IDs are the same! (%d)', $hits));
                }
                unset($transferSourceIDs);

                // compare source and destination names
                $transferSource = [(string)$transfer->account_name, (int)$transfer->opposing_account_name];
                sort($transferSource);
                /** @noinspection DisconnectedForeachInstructionInspection */
                Log::debug('Comparing current transaction source+dest names', $currentSourceNames);
                Log::debug('.. with current transfer source+dest names', $transferSource);
                if ($currentSourceNames === $transferSource) {
                    // @codeCoverageIgnoreStart
                    Log::debug(sprintf('Source names are the same! (%d)', $hits));
                    ++$hits;
                    // @codeCoverageIgnoreEnd
                }
                $totalHits += $hits;
                if ($totalHits >= $requiredHits) {
                    return true;
                }
            }
        }
        Log::debug(sprintf('Total hits: %d, required: %d', $totalHits, $requiredHits));

        return $totalHits >= $requiredHits;
    }

}
