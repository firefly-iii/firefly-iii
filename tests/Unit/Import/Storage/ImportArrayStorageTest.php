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

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\TransactionCollector;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Import\Storage\ImportArrayStorage;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Rule;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class ImportArrayStorageTest
 */
class ImportArrayStorageTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }


    /**
     * Very basic storage routine. Doesn't call store()
     *
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     */
    public function testBasic(): void
    {
        // mock stuff
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        // make fake job
        $job = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'a_storage' . random_int(1, 10000);
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->transactions  = [];
        $job->save();


        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $journalRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('getTransactions')->once()->andReturn([]);

        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
    }

    /**
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     */
    public function testBasicStoreDoubleTransferWithRules(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('findNull')->once()->andReturn($this->user());

        // get a transfer:
        /** @var TransactionJournal $transfer */
        $transfer = $this->user()->transactionJournals()
                         ->inRandomOrder()->where('transaction_type_id', 3)
                         ->first();

        // get transfer as a collection, so the compare routine works.
        $transactionCollector = new TransactionCollector;
        $transactionCollector->setUser($this->user());
        $transactionCollector->setJournals(new Collection([$transfer]));
        $transferCollection = $transactionCollector->withOpposingAccount()->getTransactions();

        // make fake job
        $job = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'h_storage' . random_int(1, 10000);
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = ['apply-rules' => true];
        $job->transactions  = ['count' => 3];
        $transactions       = [$this->singleTransfer(), $this->singleWithdrawal(), $this->basedOnTransfer($transfer)];
        $job->save();

        // get some stuff:
        $tag                      = $this->user()->tags()->inRandomOrder()->first();
        $journal                  = $this->user()->transactionJournals()->inRandomOrder()->first();
        $ruleOne                  = new Rule;
        $ruleOne->stop_processing = false;
        $ruleTwo                  = new Rule;
        $ruleTwo->stop_processing = true;

        // mock stuff
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('getTransactions')->times(2)->andReturn($transactions);

        // mock calls:
        $collector->shouldReceive('setUser')->times(2);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withAnyArgs();
        $ruleRepos->shouldReceive('setUser')->once();
        $tagRepos->shouldReceive('setUser')->once();
        $tagRepos->shouldReceive('store')->once()->andReturn($tag);
        $repository->shouldReceive('setTag')->once();
        $ruleRepos->shouldReceive('getForImport')->andReturn(new Collection([$ruleOne, $ruleTwo]));
        $journalRepos->shouldReceive('setUser')->once();
        $journalRepos->shouldReceive('store')->twice()->andReturn($journal);
        $journalRepos->shouldReceive('findByHash')->andReturn(null)->times(5);
        $repository->shouldReceive('addErrorMessage')->withArgs(
            [Mockery::any(), 'Row #2 ("' . $transfer->description . '") could not be imported. Such a transfer already exists.']
        )->once();

        // mock collector so it will return some transfers:
        $collector->shouldReceive('setAllAssetAccounts')->times(1)->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::TRANSFER]])->once()->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->times(2)->andReturnSelf();
        $collector->shouldReceive('ignoreCache')->once()->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->once()->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($transferCollection);

        // set journals for the return method.
        $collector->shouldReceive('setJournals')->andReturnSelf();
        $collector->shouldReceive('addFilter')->andReturnSelf();


        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        $result = new Collection;
        try {
            $result = $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(2, $result);
    }

    /**
     * Two withdrawals, one of which is duplicated.
     *
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     */
    public function testBasicStoreIsDouble(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('findNull')->once()->andReturn($this->user());

        // make fake job
        $transactions = [$this->singleWithdrawal(), $this->singleWithdrawal()];
        $job          = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'b_storage' . random_int(1, 10000);
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = ['apply-rules' => true];
        $job->transactions  = ['count' => 2];
        $job->save();

        // get some stuff:
        $tag                          = $this->user()->tags()->inRandomOrder()->first();
        $journal                      = $this->user()->transactionJournals()->inRandomOrder()->first();
        $ruleOne                      = new Rule;
        $ruleOne->stop_processing     = false;
        $ruleTwo                      = new Rule;
        $ruleTwo->stop_processing     = true;
        $meta                         = new TransactionJournalMeta;
        $meta->transaction_journal_id = 3;

        // mock stuff
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('getTransactions')->times(2)->andReturn($transactions);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withAnyArgs();
        $ruleRepos->shouldReceive('setUser')->once();
        $tagRepos->shouldReceive('setUser')->once();
        $tagRepos->shouldReceive('store')->once()->andReturn($tag);
        $repository->shouldReceive('setTag')->once();
        $ruleRepos->shouldReceive('getForImport')->andReturn(new Collection([$ruleOne, $ruleTwo]));
        $journalRepos->shouldReceive('setUser')->once();
        $journalRepos->shouldReceive('store')->once()->andReturn($journal);
        $journalRepos->shouldReceive('findByHash')->andReturn(null, $meta, null)->times(3);
        $repository->shouldReceive('addErrorMessage')->once()
                   ->withArgs([Mockery::any(), 'Row #1 ("' . $transactions[1]['description'] . '") could not be imported. It already exists.']);

        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        $result = new Collection;
        try {
            $result = $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(1, $result);
    }

    /**
     * Very basic storage routine. Call store with no data.
     *
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     */
    public function testBasicStoreNothing(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('findNull')->once()->andReturn($this->user());

        // make fake job
        $job = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'c_storage' . random_int(1, 10000);
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->transactions  = [];
        $job->save();

        // mock stuff
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withAnyArgs();
        $journalRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('getTransactions')->times(2)->andReturn([]);

        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        $result = new Collection;
        try {
            $result = $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $result);
    }

    /**
     * Call store with no data, also assume rules.
     *
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     */
    public function testBasicStoreNothingWithRules(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('findNull')->once()->andReturn($this->user());

        // make fake job
        $job = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'd_storage' . random_int(1, 10000);
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = ['apply-rules' => true];
        $job->transactions  = [];
        $job->save();

        // mock stuff
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('getTransactions')->times(2)->andReturn([]);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withAnyArgs();
        $ruleRepos->shouldReceive('setUser')->once();
        $ruleRepos->shouldReceive('getForImport')->andReturn(new Collection);
        $journalRepos->shouldReceive('setUser')->once();

        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        $result = new Collection;
        try {
            $result = $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(0, $result);
    }

    /**
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     */
    public function testBasicStoreSingleWithNoRules(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('findNull')->once()->andReturn($this->user());

        // make fake job
        $transactions = [$this->singleWithdrawal()];
        $job          = new ImportJob;
        $job->user()->associate($this->user());
        $job->key           = 'e_storage' . random_int(1, 10000);
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = ['apply-rules' => true];
        $job->transactions  = ['count' => 1];
        $job->save();

        // get some stuff:
        $tag     = $this->user()->tags()->inRandomOrder()->first();
        $journal = $this->user()->transactionJournals()->inRandomOrder()->first();

        // mock stuff
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withAnyArgs();
        $ruleRepos->shouldReceive('setUser')->once();
        $tagRepos->shouldReceive('setUser')->once();
        $tagRepos->shouldReceive('store')->once()->andReturn($tag);
        $repository->shouldReceive('setTag')->once();
        $ruleRepos->shouldReceive('getForImport')->andReturn(new Collection);
        $journalRepos->shouldReceive('setUser')->once();
        $journalRepos->shouldReceive('store')->once()->andReturn($journal);
        $journalRepos->shouldReceive('findByHash')->andReturn(null)->times(2);
        $repository->shouldReceive('getTransactions')->times(2)->andReturn($transactions);

        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        $result = new Collection;
        try {
            $result = $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(1, $result);
    }

    /**
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     */
    public function testBasicStoreSingleWithRules(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('findNull')->once()->andReturn($this->user());

        // make fake job
        $job          = new ImportJob;
        $transactions = [$this->singleWithdrawal()];
        $job->user()->associate($this->user());
        $job->key           = 'f_storage' . random_int(1, 10000);
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = ['apply-rules' => true];
        $job->transactions  = ['count' => 1];
        $job->save();

        // get some stuff:
        $tag                      = $this->user()->tags()->inRandomOrder()->first();
        $journal                  = $this->user()->transactionJournals()->inRandomOrder()->first();
        $ruleOne                  = new Rule;
        $ruleOne->stop_processing = false;
        $ruleTwo                  = new Rule;
        $ruleTwo->stop_processing = true;

        // mock stuff
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withAnyArgs();
        $ruleRepos->shouldReceive('setUser')->once();
        $tagRepos->shouldReceive('setUser')->once();
        $tagRepos->shouldReceive('store')->once()->andReturn($tag);
        $repository->shouldReceive('setTag')->once();
        $ruleRepos->shouldReceive('getForImport')->andReturn(new Collection([$ruleOne, $ruleTwo]));
        $journalRepos->shouldReceive('setUser')->once();
        $journalRepos->shouldReceive('store')->once()->andReturn($journal);
        $journalRepos->shouldReceive('findByHash')->andReturn(null)->times(2);
        $repository->shouldReceive('getTransactions')->times(2)->andReturn($transactions);


        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        $result = new Collection;
        try {
            $result = $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(1, $result);
    }

    /**
     * @covers \FireflyIII\Import\Storage\ImportArrayStorage
     */
    public function testBasicStoreTransferWithRules(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('findNull')->once()->andReturn($this->user());

        // make fake job
        $job = new ImportJob;
        $transactions = [$this->singleTransfer(), $this->singleWithdrawal()];
        $job->user()->associate($this->user());
        $job->key           = 'g_storage' . random_int(1, 10000);
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = ['apply-rules' => true];
        $job->transactions  = ['count' => 2];
        $job->save();

        // get a transfer:
        $transfer = $this->user()->transactionJournals()
                         ->inRandomOrder()->where('transaction_type_id', 3)
                         ->first();

        // get transfer as a collection, so the compare routine works.
        $transactionCollector = new TransactionCollector;
        $transactionCollector->setUser($this->user());
        $transactionCollector->setJournals(new Collection([$transfer]));
        $transferCollection = $transactionCollector->withOpposingAccount()->getTransactions();

        // get some stuff:
        $tag                      = $this->user()->tags()->inRandomOrder()->first();
        $journal                  = $this->user()->transactionJournals()->inRandomOrder()->first();
        $ruleOne                  = new Rule;
        $ruleOne->stop_processing = false;
        $ruleTwo                  = new Rule;
        $ruleTwo->stop_processing = true;

        // mock stuff
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);
        $ruleRepos    = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        // mock calls:
        $collector->shouldReceive('setUser')->times(2); // twice for transfer
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withAnyArgs();
        $ruleRepos->shouldReceive('setUser')->once();
        $tagRepos->shouldReceive('setUser')->once();
        $tagRepos->shouldReceive('store')->once()->andReturn($tag);
        $repository->shouldReceive('setTag')->once();
        $ruleRepos->shouldReceive('getForImport')->andReturn(new Collection([$ruleOne, $ruleTwo]));
        $journalRepos->shouldReceive('setUser')->once();
        $journalRepos->shouldReceive('store')->twice()->andReturn($journal);
        $journalRepos->shouldReceive('findByHash')->andReturn(null)->times(4);
        $repository->shouldReceive('getTransactions')->times(2)->andReturn($transactions);

        // mock collector so it will return some transfers:
        $collector->shouldReceive('setAllAssetAccounts')->once()->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::TRANSFER]])->once()->andReturnSelf();
        $collector->shouldReceive('ignoreCache')->once()->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->times(2)->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->once()->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($transferCollection);

        // set journals for the return method.
        $collector->shouldReceive('setJournals')->andReturnSelf();
        $collector->shouldReceive('addFilter')->andReturnSelf();

        $storage = new ImportArrayStorage;
        $storage->setImportJob($job);
        $result = new Collection;
        try {
            $result = $storage->store();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertCount(2, $result);
    }

    /**
     * @param TransactionJournal $transfer
     *
     * @return array
     */
    private function basedOnTransfer(TransactionJournal $transfer): array
    {
        $destination = $transfer->transactions()->where('amount', '>', 0)->first();
        $source      = $transfer->transactions()->where('amount', '<', 0)->first();
        $amount      = $destination->amount;

        return
            [
                'type'               => 'transfer',
                'date'               => $transfer->date->format('Y-m-d H:i:s'),
                'tags'               => '',
                'user'               => $this->user()->id,

                // all custom fields:
                'internal_reference' => null,
                'notes'              => null,

                // journal data:
                'description'        => $transfer->description,
                'piggy_bank_id'      => null,
                'piggy_bank_name'    => null,
                'bill_id'            => null,
                'bill_name'          => null,

                // transaction data:
                'transactions'       => [
                    [
                        'currency_id'           => null,
                        'currency_code'         => 'EUR',
                        'description'           => null,
                        'amount'                => $amount,
                        'budget_id'             => null,
                        'budget_name'           => null,
                        'category_id'           => null,
                        'category_name'         => null,
                        'source_id'             => $source->account_id,
                        'source_name'           => null,
                        'destination_id'        => $destination->account_id,
                        'destination_name'      => null,
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
     * @throws \Exception
     */
    private function singleTransfer(): array
    {
        return
            [
                'type'               => 'transfer',
                'date'               => Carbon::create()->format('Y-m-d'),
                'tags'               => '',
                'user'               => $this->user()->id,

                // all custom fields:
                'internal_reference' => null,
                'notes'              => null,

                // journal data:
                'description'        => 'Some TEST transfer #' . random_int(1, 10000),
                'piggy_bank_id'      => null,
                'piggy_bank_name'    => null,
                'bill_id'            => null,
                'bill_name'          => null,

                // transaction data:
                'transactions'       => [
                    [
                        'currency_id'           => null,
                        'currency_code'         => 'EUR',
                        'description'           => null,
                        'amount'                => random_int(500, 5000) / 100,
                        'budget_id'             => null,
                        'budget_name'           => null,
                        'category_id'           => null,
                        'category_name'         => null,
                        'source_id'             => 1,
                        'source_name'           => null,
                        'destination_id'        => 2,
                        'destination_name'      => null,
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
     * @throws \Exception
     */
    private function singleWithdrawal(): array
    {
        return
            [
                'type'               => 'withdrawal',
                'date'               => Carbon::create()->format('Y-m-d'),
                'tags'               => '',
                'user'               => $this->user()->id,

                // all custom fields:
                'internal_reference' => null,
                'notes'              => null,

                // journal data:
                'description'        => 'Some TEST withdrawal #' . random_int(1, 10000),
                'piggy_bank_id'      => null,
                'piggy_bank_name'    => null,
                'bill_id'            => null,
                'bill_name'          => null,

                // transaction data:
                'transactions'       => [
                    [
                        'currency_id'           => null,
                        'currency_code'         => 'EUR',
                        'description'           => null,
                        'amount'                => random_int(500, 5000) / 100,
                        'budget_id'             => null,
                        'budget_name'           => null,
                        'category_id'           => null,
                        'category_name'         => null,
                        'source_id'             => null,
                        'source_name'           => 'Checking Account',
                        'destination_id'        => null,
                        'destination_name'      => 'Random TEST expense account #' . random_int(1, 10000),
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
