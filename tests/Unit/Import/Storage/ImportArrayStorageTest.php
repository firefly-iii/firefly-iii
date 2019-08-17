<?php
/**
 * ImportArrayStorageTest.php
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

namespace Tests\Unit\Import\Storage;

use Amount;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Import\Storage\ImportArrayStorage;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\TransactionRules\Processor;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class ImportArrayStorageTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 *
 */
class ImportArrayStorageTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }


    /**
     * Very basic storage routine. Doesn't call store()
     *
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     */
    public function testBasic(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $groupRepos   = $this->mock(TransactionGroupRepositoryInterface::class);

        $this->mock(TagRepositoryInterface::class);
        $this->mock(Processor::class);
        $this->mock(RuleRepositoryInterface::class);
        $this->mock(GroupCollectorInterface::class);
        Amount::shouldReceive('something');

        $language       = new Preference;
        $language->data = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($language)->atLeast()->once();



        // make fake job
        $job = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'a_storage' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->transactions  = [];
        $job->save();


        // mock user calls
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $groupRepos->shouldReceive('setUser')->atLeast()->once();

        // mock other calls.
        $repository->shouldReceive('getTransactions')->atLeast()->once()->andReturn([]);
        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());

        // status changes of the job.
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'storing_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'stored_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linking_to_tag']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linked_to_tag']);

        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        try {
            $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Submit a transfer. Mark it as not duplicate.
     *
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     *
     */
    public function testTransfer(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        // data to submit:
        $transactions = [
            $this->singleImportTransfer(),
        ];

        // data that is returned:
        $withdrawalGroup = $this->getRandomWithdrawalGroup();
        $tag             = $this->getRandomTag();
        $transfer        = $this->getRandomTransferAsArray();

        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $groupRepos   = $this->mock(TransactionGroupRepositoryInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $this->mock(Processor::class);
        $this->mock(RuleRepositoryInterface::class);

        Amount::shouldReceive('something');

        $language       = new Preference;
        $language->data = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($language)->atLeast()->once();


        // make fake job
        $job = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'a_storage' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->transactions  = [];
        $job->save();


        // mock user calls
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $groupRepos->shouldReceive('setUser')->atLeast()->once();
        $tagRepos->shouldReceive('setUser')->atLeast()->once();

        // mock other calls.
        $repository->shouldReceive('getTransactions')->atLeast()->once()->andReturn($transactions);
        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());

        // status changes of the job.
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'storing_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'stored_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linking_to_tag']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linked_to_tag']);

        // calls to validate and import transactions:
        $journalRepos->shouldReceive('findByHash')->withArgs([Mockery::any()])->atLeast()->once()->andReturnNull();
        $groupRepos->shouldReceive('store')->atLeast()->once()->andReturn($withdrawalGroup);
        $tagRepos->shouldReceive('store')->atLeast()->once()->andReturn($tag);
        $repository->shouldReceive('setTag')->atLeast()->once()->andReturn($job);

        // also mocks collector:
        $collector->shouldReceive('setUser')->atLeast()->once();
        $collector->shouldReceive('setTypes')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setLimit')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setGroup')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([$transfer]);


        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        try {
            $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Submit a transfer, and its not duplicate.
     *
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     *
     */
    public function testTransferNotDuplicate(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        // data to submit:
        $transactions = [
            $this->singleImportTransfer(),
        ];

        // data that is returned:
        $transferGroup = $this->getRandomTransferGroup();
        $tag           = $this->getRandomTag();
        $transfer      = $this->getRandomTransferAsArray();

        // make sure the right fields of the transfergroup and the transactions
        // are equal, so the duplicate detector is triggered.

        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $groupRepos   = $this->mock(TransactionGroupRepositoryInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $this->mock(Processor::class);
        $this->mock(RuleRepositoryInterface::class);

        Amount::shouldReceive('something');

        $language       = new Preference;
        $language->data = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($language)->atLeast()->once();


        // make fake job
        $job = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'a_storage' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->transactions  = [];
        $job->save();


        // mock user calls
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $groupRepos->shouldReceive('setUser')->atLeast()->once();
        $tagRepos->shouldReceive('setUser')->atLeast()->once();

        // mock other calls.
        $repository->shouldReceive('getTransactions')->atLeast()->once()->andReturn($transactions);
        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());

        // status changes of the job.
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'storing_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'stored_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linking_to_tag']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linked_to_tag']);

        // calls to validate and import transactions:
        $journalRepos->shouldReceive('findByHash')->withArgs([Mockery::any()])->atLeast()->once()->andReturnNull();
        $groupRepos->shouldReceive('store')->atLeast()->once()->andReturn($transferGroup);
        $tagRepos->shouldReceive('store')->atLeast()->once()->andReturn($tag);
        $repository->shouldReceive('setTag')->atLeast()->once()->andReturn($job);

        // also mocks collector:
        $collector->shouldReceive('setUser')->atLeast()->once();
        $collector->shouldReceive('setTypes')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setLimit')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setGroup')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([$transfer]);


        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        try {
            $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Submit a transfer, and the amounts match, but the rest doesn't.
     *
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     *
     */
    public function testTransferNotDuplicateAmount(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        // data to submit:
        $transactions = [
            $this->singleImportTransfer(),
        ];

        // data that is returned:
        $transferGroup = $this->getRandomTransferGroup();
        $tag           = $this->getRandomTag();
        $transfer      = $this->getRandomTransferAsArray();

        // are equal, so the duplicate detector is triggered.
        $transactions[0]['transactions'][0]['amount'] = '56.78';
        $transfer['amount']                           = '56.78';
        //$transferGroup['transactions']['amount']   = '12';
        /** @var TransactionJournal $journal */
        $journal = $transferGroup->transactionJournals->first();
        $journal->transactions->each(static function (Transaction $t) {
            if ($t->amount < 0) {
                $t->amount = '-56.78';
            }
            if ($t->amount > 0) {
                $t->amount = '56.78';
            }
            $t->save();
        });
        $transferGroup->refresh();

        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $groupRepos   = $this->mock(TransactionGroupRepositoryInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $this->mock(Processor::class);
        $this->mock(RuleRepositoryInterface::class);

        Amount::shouldReceive('something');

        $language       = new Preference;
        $language->data = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($language)->atLeast()->once();


        // make fake job
        $job = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'a_storage' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->transactions  = [];
        $job->save();


        // mock user calls
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $groupRepos->shouldReceive('setUser')->atLeast()->once();
        $tagRepos->shouldReceive('setUser')->atLeast()->once();

        // mock other calls.
        $repository->shouldReceive('getTransactions')->atLeast()->once()->andReturn($transactions);
        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());

        // status changes of the job.
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'storing_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'stored_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linking_to_tag']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linked_to_tag']);

        // calls to validate and import transactions:
        $journalRepos->shouldReceive('findByHash')->withArgs([Mockery::any()])->atLeast()->once()->andReturnNull();
        $groupRepos->shouldReceive('store')->atLeast()->once()->andReturn($transferGroup);
        $tagRepos->shouldReceive('store')->atLeast()->once()->andReturn($tag);
        $repository->shouldReceive('setTag')->atLeast()->once()->andReturn($job);

        // also mocks collector:
        $collector->shouldReceive('setUser')->atLeast()->once();
        $collector->shouldReceive('setTypes')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setLimit')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setGroup')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([$transfer]);


        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        try {
            $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Submit a transfer, and the amounts match, and the description matches, but the rest doesn't
     *
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     *
     */
    public function testTransferNotDuplicateDescr(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        // data to submit:
        $transactions = [
            $this->singleImportTransfer(),
        ];

        // data that is returned:
        $transferGroup = $this->getRandomTransferGroup();
        $tag           = $this->getRandomTag();
        $transfer      = $this->getRandomTransferAsArray();

        // are equal, so the duplicate detector is triggered.
        $transactions[0]['transactions'][0]['amount']      = '56.78';
        $transfer['amount']                                = '56.78';
        $transactions[0]['transactions'][0]['description'] = $transfer['description'];


        //$transferGroup['transactions']['amount']   = '12';
        /** @var TransactionJournal $journal */
        $journal = $transferGroup->transactionJournals->first();
        $journal->transactions->each(static function (Transaction $t) {
            if ($t->amount < 0) {
                $t->amount = '-56.78';
            }
            if ($t->amount > 0) {
                $t->amount = '56.78';
            }
            $t->save();
        });
        $transferGroup->refresh();

        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $groupRepos   = $this->mock(TransactionGroupRepositoryInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $this->mock(Processor::class);
        $this->mock(RuleRepositoryInterface::class);

        Amount::shouldReceive('something');

        $language       = new Preference;
        $language->data = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($language)->atLeast()->once();


        // make fake job
        $job = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'a_storage' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->transactions  = [];
        $job->save();


        // mock user calls
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $groupRepos->shouldReceive('setUser')->atLeast()->once();
        $tagRepos->shouldReceive('setUser')->atLeast()->once();

        // mock other calls.
        $repository->shouldReceive('getTransactions')->atLeast()->once()->andReturn($transactions);
        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());

        // status changes of the job.
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'storing_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'stored_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linking_to_tag']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linked_to_tag']);

        // calls to validate and import transactions:
        $journalRepos->shouldReceive('findByHash')->withArgs([Mockery::any()])->atLeast()->once()->andReturnNull();
        $groupRepos->shouldReceive('store')->atLeast()->once()->andReturn($transferGroup);
        $tagRepos->shouldReceive('store')->atLeast()->once()->andReturn($tag);
        $repository->shouldReceive('setTag')->atLeast()->once()->andReturn($job);

        // also mocks collector:
        $collector->shouldReceive('setUser')->atLeast()->once();
        $collector->shouldReceive('setTypes')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setLimit')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setGroup')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([$transfer]);


        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        try {
            $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Submit a transfer, and the amounts match, and the description matches,
     * and the date matches, but the rest doesn't
     *
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     *
     */
    public function testTransferNotDuplicateDate(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        // data to submit:
        $transactions = [
            $this->singleImportTransfer(),
        ];

        // data that is returned:
        $transferGroup = $this->getRandomTransferGroup();
        $tag           = $this->getRandomTag();
        $transfer      = $this->getRandomTransferAsArray();

        // are equal, so the duplicate detector is triggered.
        $transactions[0]['transactions'][0]['amount']      = '56.78';
        $transfer['amount']                                = '56.78';
        $transactions[0]['transactions'][0]['description'] = $transfer['description'];
        $transactions[0]['transactions'][0]['date']        = $transfer['date']->format('Y-m-d H:i:s');


        //$transferGroup['transactions']['amount']   = '12';
        /** @var TransactionJournal $journal */
        $journal = $transferGroup->transactionJournals->first();
        $journal->transactions->each(static function (Transaction $t) {
            if ($t->amount < 0) {
                $t->amount = '-56.78';
            }
            if ($t->amount > 0) {
                $t->amount = '56.78';
            }
            $t->save();
        });
        $transferGroup->refresh();

        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $groupRepos   = $this->mock(TransactionGroupRepositoryInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $this->mock(Processor::class);
        $this->mock(RuleRepositoryInterface::class);

        Amount::shouldReceive('something');

        $language       = new Preference;
        $language->data = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($language)->atLeast()->once();


        // make fake job
        $job = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'a_storage' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->transactions  = [];
        $job->save();


        // mock user calls
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $groupRepos->shouldReceive('setUser')->atLeast()->once();
        $tagRepos->shouldReceive('setUser')->atLeast()->once();

        // mock other calls.
        $repository->shouldReceive('getTransactions')->atLeast()->once()->andReturn($transactions);
        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());

        // status changes of the job.
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'storing_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'stored_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linking_to_tag']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linked_to_tag']);

        // calls to validate and import transactions:
        $journalRepos->shouldReceive('findByHash')->withArgs([Mockery::any()])->atLeast()->once()->andReturnNull();
        $groupRepos->shouldReceive('store')->atLeast()->once()->andReturn($transferGroup);
        $tagRepos->shouldReceive('store')->atLeast()->once()->andReturn($tag);
        $repository->shouldReceive('setTag')->atLeast()->once()->andReturn($job);

        // also mocks collector:
        $collector->shouldReceive('setUser')->atLeast()->once();
        $collector->shouldReceive('setTypes')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setLimit')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setGroup')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([$transfer]);


        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        try {
            $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }


    /**
     * Submit a transfer, and the amounts match, and the description matches,
     * and the date matches, and the accounts match, making it a duplicate.
     *
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     *
     */
    public function testTransferNotDuplicateAccounts(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        // data to submit:
        $transactions = [
            $this->singleImportTransfer(),
        ];

        // data that is returned:
        $transferGroup = $this->getRandomTransferGroup();
        $tag           = $this->getRandomTag();
        $transfer      = $this->getRandomTransferAsArray();

        // are equal, so the duplicate detector is triggered.
        $transfer['amount']                                = '56.78';
        $transfer['source_account_id']                     = 0;
        $transfer['source_account_name']                   = 'x';
        $transfer['destination_account_id']                = 0;
        $transfer['destination_account_name']              = 'x';
        $transactions[0]['transactions'][0]['amount']      = '56.78';
        $transactions[0]['transactions'][0]['description'] = $transfer['description'];
        $transactions[0]['transactions'][0]['date']        = $transfer['date']->format('Y-m-d H:i:s');


        //$transferGroup['transactions']['amount']   = '12';
        /** @var TransactionJournal $journal */
        $journal = $transferGroup->transactionJournals->first();
        $journal->transactions->each(static function (Transaction $t) {
            if ($t->amount < 0) {
                $t->amount = '-56.78';
            }
            if ($t->amount > 0) {
                $t->amount = '56.78';
            }
            $t->save();
        });
        $transferGroup->refresh();

        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $groupRepos   = $this->mock(TransactionGroupRepositoryInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $this->mock(TagRepositoryInterface::class);
        $this->mock(Processor::class);
        $this->mock(RuleRepositoryInterface::class);

        Amount::shouldReceive('something');

        $language       = new Preference;
        $language->data = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($language)->atLeast()->once();


        // make fake job
        $job = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'a_storage' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->transactions  = [];
        $job->save();


        // mock user calls
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $groupRepos->shouldReceive('setUser')->atLeast()->once();

        // mock other calls.
        $repository->shouldReceive('getTransactions')->atLeast()->once()->andReturn($transactions);
        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());

        // status changes of the job.
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'storing_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'stored_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linking_to_tag']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linked_to_tag']);

        // calls to validate and import transactions:
        $journalRepos->shouldReceive('findByHash')->withArgs([Mockery::any()])->atLeast()->once()->andReturnNull();

        // also mocks collector:
        $collector->shouldReceive('setUser')->atLeast()->once();
        $collector->shouldReceive('setTypes')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setLimit')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setPage')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withAccountInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getExtractedJournals')->atLeast()->once()->andReturn([$transfer]);

        // since a duplicate was found, must register error:
        $repository->shouldReceive('addErrorMessage')->atLeast()->once()->withArgs([Mockery::any(), sprintf('Row #0 ("%s") could not be imported. It already exists.', $transfer['description'])]);


        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        try {
            $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }


    /**
     * Same as testBasic but submits the minimum amount of data required to store a transaction.
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     *
     */
    public function testSimple(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        // data to submit:
        $transactions = [
            $this->singleImportWithdrawal(),
        ];

        // data that is returned:
        $withdrawalGroup = $this->getRandomWithdrawalGroup();
        $tag             = $this->getRandomTag();

        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $groupRepos   = $this->mock(TransactionGroupRepositoryInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);

        $this->mock(Processor::class);
        $this->mock(RuleRepositoryInterface::class);
        $this->mock(GroupCollectorInterface::class);
        Amount::shouldReceive('something');

        $language       = new Preference;
        $language->data = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($language)->atLeast()->once();


        // make fake job
        $job = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'a_storage' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->transactions  = [];
        $job->save();


        // mock user calls
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $groupRepos->shouldReceive('setUser')->atLeast()->once();
        $tagRepos->shouldReceive('setUser')->atLeast()->once();

        // mock other calls.
        $repository->shouldReceive('getTransactions')->atLeast()->once()->andReturn($transactions);
        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());

        // status changes of the job.
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'storing_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'stored_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linking_to_tag']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linked_to_tag']);

        // calls to validate and import transactions:
        $journalRepos->shouldReceive('findByHash')->withArgs([Mockery::any()])->atLeast()->once()->andReturnNull();
        $groupRepos->shouldReceive('store')->atLeast()->once()->andReturn($withdrawalGroup);
        $tagRepos->shouldReceive('store')->atLeast()->once()->andReturn($tag);
        $repository->shouldReceive('setTag')->atLeast()->once()->andReturn($job);


        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        try {
            $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Same as testBasic but submits the minimum amount of data required to store a transaction.
     *
     * The one journal in the list is a duplicate.
     *
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     *
     */
    public function testSimpleDuplicate(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        // data to submit:
        $transactions = [
            $this->singleImportWithdrawal(),
        ];

        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $groupRepos   = $this->mock(TransactionGroupRepositoryInterface::class);

        $this->mock(TagRepositoryInterface::class);
        $this->mock(Processor::class);
        $this->mock(RuleRepositoryInterface::class);
        $this->mock(GroupCollectorInterface::class);
        Amount::shouldReceive('something');

        $language       = new Preference;
        $language->data = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($language)->atLeast()->once();

        $meta                         = new TransactionJournalMeta;
        $meta->transaction_journal_id = 1;


        // make fake job
        $job = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'a_storage' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->transactions  = [];
        $job->save();


        // mock user calls
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $groupRepos->shouldReceive('setUser')->atLeast()->once();

        // mock other calls.
        $repository->shouldReceive('getTransactions')->atLeast()->once()->andReturn($transactions);
        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());

        // status changes of the job.
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'storing_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'stored_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linking_to_tag']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linked_to_tag']);

        // calls to validate and import transactions:
        $journalRepos->shouldReceive('findByHash')->withArgs([Mockery::any()])->atLeast()->once()->andReturn($meta);

        // errors because of duplicate:
        $repository->shouldReceive('addErrorMessage')->atLeast()->once()
                   ->withArgs([Mockery::any(), 'Row #0 ("") could not be imported. It already exists.']);


        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        try {
            $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * Same as testBasic but submits the minimum amount of data required to store a transaction.
     *
     * Also applies the rules, but there are none.
     *
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     *
     */
    public function testSimpleApplyNoRules(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        // data to submit:
        $transactions = [
            $this->singleImportWithdrawal(),
        ];

        // data that is returned:
        $withdrawalGroup = $this->getRandomWithdrawalGroup();
        $tag             = $this->getRandomTag();

        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $groupRepos   = $this->mock(TransactionGroupRepositoryInterface::class);
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);

        $this->mock(Processor::class);

        $this->mock(GroupCollectorInterface::class);
        Amount::shouldReceive('something');

        $language       = new Preference;
        $language->data = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($language)->atLeast()->once();


        // make fake job
        $job = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'a_storage' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'apply-rules' => true,
        ];
        $job->transactions  = [];
        $job->save();


        // mock user calls
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $groupRepos->shouldReceive('setUser')->atLeast()->once();
        $tagRepos->shouldReceive('setUser')->atLeast()->once();
        $ruleRepos->shouldReceive('setUser')->atLeast()->once();

        // mock other calls.
        $repository->shouldReceive('getTransactions')->atLeast()->once()->andReturn($transactions);
        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());

        // status changes of the job.
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'storing_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'stored_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linking_to_tag']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linked_to_tag']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'applying_rules']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'rules_applied']);


        // calls to validate and import transactions:
        $journalRepos->shouldReceive('findByHash')->withArgs([Mockery::any()])->atLeast()->once()->andReturnNull();
        $groupRepos->shouldReceive('store')->atLeast()->once()->andReturn($withdrawalGroup);
        $tagRepos->shouldReceive('store')->atLeast()->once()->andReturn($tag);
        $repository->shouldReceive('setTag')->atLeast()->once()->andReturn($job);

        // calls for application of rules, but returns NO rules.
        $ruleRepos->shouldReceive('getForImport')->once()->andReturn(new Collection);

        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        try {
            $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }


    /**
     * Same as testBasic but submits the minimum amount of data required to store a transaction.
     *
     * Also applies the rules, but there are none.
     *
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     *
     */
    public function testSimpleApplyOneRules(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        // data to submit:
        $transactions = [
            $this->singleImportWithdrawal(),
        ];

        // data that is returned:
        $withdrawalGroup = $this->getRandomWithdrawalGroup();
        $tag             = $this->getRandomTag();
        $rule            = $this->getRandomRule();

        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $groupRepos   = $this->mock(TransactionGroupRepositoryInterface::class);
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $processor = $this->mock(Processor::class);

        $this->mock(GroupCollectorInterface::class);
        Amount::shouldReceive('something');

        $language       = new Preference;
        $language->data = 'en_US';
        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'language', 'en_US'])->andReturn($language)->atLeast()->once();


        // make fake job
        $job = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'a_storage' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [
            'apply-rules' => true,
        ];
        $job->transactions  = [];
        $job->save();


        // mock user calls
        $repository->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $groupRepos->shouldReceive('setUser')->atLeast()->once();
        $tagRepos->shouldReceive('setUser')->atLeast()->once();
        $ruleRepos->shouldReceive('setUser')->atLeast()->once();

        // mock other calls.
        $repository->shouldReceive('getTransactions')->atLeast()->once()->andReturn($transactions);
        Preferences::shouldReceive('mark')->atLeast()->once()->withNoArgs();
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($this->user());

        // status changes of the job.
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'storing_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'stored_data']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linking_to_tag']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'linked_to_tag']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'applying_rules']);
        $repository->shouldReceive('setStatus')->atLeast()->once()->withArgs([Mockery::any(), 'rules_applied']);


        // calls to validate and import transactions:
        $journalRepos->shouldReceive('findByHash')->withArgs([Mockery::any()])->atLeast()->once()->andReturnNull();
        $groupRepos->shouldReceive('store')->atLeast()->once()->andReturn($withdrawalGroup);
        $tagRepos->shouldReceive('store')->atLeast()->once()->andReturn($tag);
        $repository->shouldReceive('setTag')->atLeast()->once()->andReturn($job);

        // calls for application of rules, but returns 1 rules.
        $ruleRepos->shouldReceive('getForImport')->once()->andReturn(new Collection([$rule]));
        $processor->shouldReceive('make')->atLeast()->once();
        $processor->shouldReceive('handleTransactionJournal')->atLeast()->once();

        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        try {
            $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @return array
     */
    private function singleImportWithdrawal(): array
    {
        return
            [
                'type'               => 'withdrawal',
                'tags'               => '',
                'user'               => $this->user()->id,

                // all custom fields:
                'internal_reference' => null,
                'notes'              => null,

                // journal data:
                'description'        => 'Some TEST withdrawal #1',
                'piggy_bank_id'      => null,
                'piggy_bank_name'    => null,
                'bill_id'            => null,
                'bill_name'          => null,

                // transaction data:
                'transactions'       => [
                    [
                        'date'                  => '2019-01-01',
                        'type'                  => 'withdrawal',
                        'currency_id'           => null,
                        'currency_code'         => 'EUR',
                        'description'           => null,
                        'amount'                => '12.34',
                        'budget_id'             => null,
                        'budget_name'           => null,
                        'category_id'           => null,
                        'category_name'         => null,
                        'source_id'             => null,
                        'source_name'           => 'Checking Account',
                        'destination_id'        => null,
                        'destination_name'      => 'Random TEST expense account #2',
                        'foreign_currency_id'   => null,
                        'foreign_currency_code' => null,
                        'foreign_amount'        => null,
                        'reconciled'            => false,
                        'identifier'            => 0,
                    ],
                ],
            ];
    }

    /**
     * @return array
     */
    private function singleImportTransfer(): array
    {
        return
            [
                'type'               => 'transfer',
                'tags'               => '',
                'user'               => $this->user()->id,

                // all custom fields:
                'internal_reference' => null,
                'notes'              => null,

                // journal data:
                'description'        => 'Some TEST transfer #1',
                'piggy_bank_id'      => null,
                'piggy_bank_name'    => null,
                'bill_id'            => null,
                'bill_name'          => null,

                // transaction data:
                'transactions'       => [
                    [
                        'date'                  => '2019-01-01',
                        'type'                  => 'transfer',
                        'currency_id'           => null,
                        'currency_code'         => 'EUR',
                        'description'           => null,
                        'amount'                => '12.34',
                        'budget_id'             => null,
                        'budget_name'           => null,
                        'category_id'           => null,
                        'category_name'         => null,
                        'source_id'             => null,
                        'source_name'           => 'Checking Account',
                        'destination_id'        => null,
                        'destination_name'      => 'Random TEST expense account #2',
                        'foreign_currency_id'   => null,
                        'foreign_currency_code' => null,
                        'foreign_amount'        => null,
                        'reconciled'            => false,
                        'identifier'            => 0,
                    ],
                ],
            ];
    }
}
