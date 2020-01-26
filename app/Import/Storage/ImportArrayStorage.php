<?php

/**
 * ImportArrayStorage.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Storage;

use Carbon\Carbon;
use DB;
use Exception;
use FireflyIII\Events\RequestedReportOnJournals;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Rule;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\TransactionRules\Engine\RuleEngine;
use FireflyIII\TransactionRules\Processor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Log;

/**
 * Creates new transactions based on arrays.
 *
 * Class ImportArrayStorage
 *
 */
class ImportArrayStorage
{
    /** @var int Number of hits required for a transfer to match. */
    private const REQUIRED_HITS = 4;
    /** @var bool Check for transfers during import. */
    private $checkForTransfers = false;
    /** @var TransactionGroupRepositoryInterface */
    private $groupRepos;
    /** @var ImportJob The import job */
    private $importJob;
    /** @var JournalRepositoryInterface Journal repository for storage. */
    private $journalRepos;
    /** @var string */
    private $language = 'en_US';
    /** @var ImportJobRepositoryInterface Import job repository */
    private $repository;
    /** @var array The transfers the user already has. */
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

        $this->groupRepos = app(TransactionGroupRepositoryInterface::class);
        $this->groupRepos->setUser($importJob->user);

        // get language of user.
        /** @var Preference $pref */
        $pref           = app('preferences')->getForUser($importJob->user, 'language', config('firefly.default_language', 'en_US'));
        $this->language = $pref->data;

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
        $collection = $this->storeGroupArray();
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
        Log::debug('Now in applyRules()');

        /** @var RuleEngine $ruleEngine */
        $ruleEngine = app(RuleEngine::class);
        $ruleEngine->setUser($this->importJob->user);
        $ruleEngine->setAllRules(true);

