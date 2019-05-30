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
 * Creates new transactions based on arrays.
 *
 * Class ImportArrayStorage
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportArrayStorage
{
    /** @var int Number of hits required for a transfer to match. */
    private const REQUIRED_HITS = 4;
    /** @var bool Check for transfers during import. */
    private $checkForTransfers = false;
    /** @var ImportJob The import job */
    private $importJob;
    /** @var JournalRepositoryInterface Journal repository for storage. */
    private $journalRepos;
    /** @var ImportJobRepositoryInterface Import job repository */
    private $repository;
    /** @var Collection The transfers the user already has. */
    private $transfers;

    /**
     * Set job, count transfers in the array and create the repository.
     *
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);

        $this->countTransfers();

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
        event(new RequestedReportOnJournals((int)$this->importJob->user_id, $collection));

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
                        $journal->refresh();
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
     */
    private function countTransfers(): void
    {
        Log::debug('Now in countTransfers()');
        /** @var array $array */
        $array = $this->repository->getTransactions($this->importJob);


        $count = 0;
        foreach ($array as $index => $transaction) {
            if (strtolower(TransactionType::TRANSFER) === strtolower($transaction['type'])) {
                $count++;
                Log::debug(sprintf('Row #%d is a transfer, increase count to %d', $index + 1, $count));
            }
        }
        Log::debug(sprintf('Count of transfers in import array is %d.', $count));
        if ($count > 0) {
            $this->checkForTransfers = true;
            Log::debug('Will check for duplicate transfers.');
            // get users transfers. Needed for comparison.
            $this->getTransfers();
        }
    }

    /**
     * @param int   $index
     * @param array $transaction
     *
     * @return bool
     * @throws FireflyException
     */
    private function duplicateDetected(int $index, array $transaction): bool
    {
        $hash       = $this->getHash($transaction);
        $existingId = $this->hashExists($hash);
        if (null !== $existingId) {
            $message = sprintf('Row #%d ("%s") could not be imported. It already exists.', $index, $transaction['description']);
            $this->logDuplicateObject($transaction, $existingId);
            $this->repository->addErrorMessage($this->importJob, $message);

            return true;
        }

        // do transfer detection:
        if ($this->checkForTransfers && $this->transferExists($transaction)) {
            $message = sprintf('Row #%d ("%s") could not be imported. Such a transfer already exists.', $index, $transaction['description']);
            $this->logDuplicateTransfer($transaction);
            $this->repository->addErrorMessage($this->importJob, $message);

            return true;
        }

        return false;
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
        unset($transaction['import_hash_v2'], $transaction['original_source']);
        $json = json_encode($transaction);
        if (false === $json) {
            // @codeCoverageIgnoreStart
            /** @noinspection ForgottenDebugOutputInspection */
            Log::error('Could not encode import array.', $transaction);
            throw new FireflyException('Could not encode import array. Please see the logs.');
            // @codeCoverageIgnoreEnd
        }
        $hash = hash('sha256', $json);
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
            'zoom_level'  => null,
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
        $array   = $this->repository->getTransactions($this->importJob);
        $count   = count($array);
        $toStore = [];

        Log::notice(sprintf('Will now store the transactions. Count of items is %d.', $count));

        /*
         * Detect duplicates in initial array:
         */
        foreach ($array as $index => $transaction) {
            Log::debug(sprintf('Now at item %d out of %d', $index + 1, $count));
            if ($this->duplicateDetected($index, $transaction)) {
                Log::warning(sprintf('Row #%d seems to be a duplicate entry and will be ignored.', $index));
                continue;
            }
            $transaction['import_hash_v2'] = $this->getHash($transaction);
            $toStore[]                   = $transaction;
        }
        $count = count($toStore);
        if (0 === $count) {
            Log::info('No transactions to store left!');

            return new Collection;
        }
        Log::notice(sprintf('After a first check for duplicates, the count of items is %d.', $count));
        Log::notice('Going to store...');
        // now actually store them:
        $collection = new Collection;
        foreach ($toStore as $index => $store) {
            // do duplicate detection again!
            if ($this->duplicateDetected($index, $store)) {
                Log::warning(sprintf('Row #%d seems to be a imported already and will be ignored.', $index), $store);
                continue;
            }

            Log::debug(sprintf('Going to store entry %d of %d', $index + 1, $count));
            // convert the date to an object:
            $store['date']        = Carbon::parse($store['date'], config('app.timezone'));
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

            Log::info(sprintf('Stored #%d: "%s" (ID #%d)', $index, $journal->description, $journal->id));
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
        Log::notice(sprintf('Done storing. Firefly III has stored %d transactions.', $collection->count()));

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
        Log::debug('Check if array is a double transfer.');
        if (strtolower(TransactionType::TRANSFER) !== strtolower($transaction['type'])) {
            Log::debug(sprintf('Is a %s, not a transfer so no.', $transaction['type']));

            return false;
        }
        // how many hits do we need?
        Log::debug(sprintf('Array has %d transactions.', count($transaction['transactions'])));
        Log::debug(sprintf('System has %d existing transfers', count($this->transfers)));
        // loop over each split:
        Log::debug(sprintf('This transfer has %d split(s)', count($transaction['transactions'])));
        foreach ($transaction['transactions'] as $index => $current) {
            Log::debug(sprintf('Required hits for transfer comparison is %d', self::REQUIRED_HITS));
            Log::debug(sprintf('Now at transfer split %d of %d', $index + 1, count($transaction['transactions'])));

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
                    Log::debug('Amount is not a match, continue with next transfer.');
                    continue;
                }
                ++$hits;
                Log::debug(sprintf('Comparison is a hit! (%s)', $hits));

                // compare description:
                $comparison = '(empty description)' === $transfer->description ? '' : $transfer->description;
                Log::debug(sprintf('Comparing "%s" to "%s" (original: "%s")', $description, $transfer->description, $comparison));
                if ($description !== $comparison) {
                    Log::debug('Description is not a match, continue with next transfer.');
                    continue; // @codeCoverageIgnore
                }
                ++$hits;
                Log::debug(sprintf('Comparison is a hit! (%s)', $hits));

                // compare date:
                $transferDate = $transfer->date->format('Y-m-d H:i:s');
                Log::debug(sprintf('Comparing dates "%s" to "%s"', $transaction['date'], $transferDate));
                if ($transaction['date'] !== $transferDate) {
                    Log::debug('Date is not a match, continue with next transfer.');
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
                Log::debug('Source IDs are not the same.');
                unset($transferSourceIDs);

                // compare source and destination names
                $transferSource = [(string)$transfer->account_name, (string)$transfer->opposing_account_name];
                sort($transferSource);
                /** @noinspection DisconnectedForeachInstructionInspection */
                Log::debug('Comparing current transaction source+dest names', $currentSourceNames);
                Log::debug('.. with current transfer source+dest names', $transferSource);
                if ($currentSourceNames === $transferSource) {
                    // @codeCoverageIgnoreStart
                    ++$hits;
                    Log::debug(sprintf('Source names are the same! (%d)', $hits));
                    // @codeCoverageIgnoreEnd
                }
                Log::debug('Source names are not the same.');
                Log::debug(sprintf('Number of hits is %d', $hits));
                if ($hits >= self::REQUIRED_HITS) {
                    Log::debug(sprintf('Is more than %d, return true.', self::REQUIRED_HITS));

                    return true;
                }
            }
        }
        Log::debug('Is not an existing transfer, return false.');

        return false;
    }

}