        // for this call, the rule engine only includes "store" rules:
        $ruleEngine->setTriggerMode(RuleEngine::TRIGGER_STORE);
        Log::debug('Start of engine loop');
        foreach ($collection as $group) {
            $this->applyRulesGroup($ruleEngine, $group);
        }
        Log::debug('End of engine loop.');
    }

    /**
     * @param RuleEngine       $ruleEngine
     * @param TransactionGroup $group
     */
    private function applyRulesGroup(RuleEngine $ruleEngine, TransactionGroup $group): void
    {
        Log::debug(sprintf('Processing group #%d', $group->id));
        foreach ($group->transactionJournals as $journal) {
            Log::debug(sprintf('Processing journal #%d from group #%d', $journal->id, $group->id));
            $ruleEngine->processTransactionJournal($journal);
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
        foreach ($array as $index => $group) {

            foreach ($group['transactions'] as $transaction) {
                if (strtolower(TransactionType::TRANSFER) === strtolower($transaction['type'])) {
                    $count++;
                    Log::debug(sprintf('Row #%d is a transfer, increase count to %d', $index + 1, $count));
                }
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
     * @param array $group
     *
     * @return bool
     */
    private function duplicateDetected(int $index, array $group): bool
    {
        Log::debug(sprintf('Now in duplicateDetected(%d)', $index));
        $transactions = $group['transactions'] ?? [];
        foreach ($transactions as $transaction) {
            $hash       = $this->getHash($transaction);
            $existingId = $this->hashExists($hash);
            if (null !== $existingId) {
                $message = (string)trans('import.duplicate_row', ['row' => $index, 'description' => $transaction['description']]);
                $this->logDuplicateObject($transaction, $existingId);
                $this->repository->addErrorMessage($this->importJob, $message);

                return true;
            }

            // do transfer detection:
            if ($this->checkForTransfers && $this->transferExists($transaction)) {
                $message = (string)trans('import.duplicate_row', ['row' => $index, 'description' => $transaction['description']]);
                $this->logDuplicateTransfer($transaction);
                $this->repository->addErrorMessage($this->importJob, $message);

                return true;
            }
        }

        return false;
    }

    /**
     * Get hash of transaction.
     *
     * @param array $transaction
     *
     * @return string
     */
    private function getHash(array $transaction): string
    {
        unset($transaction['import_hash_v2'], $transaction['original_source']);
        $json = json_encode($transaction, JSON_THROW_ON_ERROR);
        if (false === $json) {
            // @codeCoverageIgnoreStart
            /** @noinspection ForgottenDebugOutputInspection */
            Log::error('Could not encode import array.', $transaction);
            try {
                $json = random_int(1, 10000);
            } catch (Exception $e) {
                // seriously?
                Log::error(sprintf('random_int() just failed. I want a medal: %s', $e->getMessage()));
            }
            // @codeCoverageIgnoreEnd
        }

        $hash = hash('sha256', $json);
        Log::debug(sprintf('The hash is: %s', $hash), $transaction);

        return $hash;
    }

    /**
     * @param TransactionGroup $transactionGroup
     *
     * @return array
     */
    private function getTransactionFromJournal(TransactionGroup $transactionGroup): array
    {
        // collect transactions using the journal collector
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setUser($this->importJob->user);
        $collector->setGroup($transactionGroup);

        return $collector->getExtractedJournals();
    }

    /**
     * Get the users transfers, so they can be compared to whatever the user is trying to import.
     */
    private function getTransfers(): void
    {
        Log::debug('Now in getTransfers()');
        app('preferences')->mark();

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setUser($this->importJob->user);
        $collector
            ->setTypes([TransactionType::TRANSFER])->setLimit(10000)->setPage(1)
            ->withAccountInformation();
        $this->transfers = $collector->getExtractedJournals();
        Log::debug(sprintf('Count of getTransfers() is %d', count($this->transfers)));
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
        Log::debug('Looping groups...');

        // TODO double loop.

        /** @var TransactionGroup $group */
        foreach ($collection as $group) {
            Log::debug(sprintf('Looping journals in group #%d', $group->id));
            /** @var TransactionJournal $journal */
            $journalIds = $group->transactionJournals->pluck('id')->toArray();
            $tagId      = $tag->id;
            foreach ($journalIds as $journalId) {
                Log::debug(sprintf('Linking journal #%d to tag #%d...', $journalId, $tagId));
                // @codeCoverageIgnoreStart
                try {
                    DB::table('tag_transaction_journal')->insert(['transaction_journal_id' => $journalId, 'tag_id' => $tagId]);
                } catch (QueryException $e) {
                    Log::error(sprintf('Could not link journal #%d to tag #%d because: %s', $journalId, $tagId, $e->getMessage()));
                }
                // @codeCoverageIgnoreEnd
            }
            Log::info(sprintf('Linked %d journals to tag #%d ("%s")', $collection->count(), $tag->id, $tag->tag));
        }
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
     * @param int   $index
     * @param array $group
     *
     * @return TransactionGroup|null
     */
    private function storeGroup(int $index, array $group): ?TransactionGroup
    {
        Log::debug(sprintf('Going to store entry #%d', $index + 1));

        // do some basic error catching.
        foreach ($group['transactions'] as $groupIndex => $transaction) {
            $group['transactions'][$groupIndex]['date']        = Carbon::parse($transaction['date'], config('app.timezone'));
            $group['transactions'][$groupIndex]['description'] = '' === $transaction['description'] ? '(empty description)' : $transaction['description'];
        }

        // do duplicate detection!
        if ($this->duplicateDetected($index, $group)) {
            Log::warning(sprintf('Row #%d seems to be a imported already and will be ignored.', $index));

            return null;
        }

        // store the group
        try {
            $newGroup = $this->groupRepos->store($group);
            // @codeCoverageIgnoreStart
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            $this->repository->addErrorMessage($this->importJob, sprintf('Row #%d could not be imported. %s', $index, $e->getMessage()));

            return null;
        }
        // @codeCoverageIgnoreEnd
        Log::debug(sprintf('Stored as group #%d', $newGroup->id));

        // add to collection of transfers, if necessary:
        if ('transfer' === strtolower($group['transactions'][0]['type'])) {
            $journals = $this->getTransactionFromJournal($newGroup);
            Log::debug('We just stored a transfer, so add the journal to the list of transfers.');
            foreach ($journals as $newJournal) {
                $this->transfers[] = $newJournal;
            }
            Log::debug(sprintf('List length is now %d', count($this->transfers)));
        }

        return $newGroup;
    }

    /**
     * Store array as journals.
     *
     * @return Collection
     * @throws FireflyException
     *
     */
    private function storeGroupArray(): Collection
    {
        /** @var array $array */
        $array = $this->repository->getTransactions($this->importJob);
        $count = count($array);

        Log::notice(sprintf('Will now store the groups. Count of groups is %d.', $count));
        Log::notice('Going to store...');

        $collection = new Collection;
        foreach ($array as $index => $group) {
            Log::debug(sprintf('Now store #%d', $index + 1));
            $result = $this->storeGroup($index, $group);
            if (null !== $result) {
                $collection->push($result);
            }
        }
        Log::notice(sprintf('Done storing. Firefly III has stored %d transactions.', $collection->count()));

        return $collection;
    }

    /**
     * Check if a transfer exists.
     *
     * @param array $transaction
     *
     * @return bool
     *
     */
    private function transferExists(array $transaction): bool
    {
        Log::debug('transferExists() Check if transaction is a double transfer.');

        // how many hits do we need?
        Log::debug(sprintf('System has %d existing transfers', count($this->transfers)));
        // loop over each split:

        // check if is a transfer
        if (strtolower(TransactionType::TRANSFER) !== strtolower($transaction['type'])) {
            // @codeCoverageIgnoreStart
            Log::debug(sprintf('Is a %s, not a transfer so no.', $transaction['type']));

            return false;
            // @codeCoverageIgnoreEnd
        }


        Log::debug(sprintf('Required hits for transfer comparison is %d', self::REQUIRED_HITS));

        // get the amount:
        /** @noinspection UnnecessaryCastingInspection */
        $amount = (string)($transaction['amount'] ?? '0');
        if (bccomp($amount, '0') === -1) {
            $amount = bcmul($amount, '-1'); // @codeCoverageIgnore
        }

        // get the description:
        //$description = '' === (string)$transaction['description'] ? $transaction['description'] : $transaction['description'];
        $description = (string)$transaction['description'];

        // get the source and destination ID's:
        $transactionSourceIDs = [(int)$transaction['source_id'], (int)$transaction['destination_id']];
        sort($transactionSourceIDs);

        // get the source and destination names:
        $transactionSourceNames = [(string)$transaction['source_name'], (string)$transaction['destination_name']];
        sort($transactionSourceNames);

        // then loop all transfers:
        /** @var array $transfer */
        foreach ($this->transfers as $transfer) {
            // number of hits for this split-transfer combination:
            $hits = 0;
            Log::debug(sprintf('Now looking at transaction journal #%d', $transfer['transaction_journal_id']));
            // compare amount:
            $originalAmount = app('steam')->positive($transfer['amount']);
            Log::debug(sprintf('Amount %s compared to %s', $amount, $originalAmount));
            if (0 !== bccomp($amount, $originalAmount)) {
                Log::debug('Amount is not a match, continue with next transfer.');
                continue;
            }
            ++$hits;
            Log::debug(sprintf('Comparison is a hit! (%s)', $hits));

            // compare description:
            // $comparison = '(empty description)' === $transfer['description'] ? '' : $transfer['description'];
            $comparison = $transfer['description'];
            Log::debug(sprintf('Comparing "%s" to "%s" (original: "%s")', $description, $transfer['description'], $comparison));
            if ($description !== $comparison) {
                Log::debug('Description is not a match, continue with next transfer.');
                continue; // @codeCoverageIgnore
            }
            ++$hits;
            Log::debug(sprintf('Comparison is a hit! (%s)', $hits));

            // compare date:
            $transferDate    = $transfer['date']->format('Y-m-d H:i:s');
            $transactionDate = $transaction['date']->format('Y-m-d H:i:s');
            Log::debug(sprintf('Comparing dates "%s" to "%s"', $transactionDate, $transferDate));
            if ($transactionDate !== $transferDate) {
                Log::debug('Date is not a match, continue with next transfer.');
                continue; // @codeCoverageIgnore
            }
            ++$hits;
            Log::debug(sprintf('Comparison is a hit! (%s)', $hits));

            // compare source and destination id's
            $transferSourceIDs = [(int)$transfer['source_account_id'], (int)$transfer['destination_account_id']];
            sort($transferSourceIDs);
            /** @noinspection DisconnectedForeachInstructionInspection */
            Log::debug('Comparing current transaction source+dest IDs', $transactionSourceIDs);
            Log::debug('.. with current transfer source+dest IDs', $transferSourceIDs);
            if ($transactionSourceIDs === $transferSourceIDs) {
                ++$hits;
                Log::debug(sprintf('Source IDs are the same! (%d)', $hits));
            }
            if ($transactionSourceIDs !== $transferSourceIDs) {
                Log::debug('Source IDs are not the same.');
            }
            unset($transferSourceIDs);

            // compare source and destination names
            $transferSource = [(string)($transfer['source_account_name'] ?? ''), (string)($transfer['destination_account_name'] ?? '')];
            sort($transferSource);
            /** @noinspection DisconnectedForeachInstructionInspection */
            Log::debug('Comparing current transaction source+dest names', $transactionSourceNames);
            Log::debug('.. with current transfer source+dest names', $transferSource);
            if ($transactionSourceNames === $transferSource && $transferSource !== ['', '']) {
                // @codeCoverageIgnoreStart
                ++$hits;
                Log::debug(sprintf('Source names are the same! (%d)', $hits));
                // @codeCoverageIgnoreEnd
            }
            if ($transactionSourceNames !== $transferSource) {
                Log::debug('Source names are not the same.');
            }

            Log::debug(sprintf('Number of hits is %d', $hits));
            if ($hits >= self::REQUIRED_HITS) {
                Log::debug(sprintf('Is more than %d, return true.', self::REQUIRED_HITS));

                return true;
            }
        }
        Log::debug('Is not an existing transfer, return false.');

        return false;
    }

}
